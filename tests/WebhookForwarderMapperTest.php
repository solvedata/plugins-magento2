<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\WebhookForwarder\WebhookForwarderMapper;

class WebhookForwarderMapperTest extends TestCase
{
    public function testMapperShouldRemoveSenstiveFields()
    {
        $mapper = new WebhookForwarderMapper();
        $webhookPayload = $mapper->map([
            [
                'payload' => json_encode([
                    'customer' => [
                        'email' => 'test@example.net',
                        'password_hash' => 'hash',
                        'rp_token' => 'reset token'
                    ]
                ])
            ]
        ]);

        $firstEventPayload = $webhookPayload['events'][0]['payload'];
        
        $this->assertEquals('test@example.net', $firstEventPayload['customer']['email']);
        $this->assertFalse(isset($firstEventPayload['customer']['password_hash']));
        $this->assertFalse(isset($firstEventPayload['customer']['rp_token']));
    }

    public function testMapperShouldBeAbleToHandleEventsWithNoSenstiveFields()
    {
        $mapper = new WebhookForwarderMapper();
        $webhookPayload = $mapper->map([
            [
                'payload' => json_encode([
                    'customer' => [
                        'email' => 'test@example.net'
                    ]
                ])
            ]
        ]);

        $firstEventPayload = $webhookPayload['events'][0]['payload'];
        
        $this->assertEquals('test@example.net', $firstEventPayload['customer']['email']);
    }
}
