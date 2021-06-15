<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Cart;

use SolveData\Events\Helper\ReclaimCartTokenHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Logger;

class Reclaim extends \Magento\Framework\App\Action\Action
{
    private $context;
    private $cart;
    private $quoteRepository;
    private $customerSession;
    private $config;
    private $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        Config $config,
        Logger $logger
    ) {
        $this->context = $context;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
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

                // Allow the cart to be accessed even if the user is not logged
                // in. If the user is actually logged in (with any account) the
                // cart will be immeidately reassociated with this account.
                //
                // We also put the qcuid (quote customer id) and scuid (session
                // customer id) in the query params to create a trail we can
                // use for debugging in the future if need be (by querying
                // pageviews).
                $params['qcuid'] = $quote->getCustomerId();
                $params['scuid'] = $this->customerSession->getCustomerId();

                if ($this->config->isCartDisassociationEnabled()) {
                    if (empty($quote->getCustomerId())) {
                        // Disassociate quote customer
                        $params['dqcu'] = false;
                    } else {
                        $params['dqcu'] = true;
                        $quote->setCustomerId(null);
                        $quote->save();
                    }
                } else {
                    $params['dqcu'] = false;
                }
                $this->cart->setQuote($quote);
                $this->cart->save();

                if (!empty($existingCartId) && $existingCartId !== $quoteId) {
                    // `slv_ecid` is short for "Solve existing cart ID"
                    // This is a diagnostic query parameter so we understand that the previous "existing" cart has been replaced.
                    $params['slv_ecid'] = $existingCartId;
                }

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
}
