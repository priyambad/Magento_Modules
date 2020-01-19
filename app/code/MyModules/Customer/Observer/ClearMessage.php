<?php


namespace Redington\Customer\Observer;

class ClearMessage implements \Magento\Framework\Event\ObserverInterface
{

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
    ) {
        $this->_cookieManager = $cookieManager;
    }


    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $request = $observer->getEvent()->getRequest();
        $actionFullName = strtolower($request->getFullActionName());
       
        if($actionFullName!='catalog_product_view' && $actionFullName!='cms_index_index')
        {
             $this->_cookieManager->deleteCookie('mage-messages');
        }
    }
}
