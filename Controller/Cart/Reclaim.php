<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Cart;

use SolveData\Events\Model\Logger;

class Reclaim extends \Magento\Framework\App\Action\Action
{
    private $context;
    private $cart;
    private $quoteRepository;
    private $quoteIdMaskFactory;
    private $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        Logger $logger
    ) {
        $this->context = $context;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->context->getRequest();
        $params = $request->getParams();

        $maskedQuoteId = empty($params['mq_id']) ? '' : $params['mq_id'];

        unset($params['mq_id']);

        if (!empty($maskedQuoteId)) {
            try {
                $existingCartId = $this->getExistingCartId();

                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($maskedQuoteId, 'masked_id');
                $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
                $this->cart->setQuote($quote);
                $this->cart->save();

                if (!empty($existingCartId) && $existingCartId !== $quoteIdMask->getQuoteId()) {
                    // `slv_ecid` is short for "Solve existing cart ID"
                    // This is a diagnostic query parameter so we understand that the previous "existing" cart has been replaced.
                    $params['slv_ecid'] = $existingCartId;
                }

                $redirect = $this->resultRedirectFactory->create();
                $redirect->setPath('checkout/cart', ['_query' => $params]);
                return $redirect;
            } catch (\Throwable $t) {
                $this->logger->debug('failed to reclaim cart', ['masked_quote_id' => $maskedQuoteId, 'params' => $params]);
                $this->logger->error($t);
            }
        } else {
            $this->logger->warn('empty quote id passed to cart reclaim', ['quote_id' => $maskedQuoteId, 'params' => $params]);
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
            $this->logger->debug('failed to get existing cart id before reclaiming a cart');
            $this->logger->warn($t);

            return null;
        }
    }
}
