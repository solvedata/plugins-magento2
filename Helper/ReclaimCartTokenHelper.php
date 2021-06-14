<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use DateTime;
use SolveData\Events\Model\Logger;

/**
 * Helpers to generate, parse & validate tokens which can used to reclaim abandoned carts.
 * 
 * The token's authenticity is secured by a HMAC to prevent the token from being guessed.
 * 
 * A HMAC is used instead of Magento's masked quote IDs because every cart doesn't necessary have a masked ID
 *      and creating a new masked ID can fail (via a foreign key constraint) if the cart has been deleted
 *      by the time a cart event being processed by this extension's queue.
 */
class ReclaimCartTokenHelper
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Generate a token to allow this cart to be reclaimed.
     *
     * @param string $quoteId
     * @param string $time
     * @param string $secret Secret to be used to generate a HMAC to ensure the authenticity of the reclaim token
     *
     * @return string The token which can be used to reclaim the cart
     */
    public function generateReclaimToken(string $quoteId, DateTime $time, string $secret): string
    {
        // Include the salt & timestamp in the token to increase the entropy of the HMAC'd message.
        // The timestamp field is currently only included in case it's useful for future functionality.
        $salt = rand();
        $timestamp = $time->getTimestamp();

        $data = [
            'id' => $quoteId,
            'sa' => $salt,
            'ts' => $timestamp,
            'hm' => hash_hmac('sha256', "$quoteId|$salt|$timestamp", $secret),
        ];

        return base64_encode(json_encode($data));
    }

    /**
     * Parse and validate a cart reclaim token.
     *
     * @param string $token
     * @param string $secret Secret to be used to generate a HMAC to ensure the authenticity of the reclaim token
     *
     * @return string The quote ID if the token was valid, otherwise null
     */
    public function parseAndValidateToken(string $token, string $secret): ?string
    {
        try {
            // URL decode the data before base64 decoding it just in case it has been URL
            //      encoded twice as it passed through different external services.
            // This usually shouldn't be necessary as the token will be URL decoded in Magento's web framework
            //      before our controller code is ran. 
            $data = json_decode(base64_decode(urldecode($token)), true);

            $quoteId = $data['id'];
            $salt = $data['sa'];
            $timestamp = $data['ts'];
            $hmac = $data['hm'];

            if (empty($quoteId) || empty($salt) || empty($timestamp) || empty($hmac)) {
                $this->logger->debug('Not all the expected fields exist in the reclaim cart token', [
                    'token' => $token,
                    'quoteId' => $quoteId,
                    'salt' => $salt,
                    'timestamp' => $timestamp,
                    'hmac' => $hmac
                ]);
                return null;
            }

            $expectedHmac = hash_hmac('sha256', "$quoteId|$salt|$timestamp", $secret);
            if (!hash_equals($expectedHmac, $hmac)) {
                $this->logger->debug('The reclaim tokens\'s hmac does not match the expected value', [
                    'token' => $token,
                    'exptected_hmac' => $expectedHmac
                ]);
                return null;
            }

            return $quoteId;
        } catch (\Throwable $t) {
            $this->logger->warn('Unexpected error while deserializing reclaim cart token', [
                'exception' => $t,
                'token' => $token
            ]);
            return null;
        }
    }
}
