<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */

namespace Redington\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Http\Context as customerSession;

class RestrictOnOrderSave implements ObserverInterface {
    
    /**
     * default 
     */
    const SCOPE_DEFAULT = 'default';
    
    protected $cart;
    protected $messageManager;
    protected $redirect;
    protected $request;
    protected $product;
    protected $customerSession;

    /**
     *
     * @var \Redington\Catalog\Helper\Data
     */
    private $helperData;
    
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;
    
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlInterface;
    
    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    private $responseFactory;
    
    /**
     * 
     * @param RedirectInterface $redirect
     * @param Cart $cart
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param Product $product
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Redington\Catalog\Helper\Data $helperData
     * $param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     */
    public function __construct(
        RedirectInterface $redirect, 
        Cart $cart, 
        ManagerInterface $messageManager, 
        RequestInterface $request, 
        Product $product, 
        \Magento\Customer\Model\Session $session, 
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Redington\Catalog\Helper\Data $helperData,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\ResponseFactory $responseFactory
    ) {

        $this->redirect = $redirect;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->product = $product;
        $this->customerSession = $session;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helperData = $helperData;
        $this->urlInterface = $urlInterface;
        $this->responseFactory = $responseFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $moduleEnabled = $this->helperData->isModuleEnabled(self::SCOPE_DEFAULT, '');
        $periodicityEnabled = $this->helperData->isPeriodicityEnabled(self::SCOPE_DEFAULT, '');
        if($moduleEnabled && $periodicityEnabled) {
            $cartItems = $this->cart->getQuote()->getAllVisibleItems();        
            foreach ($cartItems as $cartItem) {
               $productId = $cartItem->getProductId();
                $productInfo = $this->helperData->getProductInfo($productId); 
                $filePath = '/var/log/Redington_Catalog.log';
            
                $companyId = $this->helperData->getCurrentCompanyId();
                $companyPeriodicityValue = $this->helperData->getCompanyPeriodicity($companyId);
                $itemPeriodicityValue = $productInfo->getZPeriodicity() ? $productInfo->getZPeriodicity() : 0;
                $lastPeriod = 0;        
                $lastPeriod = $companyPeriodicityValue ? $companyPeriodicityValue : $itemPeriodicityValue;
                $this->helperData->logMessage(PHP_EOL, $filePath);
                $this->helperData->logMessage('Periodicity : In Add to Cart'.PHP_EOL, $filePath);
                $this->helperData->logMessage('Company Id - '.$companyId.' Company Periodicity Value - '.$companyPeriodicityValue.' Item Periodicity Value - '.$itemPeriodicityValue.' lastPeriod - '.$lastPeriod, $filePath);
                if ($productId && $lastPeriod) {
                    $customerData = $this->customerSession->getCustomer();
                    if($productId) {
                        $to = $this->helperData->getToTime(); 
                        $from = $this->helperData->getFromTime($lastPeriod);  
                        /* Fetch all company users email ids */
                        $companyUsersEmailArray = $this->helperData->getCompanyUsersEmail($companyId); 
                        /** fetch order collection for this logged in user for current product * */
                        $orderCollection = $this->orderCollectionFactory->create()
												->addFieldToSelect('entity_id')
                                                ->addFieldToSelect('created_at')
                                                ->addFieldToFilter('status',array('neq' => 'failed'))
												->addAttributeToFilter('customer_email', ['in' => $companyUsersEmailArray]);
                        $orderCollection->addAttributeToFilter('main_table.created_at', array('date' => true, 'from' => $from, 'to' => $to));
                        $orderCollection->getSelect()->join(
                                ['sales_order_item' => 'sales_order_item'], 'main_table.entity_id = sales_order_item.order_id', []
                        )->where('sales_order_item.product_id = ?', $productId);
                        $this->helperData->logMessage('Select query '.$orderCollection->getSelect(), $filePath);
                    		
                        if ($orderCollection->getSize()) {
							
							/** get last order time**/
                            $data = $orderCollection->getFirstItem();
                            $lastOrderTime = $data->getCreatedAt();
                            $remainingTime = $this->helperData->getRemainingTime($lastOrderTime, $from);

                            $this->helperData->logMessage('Remaining Time '.$remainingTime, $filePath);
							
                            $this->helperData->logMessage('Collection Count '.$orderCollection->getSize(), $filePath);
                            $cartItem->isDeleted(true);
                            $this->cart->removeItem($cartItem->getId());

                            $RedirectUrl= $this->urlInterface->getUrl('checkout/cart/index');
                            $this->responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
                            $this->messageManager->addNotice(__('You cannot add "%1" to the cart for time '.$remainingTime.' as you have purchased before.', $productInfo->getName()));

                            throw new \Magento\Framework\Exception\LocalizedException(
                            __('You cannot add "%1" to the cart for some time as you have purchased before. ', $productInfo->getName())
                            );                        
                        } 
                    }                
                }
            }
        }
        return $this;
    }
}
