<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */
namespace Redington\Checkout\Controller\Documents;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;

class Index extends \Magento\Framework\App\Action\Action {

    /**
     * Cart Model
     *
     * @var Cart 
     */
    protected  $modelCart;
    /**
     * Checkout Session
     *
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * Constructor for clear cart
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param CheckoutSession $checkoutSession
     * @param Cart $modelCart
     * @param  \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     */
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        Cart $modelCart, 
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory)
        {
        	$this->checkoutSession = $checkoutSession;
            $this->modelCart = $modelCart;
            $this->resultJsonFactory = $jsonFactory;
            parent::__construct($context);
        }
    /**
     * Execute the remove cart data
     *
     * @return void
     */    
	public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $cart = $this->modelCart;
        try {
            $quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();
			$cart->truncate();
			$cart->saveQuote();
            $response = ['success' => true];
			return $resultJson->setData($response);	
        }catch(\Exception $e){
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_Emptycart.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info($e->getMessage());
        }   
	}
}