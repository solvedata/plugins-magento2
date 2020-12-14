<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\ProductMetadata;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use SolveData\Events\Helper\Profile as ProfileHelper;
use SolveData\Events\Model\Logger;

class PayloadConverter
{
    const PROVIDER = ProductMetadata::PRODUCT_NAME;
    const CHANNEL_WEB = 'WEB';

    const ORDER_STATUS_CREATED   = 'CREATED';
    const ORDER_STATUS_PROCESSED = 'PROCESSED';
    const ORDER_STATUS_RETURNED  = 'RETURNED';
    const ORDER_STATUS_CANCELED  = 'CANCELED';

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var ProfileHelper
     */
    protected $profileHelper;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $countries = [];

    /**
     * @param CountryFactory $countryFactory
     * @param ProfileHelper $profileHelper
     * @param RegionFactory $regionFactory
     * @param StoreManagerInterface $storeManager
     */

    public function __construct(
        CountryFactory $countryFactory,
        ProfileHelper $profileHelper,
        RegionFactory $regionFactory,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->countryFactory = $countryFactory;
        $this->profileHelper = $profileHelper;
        $this->regionFactory = $regionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Get country model
     *
     * @param string $countryId
     *
     * @return Country
     */
    protected function getCountry(string $countryId)
    {
        if (!isset($this->countries[$countryId])) {
            /**
             * @var Country $country
             */
            $country = $this->countryFactory->create();
            $this->countries[$countryId] = $country->loadByCode($countryId);
        }

        return $this->countries[$countryId];
    }

    /**
     * Get DataObject array by key
     *
     * @param array $data
     * @param string $searchKey
     *
     * @return array|string|null
     */
    protected function getDataObjectArrayByKey(array $data, string $searchKey)
    {
        foreach ($data as $key => $value) {
            $explodedKey = explode(' (', $key);
            if (count($explodedKey) < 2) {
                continue;
            }
            if ($explodedKey[0] !== $searchKey) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * Get formatted datetime value
     *
     * @param string $time
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFormattedDatetime(string $time = 'now'): string
    {
        $now = new \DateTime($time, new \DateTimeZone('UTC'));

        return $now->format(\DateTime::ISO8601);
    }

    /**
     * Get profile id by email and area data
     *
     * @param string $email
     * @param array $area
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getProfileId(string $email, array $area): ?string
    {
        try {
            $website = $this->getWebsiteDataByArea($area);
            if (empty($website)) {
                throw new \Exception('Website data is not exist in area data');
            }
            if (!is_array($website) || empty($website['website_id'])) {
                throw new \Exception('Website data is incorrect in area data');
            }
    
            return $this->profileHelper->getProfileIdByEmail($email, (int)$website['website_id']);
        } catch (\Throwable $t) {
            $this->logger->debug('failed to retrieve profile id for email', ["email" => $email]);
            $this->logger->error($t);
            return null;
        }
    }

    /**
     * Get store data by scope
     *
     * @param $area
     *
     * @return array|null
     */
    protected function getStoreDataByArea($area): ?array
    {
        return $this->getDataObjectArrayByKey($area, 'store');
    }

    /**
     * Get website data by scope
     *
     * @param $area
     *
     * @return array|null
     */
    protected function getWebsiteDataByArea($area): ?array
    {
        return $this->getDataObjectArrayByKey($area, 'website');
    }

    /**
     * Get payment data for an order
     *
     * @param array $order
     *
     * @return array|null
     */
    public function getOrderPaymentData(array $order): ?array
    {
        return $this->getDataObjectArrayByKey($order, 'payment');
    }

    /**
     * Prepare and return attributes data
     *
     * @param array $area
     *
     * @return array
     */
    public function prepareAttributesData(array $area): array
    {
        $data = [];

        try {
            $store = $this->getStoreDataByArea($area);
            $website = $this->getWebsiteDataByArea($area);
    
            if (!empty($store) && !empty($store['name'])) {
                $storeName = $store['name'];
                $data["store: $storeName"] = $storeName;
            }
    
            if (!empty($website) && !empty($website['name'])) {
                $websiteName = $website['name'];
                $data["website: $websiteName"] = $websiteName;
            }
    
            return $data;
        } catch (\Throwable $t) {
            $this->logger->debug('failed to find store and/or website from area', ["area" => json_encode($area)]);
            $this->logger->error($t);
            
            return $data;
        }
    }

    /**
     * Prepare and return attributes data for orders
     *
     * @param array $order
     * @param array $area
     *
     * @return array
     */
    private function orderAttributes(array $order, array $area): array
    {
        $attributes = $this->prepareAttributesData($area);

        if (!empty($order['gift_cards'])) {
            try {
                $giftCards = json_decode($order['gift_cards'], true);
                $giftCards = array_map(function ($giftCart) {
                    // Pick the subset of fields we are interested in so
                    //  we don't inadvertently map across the gift card's
                    //  code in case it has remaining balance.
                    return [
                        'id' => $giftCart['i'] ?? null,
                        'amount' => $giftCart['a'] ?? null,
                        'authorized' => $giftCart['authorized'] ?? null,
                        'balance' => $giftCart['ba'] ?? null,
                    ];
                }, $giftCards);
                $attributes['gift_cards'] = json_encode($giftCards);
            } catch (\Throwable $t) {
                $this->logger->debug('failed to map gift card(s) in order attributes');
                $this->logger->error($t);
            }
        }

        return $attributes;
    }

    /**
     * Prepare and return attributes data for payments
     *
     * @param array $order
     * @param array $area
     *
     * @return array
     */
    private function paymentAndReturnAttributes(array $payment, array $area): array
    {
        $attributes = $this->prepareAttributesData($area);

        if (!empty($payment[OrderPaymentInterface::METHOD])) {
            $attributes['method'] = $payment[OrderPaymentInterface::METHOD];
        }

        return $attributes;
    }

    public function getOrderProvider(array $area): string
    {
        $website = $this->getWebsiteDataByArea($area);
        return !empty($website) ? $website['code'] : self::PROVIDER;
    }


    /**
     * Convert addresses payload to Solve GraphQL Address variables data
     *
     * @param array $addresses
     *
     * @return array
     */
    public function convertAddressesData(array $addresses): array
    {
        try {
            $data = [];
            foreach ($addresses as $key => $address) {
                $key = (string)$key;
                $data[$key] = $this->convertAddressData($address);
                if (empty($data[$key])) {
                    continue;
                }

                if (empty($address[AddressInterface::DEFAULT_BILLING])
                    || empty($address[AddressInterface::DEFAULT_SHIPPING])
                ) {
                    continue;
                }
                // Create copy address data but with a different type
                if ($address[AddressInterface::DEFAULT_BILLING] && $address[AddressInterface::DEFAULT_SHIPPING]) {
                    $addressType = $data[$key]['type'] == Address::TYPE_BILLING
                        ? Address::TYPE_SHIPPING
                        : Address::TYPE_BILLING;
                    $data[$key . '_copy'] = $data[$key];
                    $data[$key . '_copy']['type'] = $addressType;
                }
            }

            return array_values($data);
        } catch (\Throwable $t) {
            $this->logger->debug('failed to convert addresses');
            $this->logger->error($t);
            return [];
        }
    }

    /**
     * Convert address data to Solve GraphQL Address variables data
     *
     * @param array $address
     *
     * @return array
     */
    public function convertAddressData(array $address): array
    {
        if (!empty($address['address_type'])) {
            $data['type'] = $address['address_type'];
        } else {
            if (!empty($address['default_shipping'])) {
                $data['type'] = Address::TYPE_SHIPPING;
            } else {
                // Temporary solution for test API
                $data['type'] = Address::TYPE_BILLING;
            }
        }

        if (!empty($address['region']) && is_array($address['region'])) {
            $data += [
                'province'     => $address['region']['region'] ?? null,
                'provinceCode' => $address['region']['region_code'] ?? null,
            ];
        } else {
            $data += [
                'province'     => $address['region'] ?? null,
                'provinceCode' => $address['provinceCode'] ?? null,
            ];
        }

        if (empty($data['provinceCode']) && !empty($data['province']) && !empty($address['country_id'])) {
            $region = $this->regionFactory->create();
            $region = $region->loadByName($data['province'], $address['country_id']);
            $data['provinceCode'] = $region->getCode();
        }

        if (!empty($address['street'])) {
            if (is_scalar($address['street'])) {
                $data['street'] = $address['street'];
            } else if (is_array($address['street'])) {
                $data += [
                    'street'  => array_shift($address['street']),
                    'street2' => array_shift($address['street']),
                ];
            }
        }
        $data += [
            'city'       => $address['city'] ?? null,
            'postalCode' => $address['postcode'] ?? null,
        ];

        if (empty($address['country_id'])) {
            return $data;
        }
        $country = $this->getCountry($address['country_id']);
        $data += [
            'countryCode' => $country->getCountryId(),
            'countryName' => $country->getName(),
        ];

        return $data;
    }

    /**
     * Convert customer data to Solve GraphQL Profile variables data
     *
     * @param array $customer
     * @param array $area
     *
     * @return array
     */
    public function convertProfileData(array $customer, array $area): array
    {
        $data = [
            'email'      => $customer[CustomerInterface::EMAIL],
            'firstName'  => $customer[CustomerInterface::FIRSTNAME],
            'lastName'   => $customer[CustomerInterface::LASTNAME],
            'fullName'   => $customer[CustomerInterface::FIRSTNAME] . ' ' . $customer[CustomerInterface::LASTNAME],
            'attributes' => json_encode($this->prepareAttributesData($area)),
        ];

        if (!empty($customer[CustomerInterface::DOB])) {
            $birthDate = new \DateTime($customer[CustomerInterface::DOB]);
            $data['birthDate'] = $birthDate->format('Y-m-d');
        }

        if (!empty($customer[CustomerInterface::GENDER])) {
            $data['gender'] = is_string($customer[CustomerInterface::GENDER]) ? $customer[CustomerInterface::GENDER] : null;
        }

        if (!empty($customer[CustomerInterface::KEY_ADDRESSES])) {
            $data['addresses'] = $this->convertAddressesData($customer[CustomerInterface::KEY_ADDRESSES]);
        }
        /*
        $phoneNumbers = [];
        foreach ($customer[CustomerInterface::KEY_ADDRESSES] as $address) {
            $phoneNumbers[] = $address[AddressInterface::TELEPHONE];
        }
        $data['phoneNumber'] = !empty($phoneNumbers) ? $phoneNumbers[0] : '';
        */

        return $data;
    }

    /**
     * Convert order data to Solve GraphQL Order variables data
     *
     * @param array $order
     * @param array $allVisibleItems
     * @param array $area
     *
     * @return array
     *
     * @throws \Exception
     */
    public function convertOrderData(array $order, array $allVisibleItems, array $area): array
    {
        $data = [
            'id'              => $order[Order::INCREMENT_ID],
            'status'          => $this->orderStatus($order),
            'currency'        => $order[OrderInterface::ORDER_CURRENCY_CODE],
            'items'           => $this->convertItemsData($allVisibleItems),
            'storeIdentifier' => (string)$order[OrderInterface::STORE_ID],
            'channel'         => self::CHANNEL_WEB,
            'adjustments'     => $this->prepareOrderAdjustments($order),
            'attributes'      => json_encode($this->orderAttributes($order, $area)),
            'provider'        => $this->getOrderProvider($area),
        ];

        if (!empty($order['created_at'])) {
            $data['created_at'] = $this->getFormattedDatetime($order['created_at']);
        }

        $addressesData = $this->convertAddressesData($order['addresses']);
        foreach ($addressesData as $address) {
            if ($address['type'] == Order\Address::TYPE_BILLING) {
                $data['billingAddress'] = $address;
                continue;
            }
            if ($address['type'] == Order\Address::TYPE_SHIPPING) {
                $data['shippingAddress'] = $address;
                continue;
            }
        }

        $id = $this->getProfileId($order['customer_email'], $area);
        if (!empty($id)) {
            $data['profile_id'] = $id;
        }

        return $data;
    }

    /**
     * Returns the Solve order status for a given Magento order
     *
     * @param array $order
     *
     * @return string
     */
    private function orderStatus(array $order): string
    {
        if (empty($order[OrderInterface::STATE])) {
            if (!empty($order[Order::TOTAL_REFUNDED])) {
                return self::ORDER_STATUS_RETURNED;
            }

            if (!empty($order[Order::TOTAL_PAID])) {
                return self::ORDER_STATUS_PROCESSED;
            }

            return self::ORDER_STATUS_CREATED;
        }

        switch ($order[OrderInterface::STATE]) {
            case Order::STATE_NEW:
                return self::ORDER_STATUS_CREATED;
            case Order::STATE_CLOSED:
                return self::ORDER_STATUS_RETURNED;
            case Order::STATE_CANCELED:
                return self::ORDER_STATUS_CANCELED;
            case Order::STATE_COMPLETE:
                return self::ORDER_STATUS_PROCESSED;
            default:
                if (!empty($order[OrderInterface::TOTAL_REFUNDED])) {
                    return self::ORDER_STATUS_RETURNED;
                }

                if (!empty($order[OrderInterface::TOTAL_PAID])) {
                    return self::ORDER_STATUS_PROCESSED;
                }

                // Work around for the Payment express extension which dispatches a `sales_order_save_after` event
                //  momentarily after the canceled event.
                // If we didn't have this work around the order would be un-canceled in Solve moments after it was canceled.
                if ($order[OrderInterface::STATE] == Order::STATE_PENDING_PAYMENT &&
                    $order[OrderInterface::STATUS] == "paymentexpress_failed") {
                    return self::ORDER_STATUS_CANCELED;
                }
                
                return self::ORDER_STATUS_PROCESSED;
        }
    }

    /**
     * Return the input to create a Solve payment in GraphQL from an order's payment data.
     *
     * @param array $order
     * @param array $area
     *
     * @return array
     */
    public function convertPaymentData(array $order, array $area): array
    {
        $payment = $this->getOrderPaymentData($order);
        $data = [
            'id'         => $payment[OrderPaymentInterface::ENTITY_ID],
            'order_id'   => $order[OrderInterface::INCREMENT_ID],
            'provider'   => $this->getOrderProvider($area),
            'amount'     => sprintf('%.4F', $payment[OrderPaymentInterface::AMOUNT_PAID]),
            'attributes' => json_encode($this->paymentAndReturnAttributes($payment, $area)),
        ];

        return $data;
    }

    /**
     * Return the input to create a Solve return in GraphQL from an order's payment data.
     *
     * @param array $order
     * @param array $area
     *
     * @return array
     */
    public function convertReturnData(array $order, array $area): array
    {
        $payment = $this->getOrderPaymentData($order);
        $data = [
            'id'            => $payment[OrderPaymentInterface::ENTITY_ID],
            'order_id'      => $order[OrderInterface::INCREMENT_ID],
            'provider'      => $this->getOrderProvider($area),
            'return_reason' => 'Refund',
            'adjustments'   => [
                [
                    'amount'      => sprintf('%.4F', $payment[OrderPaymentInterface::AMOUNT_REFUNDED]),
                    'description' => 'Refund',
                ]
            ],
            'attributes'  => json_encode($this->paymentAndReturnAttributes($payment, $area)),
        ];

        return $data;
    }

    /**
     * Return Solve's order adjustments for a Magento order
     *
     * @param array $order
     *
     * @return array
     */
    private function prepareOrderAdjustments(array $order): array
    {
        $adjustments = [];

        if (!empty($order['gift_cards_amount'])) {
            $adjustments[] = [
                'amount'      => sprintf('-%.4F', $order['gift_cards_amount']),
                'description' => 'Gift card',
            ];
        }
        
        $adjustments = array_merge($adjustments, [
            [
                'amount'      => sprintf('%.4F', $order[OrderInterface::TAX_AMOUNT]),
                'description' => 'Tax amount',
            ],
            [
                'amount'      => sprintf('%.4F', $order[OrderInterface::DISCOUNT_AMOUNT]),
                'description' => 'Discount amount',
            ],
            [
                'amount'      => sprintf('%.4F', $order[OrderInterface::SHIPPING_AMOUNT]),
                'description' => 'Shipping amount',
            ],
        ]);

        return $adjustments;
    }

    /**
     * Prepare and return attributes data for quote item of configurable product
     *
     * @param array $item
     *
     * @return array
     */
    protected function prepareConfigurableProductAttributesData(array $item)
    {
        $data = [];

        if (!empty($item['product_options']['attributes_info'])) {
            foreach ($item['product_options']['attributes_info'] as $attributeInfo) {
                if (empty($attributeInfo['option_id'])) {
                    continue;
                }
                $data[$attributeInfo['option_id']] = $attributeInfo;
            }

            return $data;
        }

        $qtyOption = reset($item['qty_options']);
        $simpleProduct = $this->getDataObjectArrayByKey($qtyOption, 'product');
        $configurableProduct = $this->getDataObjectArrayByKey($item, 'product');
        if (empty($configurableProduct['_cache_instance_used_attributes'])) {
            return [];
        }
        foreach ($configurableProduct['_cache_instance_used_attributes'] as $attribute) {
            $productAttribute = $this->getDataObjectArrayByKey($attribute, 'product_attribute');
            if (empty($productAttribute) || empty($attribute['options'])) {
                continue;
            }
            $key = $attribute['attribute_id'];
            if (!empty($productAttribute['store_label'])) {
                $data[$key]['label'] = $productAttribute['store_label'];
            } else if (!empty($productAttribute['frontend_label'])) {
                $data[$key]['label'] = $productAttribute['frontend_label'];
            } else {
                continue;
            }
            $data[$key] += [
                'option_id'    => $attribute['attribute_id'],
                'option_value' => $simpleProduct[$productAttribute['attribute_code']],
            ];
            foreach ($attribute['options'] as $option) {
                if ($option['value_index'] !== $simpleProduct[$productAttribute['attribute_code']]) {
                    continue;
                }
                if (!empty($option['store_label'])) {
                    $data[$key]['value'] = $option['store_label'];
                } else if (!empty($option['default_label'])) {
                    $data[$key]['value'] = $option['default_label'];
                } else if (!empty($option['label'])) {
                    $data[$key]['value'] = $option['label'];
                }
                continue 2;
            }
        }

        return $data;
    }

    /**
     * Convert order items data to Solve GraphQL Item variables data
     *
     * @param array $items
     * @param bool $onlyVisible
     *
     * @return array
     */
    public function convertItemsData(array $items, bool $onlyVisible = true): array
    {
        $data = [];
        foreach ($items as $key => $item) {
            if ($onlyVisible && !empty($item['parent_item_id'])) {
                continue;
            }
            if ($onlyVisible && !empty($this->getDataObjectArrayByKey($item, 'parent_item'))) {
                continue;
            }

            if (!empty($item['qty'])) {
                $data[$key]['quantity'] = $item['qty'];
            } else if (!empty($item['qty_ordered'])) {
                $data[$key]['quantity'] = (int)$item['qty_ordered'];
            } else {
                continue;
            }

            $itemAttributes = [
                'magento_item_id' => $item['item_id'] ?? null,
                'magento_original_price' => $item['original_price'] ?? null,
                'magento_final_price' => $item['final_price'] ?? null,
                'magento_price' => $item['price'] ?? null,
            ];

            if ($item['product_type'] == Configurable::TYPE_CODE) {
                $itemAttributes = $itemAttributes + $this->prepareConfigurableProductAttributesData($item);
            }

            $data[$key]['itemAttributes'] = json_encode($itemAttributes);

            /**
             * Fix because price is NULL in quote item
             * https://github.com/magento/magento2/issues/18685
             *
             * @customization START
             */
            if (empty($item['final_price']) && empty($item['price'])) {
                if ($item['product_type'] == Configurable::TYPE_CODE) {
                    $qtyOption = reset($item['qty_options']);
                    $product = $this->getDataObjectArrayByKey($qtyOption, 'product');
                    if (!empty($qtyOption)) {
                        $item['price'] = $product['price'];
                    }
                } else {
                    $product = $this->getDataObjectArrayByKey($item, 'product');
                    if (!empty($product)) {
                        $item['price'] = $product['price'];
                    }
                }
            }
            /** @customization END */

            $data[$key] += [
                'productId' => $item['product_id'],
                'title'     => $item['name'],
                'sku'       => $item['sku'],
                'price'     => sprintf('%.4F', $item['final_price'] ?? $item['price']),
            ];
        }

        return array_values($data);
    }

    /**
     * Prepare cart data to Solve GraphQL Cart argument type
     *
     * @param array $quote
     * @param array $allVisibleItems
     * @param array $area
     *
     * @return array
     *
     * @throws \Exception
     */
    public function convertCartData(array $quote, array $allVisibleItems, array $area): array
    {
        $data = [
            'id'         => $quote['entity_id'],
            'currency'   => $quote['quote_currency_code'],
            'items'      => $this->convertItemsData($allVisibleItems),
            'attributes' => json_encode($this->prepareAttributesData($area)),
            'provider'   => $this->getOrderProvider($area),
        ];

        if (!empty($quote['customer_email'])) {
            // Defensively handle a failed profile ID lookup in case
            //  the cart can be retroactively linked to a profile.
            $profileId = $this->getProfileId($quote['customer_email'], $area);
            $data['profile_id'] = !empty($profileId) ? $profileId : null;
        }

        return $data;
    }
}
