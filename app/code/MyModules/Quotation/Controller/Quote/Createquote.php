<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Controller\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Controller\Quote;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Helper\Quote as QuoteHelper;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;

/**
 * Quotation As Quotation Login action
 */
class Createquote extends Quote implements HttpPostActionInterface
{
    /**
     * @var \Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * 
     * @param Context $context
     * @param QuoteHelper $quoteHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param RestrictionInterface $customerRestriction
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\NegotiableQuote\Controller\FileProcessor $fileProcessor
     */
    public function __construct(
        Context $context,
        QuoteHelper $quoteHelper,
        CartRepositoryInterface $quoteRepository,
        RestrictionInterface $customerRestriction,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\NegotiableQuote\Controller\FileProcessor $fileProcessor
    ) {     
        parent::__construct(
            $context,
            $quoteHelper,
            $quoteRepository,
            $customerRestriction,
            $negotiableQuoteManagement,
            $settingsProvider
        );
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->fileProcessor = $fileProcessor;        
    }
    
    /**
     * Login as customer action
     *
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {       
        $quoteName = $this->getRequest()->getParam('quote-name');
        $commentText = $this->getRequest()->getParam('quote-message');
        
        $quoteId = $this->getRequest()->getParam('quote_id');
       
        if ($quoteId) {
            try {
                $files = $this->fileProcessor->getFiles();
                $quote = $this->quoteRepository->get($quoteId, ['*']);
                $items = $quote->getAllItems();
                $this->removeAddresses($quote);
                $grandTotal =0;                
                foreach ($items as $item) {
                    $item = $quote->getItemById($item->getId());
                    if ($item->getParentItem()) {
                            $item = $item->getParentItem();
                        }
                    if (!$item) {
                      continue;
                    }
                    
                    $expectedType = $this->getRequest()->getParam('expected-type-'.$item->getId());
                    $expectedPrice = $this->getRequest()->getParam('expected-price-'.$item->getId());
                    if($expectedType == 'amount') {
                        if($expectedPrice && $expectedPrice < $item->getPrice() && $expectedPrice > 0 ) {                            
                            /*
                            $item->setCustomPrice($expectedPrice);
                            $item->setOriginalCustomPrice($expectedPrice);
                            $item->getProduct()->setIsSuperMode(true);
                            $item->calcRowTotal();
                            $item->checkData();
                            $item->save(); 
                            */
                            $params = array( 
                                'qty'     => $item->getQty(),
                                'custom_price'     => $expectedPrice,
                                'original_custom_price'     => $expectedPrice
                            );
                            $quote->updateItem($item->getId(),$params);

                        }
                    } else if($expectedType == 'percentage') {
                        if($expectedPrice && $expectedPrice < 100 && $expectedPrice > 0 ) {
                            $expectedPrice = $item->getPrice() - ($expectedPrice/100)*$item->getPrice();
                            /*
                            $item->setCustomPrice($expectedPrice);
                            $item->setOriginalCustomPrice($expectedPrice);
                            $item->getProduct()->setIsSuperMode(true);
                            $item->calcRowTotal();
                            $item->checkData();
                            $item->save(); 
                            */
                            $params = array(   
                                'qty'     => $item->getQty(),
                                'custom_price'     => $expectedPrice,
                                'original_custom_price'     => $expectedPrice
                            );
                            $quote->updateItem($item->getId(),$params);

                        }
                    }
                
                }  
//                $quote->setTotalsCollectedFlag(false)->collectTotals()->save();                
//                $this->quoteRepository->save($quote);
                
                $this->negotiableQuoteManagement->create($quoteId, $quoteName, $commentText, $files);                
                $this->checkoutSession->clearQuote(); 
                $url = $this->_url->getUrl('rquotation/quote/success/quote_id/'.$quoteId);                
                $this->_redirect($url);                            
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());                
                $this->_redirect('negotiable_quote/quote');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('The quote could not be created. Please try again later.')
                );
                $this->_redirect('negotiable_quote/quote');
            }
        }        
    }
    
    /**
     * Remove address from quote.
     *
     * @param CartInterface $quote
     * @return $this
     */
    private function removeAddresses(CartInterface $quote)
    {
        if ($quote->getBillingAddress()) {
            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->getBillingAddress();
        }
        if ($quote->getShippingAddress()) {
            $quote->removeAddress($quote->getShippingAddress()->getId());
            $quote->getShippingAddress();
        }
        if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getShippingAssignments()) {
            $quote->getExtensionAttributes()->setShippingAssignments(null);
        }
        return $this;
    }
}
