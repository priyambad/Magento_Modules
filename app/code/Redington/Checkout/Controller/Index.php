<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */
namespace Redington\Checkout\Controller\Index;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
class Clearcart extends Action
{
    /**
     * Cart Model
     *
     * @var Cart 
     */
    protected  $_modelCart;
    /**
     * Checkout Session
     *
     * @var CheckoutSession
     */
    protected $_checkoutSession;
    /**
     * Constructor for clear cart
     *
     * @param CheckoutSession $checkoutSession
     * @param Cart $modelCart
     */
	public function __construct(CheckoutSession $checkoutSession,Cart $modelCart)
        {
        	$this->checkoutSession = $checkoutSession;
        	$this->_modelCart = $modelCart;
        }
    /**
     * Execute the remove cart data
     *
     * @return void
     */    
	public function execute()
        {
		$cart = $this->_modelCart;
		$quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();
		foreach($quoteItems as $item)
		{
			$cart->removeItem($item->getId())->save(); 
		}
	}
}