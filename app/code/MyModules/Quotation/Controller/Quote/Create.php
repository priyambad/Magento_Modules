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
use Redington\Quotation\Helper\Data;
/**
 * Quotation As Quotation Login action
 */
class Create extends Quote implements HttpPostActionInterface
{
    /**
     * @var \Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
	
	protected $helper;
    
    /**
     * 
     * @param Context $context
     * @param QuoteHelper $quoteHelper
	 * @param Data $customHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param RestrictionInterface $customerRestriction
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\NegotiableQuote\Controller\FileProcessor $fileProcessor
	 * @param \Magento\NegotiableQuote\Model\NegotiableQuoteFactory $negotiableQuoteFactory
     */
    public function __construct(
        Context $context,
        QuoteHelper $quoteHelper,
		Data $customHelper,
        CartRepositoryInterface $quoteRepository,
        RestrictionInterface $customerRestriction,
	    NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\App\RequestInterface $request,
        \Magento\NegotiableQuote\Controller\FileProcessor $fileProcessor,
		\Magento\NegotiableQuote\Model\NegotiableQuoteFactory $negotiableQuoteFactory,
        \Redington\Quotation\Helper\Reservation $reservationHelper,
        \Magento\Customer\Model\Session $customerSession,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		 \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {     
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
		$this->fileProcessor = $fileProcessor; 
		$this->negotiableQuoteFactory = $negotiableQuoteFactory;
		$this->helper = $customHelper;
        $this->reservationHelper = $reservationHelper;
        $this->customerSession = $customerSession;
		$this->scopeConfig = $scopeConfig;
		$this->_transportBuilder = $transportBuilder;
		$this->storeManager = $storeManagerInterface;
		$this->inlineTranslation = $inlineTranslation;
		parent::__construct($context,$quoteHelper,$quoteRepository,$customerRestriction,$negotiableQuoteManagement,$settingsProvider);
	}
    
    /**
     * Login as customer action
     *
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {  
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_ItemUpdate.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
               
              
		$this->customerSession->setIsNegotiableQuoteCreated(true);
        $quoteName = $this->request->getParam('quote-name');
        $commentText = $this->request->getParam('quote-message');
        $quoteId = $this->request->getParam('quote_id');
		$currencyRate = $this->helper->getCurrencyRate();
		
		
		
        if ($quoteId) {
            try {
				$numberRange = $this->scopeConfig->getValue('redington_quotation/general/numberrange',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				 
                $files = $this->fileProcessor->getFiles();
                $quotationId= $numberRange + $quoteId;
				$quote = $this->quoteRepository->get($quoteId, ['*']);
				$quote->setIsQuotation(1);
				$quote->setQuotationId($quotationId);
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
                    $expectedPrice = 0.00;
                    $expectedType = $this->getRequest()->getParam('expected-type-'.$item->getId());
                    $expectedPrice = $this->getRequest()->getParam('expected-price-'.$item->getId());
					$productprice = $item->getPrice();
					if($expectedType == 'amount') {
						$price = $item->getPrice() * $currencyRate;
						$expectedPrice = round($expectedPrice,2);
                        $price = round($price,2);
						if($expectedPrice <= $price && $expectedPrice > 0 ) {                            
                            $item->setProposedPrice($expectedPrice);
							//$item->setCustomPrice($expectedPrice);
                            $params = array( 
                                'qty'     => $item->getQty(),
                                'custom_price'     => $expectedPrice,
                                'original_custom_price'     => $expectedPrice,
								'base_row_total' => $expectedPrice * $item->getQty()
                            );
                            $quote->updateItem($item->getId(),$params);
                             $logger->info('-----------------------------');
							 $logger->info('expectedPrice'.$expectedPrice);
							//$item->setCustomPrice($expectedPrice);
							//$item->setOriginalCustomPrice($expectedPrice);
							//$item->setPrice($item->getPrice());
							//$item->setBasePrice($productprice);
							//$item->getProduct()->setIsSuperMode(true);
							$item->save();
							 
                        }
                    } 
					if($expectedType == 'percentage') {
						if($expectedPrice < 100 && $expectedPrice > 0 ) {
							$price = $item->getPrice() * $currencyRate;
                            $expectedPrice =  $price - ($expectedPrice/100)* $price;
                         
                            $params = array(   
                                'qty'     => $item->getQty(),
                                'custom_price'     => $expectedPrice,
                                'original_custom_price'     => $expectedPrice,
								'base_row_total' => $expectedPrice * $item->getQty()
                            );
                            $quote->updateItem($item->getId(),$params);
							
							/*$item->setCustomPrice($expectedPrice);
							$item->setOriginalCustomPrice($expectedPrice);
							$item->setPrice($productprice);
							$item->getProduct()->setIsSuperMode(true);
							$item->save();*/
                        }
                    }
                
                }  
				
				
                $quote->setTotalsCollectedFlag(false)->collectTotals()->save();                
                $this->quoteRepository->save($quote);
                $this->reservationHelper->reserveStock($quoteId, false, true);
				
				
                $this->negotiableQuoteManagement->create($quoteId, $quoteName, $commentText, $files);  
				$negotiableModel = $this->negotiableQuoteFactory->create()->load($quoteId);	
				$negotiableModel->setQuotationId($quotationId);		
				$negotiableModel->save();
				 
				$this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
				$this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES); 
				$templateVars = array(
                            'QuoteId' => $this->_url->getUrl('negotiable_quote/quote/view/quote_id/'.$quoteId),
							'quotationId'=> $quotationId,
							'companyAdmin'=> $quote->getCustomerFirstname().' '.$quote->getCustomerLastname(),
                        );
				$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
				$from = array('email' => $this->senderEmail, 'name' => $this->senderName);
				$to = $quote->getCustomerEmail();
				$this->scopeConfig->getValue('redington_quotation/general/send_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
				$transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_quotation/general/send_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
				$transport->sendMessage();
				$this->inlineTranslation->resume();	
                $this->checkoutSession->clearQuote(); 
                $this->customerSession->setIsNegotiableQuoteCreated(false);
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
