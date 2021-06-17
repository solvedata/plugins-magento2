<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Cart;

use SolveData\Events\Helper\ReclaimCartTokenHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Logger;
use SolveData\Events\Helper\QuoteMerger;

class Reclaim extends \Magento\Framework\App\Action\Action
{
    private $context;
    private $cart;
    private $quoteRepository;
    private $customerSession;
    private $config;
    private $logger;
    private $quoteMerger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param QuoteMerger $quoteMerger
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        QuoteMerger $quoteMerger,
        Config $config,
        Logger $logger
    ) {
        $this->context = $context;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->quoteMerger = $quoteMerger;
        $this->config = $config;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->context->getRequest();
        $params = $request->getParams();

        $token = empty($params['cart']) ? '' : $params['cart'];
        unset($params['cart']);

        if (!empty($token)) {
            try {
                $secret = $this->config->getHmacSecret();
                if (empty($secret)) {
                    throw new \Exception("Cannot reclaim cart as HMAC secret is not set");
                }

                $tokenHelper = new ReclaimCartTokenHelper($this->logger);
                $quoteId = $tokenHelper->parseAndValidateToken($token, $secret);
                if (empty($quoteId)) {
                    throw new \Exception("Cannot reclaim cart as token is invalid");
                }

                $existingCartId = $this->getExistingCartId();

                $this->logger->debug('Reclaiming cart', [
                    'quoteId' => $quoteId,
                    'existingCartId' => $existingCartId
                ]);

                $quote = $this->quoteRepository->get($quoteId);

                // Set the "quote's customer ID" and the "session's customer ID" as `qc` & `sc` respectively.
                $params['qc'] = $quote->getCustomerId();
                $params['sc'] = $this->customerSession->getCustomerId();

                // Set the "existing cart ID" and the "reclaimed cart ID" as `eq` & `rq` respectively.
                $params['eq'] = $existingCartId;
                $params['rq'] = $quoteId;

                // Easy case: If the customer's current quote is the quote to reclaim, then there is nothing to do.
                if ($quoteId === $existingCartId) {
                    // Add a diagnostic query parameter to record that we have "no-op"'d as the customer's
                    //      existing cart is the cart to reclaim.
                    $params['np'] = 1;
                    return $this->checkoutRedirection($params);
                }

                // Merge the reclaimed quote into their existing quote. Note
                // this getter creates the quote if it doesn't exist.
                $existingQuote = $this->cart->getQuote();
                // We take the items from the source $quote and merge them into
                // the destination $existingQuote. If an item exists in both
                // the $quote and $existingQuote, we use the quantity from
                // $quote. This avoids doubling up items already in the cart.
                $this->quoteMerger->merge($existingQuote, $quote);

                // Add a diagnostic query parameter to record that we have merged quotes
                $params['mq'] = 1;

                $this->saveQuoteForCustomer($existingQuote);
                return $this->checkoutRedirection($params);
            } catch (\Throwable $t) {
                $this->logger->error('Failed to reclaim cart: unhandled exception while trying to save cart.', [
                    'exception' => $t,
                    'token' => $token,
                    'params' => $params
                ]);
            }
        } else {
            $this->logger->warning('Failed to reclaim cart: empty quote id passed to cart reclaim.', [
                'token' => $token,
                'params' => $params
            ]);
        }

        // Add `ac_err=1` to the existing query parameters and redirect to the home page
        $params['ac_err'] = '1';

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('', ['_query' => $params]);

        return $redirect;
    }

    private function saveQuoteForCustomer($quote)
    {
        $this->cart->setQuote($quote);
        $this->cart->save();

        $checkoutSession = $this->cart->getCheckoutSession();
        $checkoutSession->setQuoteId($quote->getId());
    }

    private function checkoutRedirection($params)
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart', ['_query' => $params]);
        return $redirect;
    }

    private function getExistingCartId(): ?string
    {
        try {
            $checkoutSession = $this->cart->getCheckoutSession();
            return $checkoutSession->getQuote()->getId();
        } catch (\Throwable $t) {
            $this->logger->warning('Failed to get existing cart id before reclaiming a cart.', [
                'exception' => $t
            ]);
            return null;
        }
    }
}
