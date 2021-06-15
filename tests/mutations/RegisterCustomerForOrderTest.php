<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\RegisterCustomerForOrder;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;
use SolveData\Events\Model\Logger;

class RegisterCustomerForOrderTest extends TestCase
{
    public function testEveryOrderShouldRegisterACustomer(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {},
    "area": {}
}
PAYLOAD;

        $mutation = new RegisterCustomerForOrder(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['create_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testMuationInputIncludeEmail(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "customer_email": "jane@example.com"
    },
    "area": {}
}
PAYLOAD;

        $mutation = new RegisterCustomerForOrder(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['create_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $variables = $mutation->getVariables();
        $this->assertArrayHasKey('email', $variables['input']);
        $this->assertEquals(
            $variables['input']['email'],
            'jane@example.com'
        );
    }

    private function createPayloadConverter(): PayloadConverter
    {
        $config = $this->getMockBuilder('SolveData\Events\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

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
        
        $quoteIdMaskFactory = $this->getMockBuilder('Magento\Quote\Model\QuoteIdMaskFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->createLogger();
        
        return new PayloadConverter(
            $config,
            $countryFactory,
            $profileHelper,
            $regionFactory,
            $storeManager,
            $quoteIdMaskFactory,
            $logger
        );
    }

    private function createLogger(): Logger {
        return $this->getMockBuilder('SolveData\Events\Model\Logger')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
