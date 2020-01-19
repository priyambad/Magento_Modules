<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */
namespace Redington\Quotation\Controller\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * Redington quote success controller class
 */
class Success extends \Magento\Checkout\Controller\Onepage implements HttpGetActionInterface
{
    /**
     * Quote success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {        
        $quoteId = $this->getRequest()->getParam('quote_id') ? $this->getRequest()->getParam('quote_id') : null;
        
        //@todo: Refactor it to match CQRS
        $resultPage = $this->resultPageFactory->create();  
        $resultPage->getConfig()->getTitle()->set(__('Quotation'));
        return $resultPage;
    }
}
