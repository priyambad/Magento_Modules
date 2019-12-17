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

class RestrictOnUpdateCart implements ObserverInterface {

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
     * 
     * @param RedirectInterface $redirect
     * @param Cart $cart
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param Product $product
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Redington\Catalog\Helper\Data $helperData
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
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory

    ) {

        $this->redirect = $redirect;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->product = $product;
        $this->customerSession = $session;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helperData = $helperData;
        $this->url = $url;
        $this->responseFactory = $responseFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) { 
         //$overDueAmount = $this->customerSession->getOverdueAmount();
        $adminId = $this->companyHelper->getCompanyAdminId();

        $adminUser = $this->customerRepositoryInterface->getById($adminId);
       
        $overDueAmt = $this->customerSession->getOverdueAmount();
        $overDueAmount = round($overDueAmt,2);
        
        $isOverdue = $adminUser->getCustomAttribute('is_overdue');
        $isOverdueValue = $isOverdue ? $isOverdue->getValue() : 0;
        if($overDueAmount > 0 && $isOverdueValue == 0)
        {
/*
            $this->messageManager->addError(__("You overdue amount is ".$overDueAmount." you can't add product"));
             throw new \Magento\Framework\Exception\LocalizedException(
                __("You overdue amount is ".$overDueAmount." you can't add product")
            );*/
           
              $redirectionUrl = $this->url->getUrl() . 'checkout/cart/';
                            $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
              $this->messageManager->addError(__("Your overdue amount is ".$overDueAmount." you can't update quanity"));
              throw new \Magento\Framework\Exception\FrameworkException(
                __("Your overdue amount is ".$overDueAmount." you can't upadate quantity")
                );

        }
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
                $this->helperData->logMessage('Periodicity : In Update Cart 3'.PHP_EOL, $filePath);
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
                            
                            $this->helperData->logMessage('Collection Count 2 '.$orderCollection->getSize(), $filePath);
                            $cartItem->isDeleted(true);
                            $this->helperData->logMessage('Collection Count 4 '.$orderCollection->getSize(), $filePath);
                            $this->cart->removeItem($productInfo->getId());
                            $this->helperData->logMessage('Collection Count 5 '.$orderCollection->getSize(), $filePath);
                            $this->messageManager->addError(__('You cannot add "%1" to the cart for time '.$remainingTime.' as you have purchased before.', $productInfo->getName()));
                            $this->helperData->logMessage('Collection Count 6'.$orderCollection->getSize(), $filePath);
                            
                            $redirectionUrl = $this->url->getUrl() . 'checkout/cart/';
                            $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                            
                              throw new \Magento\Framework\Exception\FrameworkException(
                                __('You cannot add "%1" to the cart for some time as you have purchased before.', $productInfo->getName())
                                );
                        } 
                    }                
                }
            }
            }
        return $this;
    }

}
