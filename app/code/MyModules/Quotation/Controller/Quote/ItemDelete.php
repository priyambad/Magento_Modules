<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Controller\Quote;

use Magento\NegotiableQuote\Controller\Quote;
use Magento\Framework\App\Action\Context;
use Magento\NegotiableQuote\Helper\Quote as QuoteHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;

/**
 * Class ItemDelete
 */
class ItemDelete extends Quote
{
    /**
     * Authorization level of a company session.
     */
    const NEGOTIABLE_QUOTE_RESOURCE = 'Magento_NegotiableQuote::manage';

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * Construct
     *
     * @param Context $context
     * @param QuoteHelper $quoteHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param RestrictionInterface $customerRestriction
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        QuoteHelper $quoteHelper,
        CartRepositoryInterface $quoteRepository,
        RestrictionInterface $customerRestriction,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
		\Magento\Quote\Model\Quote\ItemFactory $itemFactory
    ) {
        parent::__construct(
            $context,
            $quoteHelper,
            $quoteRepository,
            $customerRestriction,
            $negotiableQuoteManagement,
            $settingsProvider
        );
        $this->formKeyValidator = $formKeyValidator;
		$this->itemFactory = $itemFactory;
    }

    /**
     * Delete item from negotiable quote
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');
        $quoteItemId = (int)$this->getRequest()->getParam('quote_item_id');

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index');
//        if (! $this->formKeyValidator->validate($this->getRequest())) {
//            return $resultRedirect;
//        }

        if ($quoteId && $quoteItemId) {
            try {
                $quote = $this->quoteRepository->get($quoteId);
                if ($quote->getCustomerId() === $this->settingsProvider->getCurrentUserId()) {
                    //$quote->removeItem($quoteItemId);
					$itemModel=$this->itemFactory->create()->load($quoteItemId);
					$itemModel->delete();
                    $quote->getItemsCollection()->removeItemByKey($quoteItemId);
                    $quote->collectTotals();
                    $quote->setTotalsCollectedFlag(false);
                    $quote->save();
                    $this->negotiableQuoteManagement->removeQuoteItem($quoteId, $quoteItemId);
                    $this->messageManager->addSuccess(__('You\'ve removed the item from quote.'));
					
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                //$this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t delete the quote item right now.'));
            }
        }
        return $resultRedirect;
    }
}
