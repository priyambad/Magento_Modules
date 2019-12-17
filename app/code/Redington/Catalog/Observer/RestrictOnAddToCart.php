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

class RestrictOnAddToCart implements ObserverInterface {

    
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
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Redington\Company\Helper\Data
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
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Quotation\Helper\Reservation $reservationHelper
    ) {

        $this->redirect = $redirect;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->product = $product;
        $this->customerSession = $session;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helperData = $helperData;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->reservationHelper = $reservationHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
         $adminId = $this->companyHelper->getCompanyAdminId();
         
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        
       
        $overDueAmt = $this->customerSession->getOverdueAmount();
        $overDueAmount = round($overDueAmt,2);
        $isOverdue = $adminUser->getCustomAttribute('is_overdue');
        $isOverdueValue = $isOverdue ? $isOverdue->getValue() : 0;
        if($overDueAmount > 0 && $isOverdueValue == 0)
        {
            
            $this->messageManager->addError(__("Your overdue amount is ".$overDueAmount." you can't add product"));
             throw new \Magento\Framework\Exception\LocalizedException(
                __("Your overdue amount is ".$overDueAmount." you can't add product")
            );
            
        }
    
        $tradeLicenseValidity = $this->getTradeLicenseValidity();
        if(!$tradeLicenseValidity){
            $this->messageManager->addError(__("Your trade license has expired, you can't add products. Please update trade license certificate."));
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Your trade license has expired, you can't add products. Please update trade license certificate.")
            );
        }
        $product = $observer->getEvent()->getProduct();
        $productQtyInPlant = $this->getProductQtyInPlant($product->getSku());
        $productQtyInPlant = $productQtyInPlant ? $productQtyInPlant : 0 ;
        $addedQty = $product->getQty();

//      reserved qty of the product
        $reservedQty = $this->reservationHelper->getReservedQty($product->getSku());
      
        if($addedQty > ($productQtyInPlant - $reservedQty)){
//            $this->messageManager->addNotice(__('The requested qty is not available (available qty - %1).', $productQtyInPlant - $reservedQty));
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The requested qty is not available (available qty - %1).', $productQtyInPlant - $reservedQty)
            );
        }
        $moduleEnabled = $this->helperData->isModuleEnabled(self::SCOPE_DEFAULT, '');
        $periodicityEnabled = $this->helperData->isPeriodicityEnabled(self::SCOPE_DEFAULT, '');
        if($moduleEnabled && $periodicityEnabled) {
            $quoteItem = $observer->getEvent()->getQuoteItem();
            $productInfo = $observer->getEvent()->getProduct();
            $productId = $productInfo->getId();
            
            $filePath = '/var/log/Redington_Catalog.log';
            
            $companyId = $this->helperData->getCurrentCompanyId();
            $companyPeriodicityValue = $this->helperData->getCompanyPeriodicity($companyId);
            $itemPeriodicityValue = $productInfo->getZPeriodicity() ? $productInfo->getZPeriodicity() : 0;
            $lastPeriod = 0;        
            $lastPeriod = $companyPeriodicityValue ? $companyPeriodicityValue : $itemPeriodicityValue;
            $this->logMessage(PHP_EOL, $filePath);
            $this->logMessage('Periodicity : In Add to Cart'.PHP_EOL, $filePath);
            $this->logMessage('Company Id - '.$companyId.' Company Periodicity Value - '.$companyPeriodicityValue.' Item Periodicity Value - '.$itemPeriodicityValue.' lastPeriod - '.$lastPeriod, $filePath);
            if ($productId && $lastPeriod) {
                $customerData = $this->customerSession->getCustomer();
                if($customerData->getId()) {
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
                    
                    $this->logMessage('Select query '.$orderCollection->getSelect(), $filePath);
                    
                    if ($orderCollection->getSize()) {
						/** get last order time**/
                        $data = $orderCollection->getFirstItem();
                        $lastOrderTime = $data->getCreatedAt();
                        $remainingTime = $this->helperData->getRemainingTime($lastOrderTime, $from);
                        $this->logMessage('Remaining Time '.$remainingTime, $filePath);
						
                        $this->logMessage('Collection Count '.$orderCollection->getSize(), $filePath);
                        $this->messageManager->addError(__('You cannot add "%1" to the cart for time '.$remainingTime.' as you have purchased before.', $productInfo->getName()));
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('You cannot add "%1" to the cart for time '.$remainingTime.' as you have purchased before.', $productInfo->getName())
                        );                        
                    } 
                }                
            }
        }
        
        return $this;
    }
    public function getProductQtyInPlant($productSku){
        $plantCode = $this->getPlantCode();
        $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku',$productSku)->addFieldToFilter('source_code',$plantCode);
        if(sizeof($sourceCollection) > 0){
            $sourceData = $sourceCollection->getFirstItem();
            $qtyInPlant = $sourceData->getQuantity();
            return $qtyInPlant;
        }else{
            return false;
        }
    }
    public function getPlantCode(){
        $salesOrg = $this->getSalesOrg();
        $distribution = $this->getDistributionChannel();
        try{
            $sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled',1)
                ->addFieldToFilter('distribution',$distribution)
                ->addFieldToFilter('sap_account_code',$salesOrg);
            return $sourceCollection->getFirstItem()->getPlantCode();
        }catch(\Exception $e){
            return null;
        }
    }
    public function getSalesOrg() {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_code')->getValue();
        return $sapAccountNumber;
    }
    public function getDistributionChannel(){
		$storeCode = $this->_storeManager->getGroup()->getCode();
		$distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code',$storeCode)->getFirstItem();
        return $distribution->getDistributionChannel();
	}
    public function getTradeLicenseValidity(){
        try{
            $filePath = '/var/log/Redington_TradeLicenseValidityCheck.log';
            $comapnyAdminId = $this->companyHelper->getCompanyAdminId();
            $this->logMessage('company Admin Id is '.$comapnyAdminId, $filePath);
            $companyAdmin = $this->customerRepositoryInterface->getById($comapnyAdminId);
            $validity = $companyAdmin->getCustomAttribute('z_trade_license_valid')->getValue();
            if($validity == 1){
                $this->logMessage('trade license is valid', $filePath);
                return true;
            }else{
                $this->logMessage('trade license is invalid', $filePath);
                return false;
            }
        }catch(\Exception $e){
            $this->logMessage($e->getMessage());
            return false;
        }
    }
    private function logMessage($message, $filePath = '/var/log/system.log') {        
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}
