<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */
 
namespace Redington\Catalog\Plugin\Model\Checkout;

use Magento\Framework\Exception\LocalizedException;

class Cart
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;
	
	/**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
	
	/**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;
	
	/**
	 * @var \Magento\Customer\Model\Session
	 */
	protected $customerSession;
	
    /**
     * Plugin constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Framework\Message\ManagerInterface $messageManager
	 * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
	 * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Magento\Customer\Model\Session $customerSession
    ) {
        $this->quote = $checkoutSession->getQuote();
		$this->_messageManager = $messageManager;
		$this->orderCollectionFactory = $orderCollectionFactory;
		$this->customerSession = $customerSession;
    }

    /**
     * beforeAddProduct
     *
     * @param      $subject
     * @param      $productInfo
     * @param null $requestInfo
     *
     * @return array
     * @throws LocalizedException
     */
    public function beforeAddProduct($subject, $productInfo, $requestInfo = null)
    {			
        if ($productInfo->getZPeriodicity()) {
			$customerData = $this->customerSession->getCustomer(); 
			$customerEmail = $customerData->getEmail();
			$time = time();
			$to = date('Y-m-d H:i:s', $time);//2019-02-27 13:44:24
			$lastTime = $time - (60*60*$productInfo->getZPeriodicity());//79200; // 60*60*22
			$from = date('Y-m-d H:i:s', $lastTime);
			$productId = $productInfo->getId();
			//echo "Email: ".$customerEmail; echo "Product Id:".$productId; echo " Periodicity:".$productInfo->getZPeriodicity(); echo " To:".$to; echo " From:".$from;
			/** fetch order collection for this logged in user for current product **/
			$orderCollection = $this->orderCollectionFactory->create()
									->addFieldToSelect('entity_id')
									->addFieldToSelect('created_at')
									->addFieldToFilter('status',array('neq' => 'failed'))
									->addAttributeToFilter('customer_email', ['eq'=>$customerEmail]);
			$orderCollection->addAttributeToFilter('main_table.created_at', array('date' => true, 'from' => $from, 'to' => $to));			
			$orderCollection->getSelect()->join(
					['sales_order_item' => 'sales_order_item'],
					'main_table.entity_id = sales_order_item.order_id',
					[]
				)->where('sales_order_item.product_id = ?', $productId);			
			//echo $orderCollection->getSelect();
			//echo "<pre>";    print_r($orderCollection->getData());		
			if($orderCollection->getSize()) {
				
				$this->_messageManager->addError(__("You can't add this product to cart as you have purchased ".$remainingTime." before"));		
				$requestInfo['qty'] = 0; 
			}            
        }

        return [$productInfo, $requestInfo];
    }
}