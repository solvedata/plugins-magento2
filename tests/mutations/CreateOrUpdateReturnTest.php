<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\CreateOrUpdateReturn;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;
use SolveData\Events\Model\Logger;

class CreateOrUpdateReturnTest extends TestCase
{
    public function testNotAllowedIfPaymentDataDoesIsNotPresent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {},
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdateReturn(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testNotAllowedIfAmountRefundedIsAbsent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {}
    },
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdateReturn(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testNotAllowedIfAmountRefunedIsZero(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {
            "amount_refunded": "0.0000"
        }
    },
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdateReturn(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testAllowedIfAmountRefunedIsNonZero(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {
            "amount_refunded": "1.0000"
        }
    },
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdateReturn(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testMuationInput(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {
            "amount_refunded": "1.0000"
        }
    },
    "area": {
        "website (Magento\\Store\\Model\\Website\\Interceptor)": {
            "code": "unit_test_store"
        }
    }
}
PAYLOAD;

        $mutation = new CreateOrUpdateReturn(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $variables = $mutation->getVariables();

        $this->assertArraySubset(
            [
                'id'            => '1001-return',
                'order_id'      => '1001',
                'provider'      => 'unit_test_store',
                'return_reason' => 'Refund',
                'adjustments'   => [
                    [
                        'amount'      => '1.0000',
                        'description' => 'Refund',
                    ]
                ],
            ],
            $variables['input']
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
