<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;

/**
 * Class View.
 */
class View extends \Magento\NegotiableQuote\Controller\Adminhtml\Quote\View
{
    /**
     * Array of actions which can be processed without secret key validation.
     *
     * @var array
     */
    protected $_publicActions = ['view'];

    /**
     * @var \Magento\NegotiableQuote\Model\Discount\StateChanges\Provider
     */
    private $messageProvider;

    /**
     * @var \Magento\NegotiableQuote\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    private $negotiableQuoteHelper;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\Discount\StateChanges\Provider $messageProvider
     * @param \Magento\NegotiableQuote\Model\Cart $cart
     * @param \Magento\NegotiableQuote\Helper\Quote $negotiableQuoteHelper
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\Discount\StateChanges\Provider $messageProvider,
        \Magento\NegotiableQuote\Model\Cart $cart,
        \Magento\NegotiableQuote\Helper\Quote $negotiableQuoteHelper,
        \Magento\NegotiableQuote\Model\Status\LabelProviderInterface $labelProvider
    ) {
        parent::__construct(
            $context,
            $logger,
            $quoteRepository,
            $negotiableQuoteManagement,
			$messageProvider,
			$cart,
			$negotiableQuoteHelper
        );
        $this->messageProvider = $messageProvider;
        $this->cart = $cart;
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
        $this->labelProvider = $labelProvider;
    }

    /**
     * View quote details.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        try {
            $quote = $this->quoteRepository->get($quoteId, ['*']);
            $this->cart->removeAllFailed();
            $this->setNotifications($quote);
            $status = '';
            if ($quote) {
                $quoteExtensionAttributes = $quote->getExtensionAttributes();
            }

            if ($quoteExtensionAttributes && $quoteExtensionAttributes->getNegotiableQuote()) {
                $negotiableQuote = $quoteExtensionAttributes->getNegotiableQuote();
                $statusLabel = $this->labelProvider->getLabelByStatus($negotiableQuote->getStatus());
                $status = '('.$statusLabel.')';
            }
        } catch (NoSuchEntityException $e) {
            $this->addNotFoundError();
            return $this->redirectOnIndexPage();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('An error occurred on the server. %1', $e->getMessage()));
            return $this->redirectOnIndexPage();
        }
        $resultPage = $this->initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Quote #%1 %2', $quoteId,$status));
        return $resultPage;
    }

    /**
     * Redirect on index page
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectOnIndexPage()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }

    /**
     * Set notifications for merchant.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     */
    private function setNotifications(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $notifications = $this->messageProvider->getChangesMessages($quote);

        foreach ($notifications as $message) {
            if ($message) {
                $this->messageManager->addWarningMessage($message);
            }
        }

        if ($this->negotiableQuoteHelper->isLockMessageDisplayed()) {
            $this->messageManager->addWarningMessage(
                __('Quote is sent to partner. It will available once released by the partner')
            );
        }
    }
}
