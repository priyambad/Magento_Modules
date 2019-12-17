<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\Quotation\Controller\Quote;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

class QuoteUpdateItem extends \Magento\Framework\App\Action\Action {

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
		CartRepositoryInterface $quoteRepository,
		\Magento\Quote\Model\Quote\ItemFactory $itemFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory)
        {
        	$this->checkoutSession = $checkoutSession;
            $this->modelCart = $modelCart;
			$this->quoteRepository = $quoteRepository;
			$this->itemFactory = $itemFactory;
            $this->resultJsonFactory = $jsonFactory;
			$this->productFactory = $productFactory;
			$this->messageManager = $messageManager;
            parent::__construct($context);
        }
    /**
     * Execute the remove cart data
     *
     * @return void
     */    
	public function execute()
    {
		  $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_Emptycart.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			
        $resultJson = $this->resultJsonFactory->create();
        $cart = $this->modelCart;
		$data = $_POST;
		$quote = $this->quoteRepository->get($_POST['quote_id'], ['*']);
        try {
            foreach($data['item-id'] as $itemid){
				
				$qty = $data['cart'][$itemid]['qty'];
				$proposedPrice = $data['expected-price-'.$itemid];
				$model = $this->itemFactory->create()->load($itemid);
				$product = $this->productFactory->create()->load($model->getProductId());
				$productprice = $product->getPrice();
				$currencyRate = $quote->getBaseToQuoteRate();
				$price = $productprice * $currencyRate;
				$logger->info('Price'.$price);
				$logger->info('Proposed Price'.$proposedPrice);
				if($proposedPrice <= $price && $proposedPrice > 0 ) {
					$logger->info('in if condition'.$proposedPrice);
				$params = array(   
                                'qty'     => $qty,
                                'proposed_price' => $proposedPrice,
								'custom_price' => $proposedPrice,
                                'row_total' => $proposedPrice * $qty
                );
					$quote->updateItem($itemid,$params);
				}
				
			}
		
			$quote->setTotalsCollectedFlag(false)->collectTotals()->save();                
            $this->quoteRepository->save($quote);
            $response = ['success' => true];
			$response['message']=$this->messageManager->addSuccess('Quotation Updated Successfully.');;
			return $resultJson->setData($response);	
        }catch(\Exception $e){
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_Emptycart.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info($e->getMessage());
        }   
	}
}
