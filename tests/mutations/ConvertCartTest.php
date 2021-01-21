<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\ConvertCart;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

class ConvertCartTest extends TestCase
{
    public function testIsAllowedIsTrueWhenIsGuestCustomerIsAbsent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {}
}
PAYLOAD;

        $mutation = new ConvertCart(
            $this->createConfig(),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testIsAllowedIsTrueWhenIsGuestCustomerIsPresentButFalse(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "customer_is_guest": 0,
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {}
}
PAYLOAD;
        
        $mutation = new ConvertCart(
            $this->createConfig(),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }


    public function testIsAllowedIsFalseWhenQouteIdIsAbsent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "remote_ip": "8.8.8.8",
        "customer_is_guest": 0,
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {}
}
PAYLOAD;

        $anonymousCartsEnabled = false;
        
        $mutation = new ConvertCart(
            $this->createConfig($anonymousCartsEnabled),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testIsAllowedIsFalseWhenIsGuestCustomer(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "customer_is_guest": 1,
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {}
}
PAYLOAD;

        $anonymousCartsEnabled = false;
        
        $mutation = new ConvertCart(
            $this->createConfig($anonymousCartsEnabled),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testIsAllowedIsFalseWhenHistoricalOrderIsImported(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "extension_attributes": {}
    },
    "area": {}
}
PAYLOAD;
        
        $mutation = new ConvertCart(
            $this->createConfig(),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testIsAllowedIsTrueWhenHistoricalOrderIsImportedAndHistoricalCartConversionIsEnabled(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "extension_attributes": {}
    },
    "area": {}
}
PAYLOAD;

        $anonymousCartsEnabled = false;
        $historicalCartConversion = true;
        
        $mutation = new ConvertCart(
            $this->createConfig($anonymousCartsEnabled, $historicalCartConversion),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testIsAllowedIsFalseWhenRemoteIpIsAbsent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {}
}
PAYLOAD;

        $anonymousCartsEnabled = false;
        
        $mutation = new ConvertCart(
            $this->createConfig($anonymousCartsEnabled),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testIsAllowedIsFalseWhenIsObjectNewExtensionAttributeIsMissing(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "extension_attributes": {}
    },
    "area": {}
}
PAYLOAD;

        $anonymousCartsEnabled = true;
        
        $mutation = new ConvertCart(
            $this->createConfig($anonymousCartsEnabled),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testIsAllowedIsTrueWhenGuestCartsAreEnabled(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "customer_is_guest": 1,
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {}
}
PAYLOAD;

        $anonymousCartsEnabled = false;
        
        $mutation = new ConvertCart(
            $this->createConfig($anonymousCartsEnabled),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testMuationVariables(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "95",
        "remote_ip": "8.8.8.8",
        "extension_attributes": {
            "is_object_new": true
        }
    },
    "area": {
        "website (Magento\\Store\\Model\\Website\\Interceptor)": {
            "code": "unit_test_store"
        }
    }
}
PAYLOAD;

        $mutation = new ConvertCart(
            $this->createConfig(),
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $variables = $mutation->getVariables();

        $this->assertArraySubset(
            [
                'id'       => '95',
                'orderId'  => '1001',
                'provider' => 'unit_test_store'
            ],
            $variables
        );
    }

    private function createConfig($anonymousCartsEnabled = false, $convertHistoricCarts = false): Config
    {
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $config->method('isAnonymousCartsEnabled')
               ->willReturn($anonymousCartsEnabled);
        
        $config->method('convertHistoricalCarts')
               ->willReturn($convertHistoricCarts);
        
        return $config;
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
