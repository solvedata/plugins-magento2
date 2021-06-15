<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Cart;

use SolveData\Events\Helper\ReclaimCartTokenHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;

class Reclaim extends \Magento\Framework\App\Action\Action
{
    private $context;
    private $cart;
    private $quoteRepository;
    private $config;
    private $logger;
    private $eventRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        Config $config,
        Logger $logger,
        EventRepository $eventRepository
    ) {
        $this->context = $context;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
        $this->logger = $logger;
        $this->eventRepository = $eventRepository;

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
                $this->cart->setQuote($quote);
                $this->cart->save();

                if (!empty($existingCartId) && $existingCartId !== $quoteId) {
                    // `slv_ecid` is short for "Solve existing cart ID"
                    // This is a diagnostic query parameter so we understand that the previous "existing" cart has been replaced.
                    $params['slv_ecid'] = $existingCartId;
                }

                $this->sendCustomEvent('custom_magento_abandoned_cart_reclaim', [
                    'quoteId' => $quoteId,
                    'existingQuoteId' => $existingCartId,
                    'reclaimToken' => $token
                ]);

                $redirect = $this->resultRedirectFactory->create();
                $redirect->setPath('checkout/cart', ['_query' => $params]);
                return $redirect;
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

        // Add `slv_ac_err=1` to the existing query parameters and redirect to the home page
        $params['slv_ac_err'] = '1';

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('', ['_query' => $params]);

        return $redirect;
    }

    private function getExistingCartId(): ?string {
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

    private function sendCustomEvent(string $eventType, array $payload): void
    {
        $event = $this->eventRepository->create();
        $event->placeCustomEvent($eventType, $payload);
    }
}
