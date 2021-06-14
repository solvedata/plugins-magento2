<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Helper\ReclaimCartTokenHelper;
use SolveData\Events\Model\Logger;

class ReclaimCartTokenHelperTest extends TestCase
{
    public function testGeneratedTokenIsValid(): void
    {
        $quoteId = 'a quote id';
        $now = new DateTime();
        $secret = 'hmac secret';

        $helper = new ReclaimCartTokenHelper($this->createLogger());

        $token = $helper->generateReclaimToken($quoteId, $now, $secret);
        $parsedQuoteId = $helper->parseAndValidateToken($token, $secret);

        $this->assertNotNull($parsedQuoteId);
        $this->assertEquals($quoteId, $parsedQuoteId);
    }

    public function testCanSuccessfullyParseToken(): void
    {
        $expectedQuoteId = 'a quote id';
        $secret = 'hmac secret';
        $token = base64_encode(json_encode([
            "id" => $expectedQuoteId,
            "sa" => 1816260486,
            "ts" => 1623573129,

            // As per https://gchq.github.io/CyberChef/#recipe=HMAC(%7B'option':'UTF8','string':'hmac%20secret'%7D,'SHA256')&input=YSBxdW90ZSBpZHwxODE2MjYwNDg2fDE2MjM1NzMxMjk
            "hm" => "3922193193622358633d7e25aa067c876fe7abd8ac48f5d063dfafae51e6c53a"
        ]));

        $helper = new ReclaimCartTokenHelper($this->createLogger());
        $parsedQuoteId = $helper->parseAndValidateToken($token, $secret);

        $this->assertNotNull($parsedQuoteId);
        $this->assertEquals($expectedQuoteId, $parsedQuoteId);
    }

    public function testCanSuccessfullyParseUrlEncodedToken(): void
    {
        $expectedQuoteId = 'a quote id';
        $secret = 'hmac secret';
        $token = 'ewogICJpZCI6ICJhIHF1b3RlIGlkIiwKICAic2EiOiAxODE2MjYwNDg2LAogICJ0cyI6IDE2MjM1NzMxMjksCiAgImhtIjogIjM5MjIxOTMxOTM2MjIzNTg2MzNkN2UyNWFhMDY3Yzg3NmZlN2FiZDhhYzQ4ZjVkMDYzZGZhZmFlNTFlNmM1M2EiCn0%3D';

        $helper = new ReclaimCartTokenHelper($this->createLogger());
        $parsedQuoteId = $helper->parseAndValidateToken($token, $secret);

        $this->assertNotNull($parsedQuoteId);
        $this->assertEquals($expectedQuoteId, $parsedQuoteId);
    }

    public function testWillReturnNullIfTokenIsInvalid(): void
    {
        $secret = 'hmac secret';
        $token = 'not a valid token';

        $helper = new ReclaimCartTokenHelper($this->createLogger());
        $parsedQuoteId = $helper->parseAndValidateToken($token, $secret);

        $this->assertNull($parsedQuoteId);
    }

    public function testWillReturnNullIfHmacIsInvalid(): void
    {
        $secret = 'hmac secret';
        $token = base64_encode(json_encode([
            "id" => 'a quote id',
            "sa" => 1816260486,
            "ts" => 1623573129,
            "hm" => "not the expected hmac"
        ]));

        $helper = new ReclaimCartTokenHelper($this->createLogger());
        $parsedQuoteId = $helper->parseAndValidateToken($token, $secret);

        $this->assertNull($parsedQuoteId);
    }

    private function createLogger(): Logger
    {
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        return $logger;
    }
}
