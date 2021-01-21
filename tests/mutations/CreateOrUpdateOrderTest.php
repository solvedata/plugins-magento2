<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\CreateOrUpdateOrder;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

class CreateOrUpdateOrderTest extends TestCase
{
    public function testIsAlwaysAllowed(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {},
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdateOrder(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testMuationInput(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "q-123",
        "customer_email": "jane@example.com",
        "order_currency_code": "USD",
        "store_id": 1,
        "shipping_amount": "1.0000",
        "tax_amount": "0.2000",
        "addresses": []
    },
    "orderAllVisibleItems": [],
    "area": {
        "website (Magento\\Store\\Model\\Website\\Interceptor)": {
            "code": "unit_test_store"
        }
    }
}
PAYLOAD;

        $mutation = new CreateOrUpdateOrder(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $variables = $mutation->getVariables();

        $this->assertArraySubset(
            [
                'id'              => '1001',
                'provider'        => 'unit_test_store',
                'status'          => 'CREATED',
                'currency'        => 'USD',
                'items'           => [],
                'storeIdentifier' => '1',
            ],
            $variables['input']
        );

        $this->assertArraySubset(
            [
                'magento_quote_id'       => 'q-123',
                'magento_customer_email' => 'jane@example.com'
            ],
            json_decode($variables['input']['attributes'], true)
        );
    }

    private function createPayloadConverter(): PayloadConverter
    {
        $countryFactory = $this->getMockBuilder('Magento\Directory\Model\CountryFactory')
            ->disableOriginalConstructor()
            ->getMock();
        
        $profileHelper = $this->getMockBuilder('SolveData\Events\Helper\Profile')
            ->disableOriginalConstructor()
            ->getMock();
        
        $regionFactory = $this->getMockBuilder('Magento\Directory\Model\RegionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        
        $storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        
        $logger = $this->getMockBuilder('SolveData\Events\Model\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        
        return new PayloadConverter(
            $countryFactory,
            $profileHelper,
            $regionFactory,
            $storeManager,
            $logger
        );
    }
}
