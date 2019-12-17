<?php


namespace Redington\SapIntegration\Controller\Cart;



class Index extends \Magento\Checkout\Controller\Cart\Index
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
	
	protected $cart;

    /**
     * Shopping cart display action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
		
		$quote = $this->cart->getQuote();
        $availableItems = $quote->getAllVisibleItems();
		
		if(count($availableItems) < 1){
			$this->cart->truncate();
			$this->cart->saveQuote();
			
		}
		
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Shopping Cart'));
        return $resultPage;
    }
}
