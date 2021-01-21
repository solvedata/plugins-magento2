<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\RegisterCustomer;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

class RegisterCustomerTest extends TestCase
{
    public function testEveryOrderShouldRegisterACustomer(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {},
    "area": {}
}
PAYLOAD;

        $mutation = new RegisterCustomer(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

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

        $mutation = new RegisterCustomer(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $variables = $mutation->getVariables();
        $this->assertArrayHasKey('email', $variables['input']);
        $this->assertEquals(
            $variables['input']['email'],
            'jane@example.com'
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
