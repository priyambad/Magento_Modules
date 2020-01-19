<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */

namespace Redington\Catalog\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    /**
     * default 
     */
    const SCOPE_DEFAULT = 'default';
    
    /**
     * Catalog Module Enable configuration
     */
    const CATALOG_MODULE_ENABLED = 'redington_catalog/general/enabled';
    
    /**
     * Catalog Module Enable configuration
     */
    const CATALOG_MODULE_PERIODICITY_ENABLED = 'redington_catalog/general/enabled_periodicity';
    
    
    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     *
     * @var \Magento\Customer\Model\CustomerFactory $customerFactory,
     */
    protected $customerFactory ;
    
    /**
     * @var \Magento\Company\Model\CompanyUser
     */
    private $companyUser;
    
    /**
     * @var \Magento\Company\Model\ResourceModel\Customer
     */
    private $companyCustomer;
    
    /**
     * @var \Magento\Catalog\Model\ProductFactory 
     * 
     */
    private $productFactory;
    
    /**
     * @var \Magento\Company\Model\Company
     * 
     */
    private $companyModel;
    
    /**
     * 
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Company\Model\CompanyUser $companyUser
     * @param \Magento\Company\Model\ResourceModel\Customer $companyCustomer
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(        
        \Magento\Customer\Model\SessionFactory  $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Company\Model\ResourceModel\Customer $companyCustomer,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Company\Model\Company $companyModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\ProductAlert\Model\StockFactory $stockFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrencyInterface
    ) {
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;    
        $this->companyUser = $companyUser;
        $this->companyCustomer = $companyCustomer;
        $this->customerFactory = $customerFactory;
        $this->productFactory = $productFactory;
        $this->companyModel = $companyModel;
        $this->customerRepository = $customerRepository;
        $this->stockFactory = $stockFactory;
        $this->currencyFactory = $currencyFactory;
        $this->priceCurrencyInterface = $priceCurrencyInterface;
    }
    
    /**
     * Get scope config value by path, scope type and code
     * 
     * @param type $path
     * @param type $scopeType
     * @param type $scopeCode
     * return string | int 
     */
    public function getConfig($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null) {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }
    
    /**
     * 
     * @param string $scopeType
     * @param string | null $scopeCode
     * @return string | null 
     */
    public function isModuleEnabled($scopeType, $scopeCode) {
        return $this->getConfig(self::CATALOG_MODULE_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * 
     * @param string $scopeType
     * @param string | null $scopeCode
     * @return string | null 
     */
    public function isPeriodicityEnabled($scopeType, $scopeCode) {
        return $this->getConfig(self::CATALOG_MODULE_PERIODICITY_ENABLED, $scopeType, $scopeCode);
    }
    
    /**
     * 
     * @param string $storeCode
     * @return string
     */
    public function getStoreByStoreCode($storeCode) {
        $store = $this->storeManager->getStore($storeCode);
        return $store;
    }

    /**
     * Get Website Id by Code
     * 
     * @param string
     * return int
     */
    public function getWebsiteIdByStoreCode($storeCode) {
        $store = $this->getStoreByStoreCode($storeCode);
        $websiteId = $store->getWebsiteId();
        return $websiteId;
    }

    /**
     * Get Store Id by Code
     * 
     * @param string
     * return int
     */
    public function getStoreIdByStoreCode($storeCode) {
        $store = $this->getStoreByStoreCode($storeCode);
        $storeId = $store->getStoreId();
        return $storeId;
    }  
    
    /**
     * 
     * @param int $companyId
     * @return array
     */
    public function getCompanyUsersEmail($companyId){         
       $userIds = $this->companyCustomer->getCustomerIdsByCompanyId($companyId);
       return $this->getFilteredCustomerCollectionByIds($userIds);       
    }
    
    /**
     * 
     * @return int
     */
    public function getCurrentCompanyId() {
        $companyId = 0;        
        $companyId = $this->companyUser->getCurrentCompanyId();  
        return $companyId;
    }
    
    /**
     * 
     * @param array $ids
     * @return array
     */
    public function getFilteredCustomerCollectionByIds($ids) {
        $custCollection = $this->customerFactory->create()->getCollection()
                ->addFieldToSelect(array('email'))
                ->addFieldToFilter('entity_id', $ids)
                ->load();
        $custCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['email']);
        $emailArray = [];
        foreach ($custCollection as $cust) {
            $emailArray[] = $cust['email'];
        }
        return $emailArray;
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public function getProductInfo($id) {   
        return $this->productFactory->create()->load($id);        
    }
    
    public function getCustomerData($fields) { 
        $customerData = $this->customerSession->create()->getCustomer();        
        if ($customerData->getId()) {
            if(!empty($fields)) {
                return $customerData->getData($fields);
            }
            return $customerData->getData();
        }
    }
    
    /**
     * get Company Admin Id By Company Id
     * 
     * @param int $companyId
     * 
     * @return string
     */
    public function getCompanyAdminIdByCompanyId($companyId) {
        $adminId = '';
        if ($companyId) {
            $companyData = $this->companyModel->load($companyId);
            if ($companyData) {
                $adminId = $companyData->getSuperUserId();
            } else {
                $customerData = $this->customerSession->create()->getCustomer(); 
                $adminId = $customerData->getId();
            }
        }
        return $adminId;
    }
    /**
     * get customer attribute value
     * 
     * @param int $customerId
     * @param string $attributeCode
     * 
     * @return string
     */
    public function getCustomerAttributeValue($customerId, $attributeCode)
    {        
        $customerRepository = $this->customerRepository->getById($customerId);
        $attributeValue = $customerRepository->getCustomAttribute($attributeCode);
        if($attributeValue){
           $attributeValue = $attributeValue->getValue();
        }else{
            $attributeValue = 0;
        }
        return $attributeValue;
    }
    
    /**
     * get Company Periodicity by company id
     * 
     * @param int $companyId
     * 
     * @return string
     */
    public  function getCompanyPeriodicity($companyId) {
        $companyAdminId = $this->getCompanyAdminIdByCompanyId($companyId);
        $companyPeriodicityValue = 0;
        $attributeCode = 'z_purchase_period';
        if($companyAdminId) {
            $companyPeriodicityValue = $this->getCustomerAttributeValue($companyAdminId, $attributeCode);
            $companyPeriodicityValue = $companyPeriodicityValue ? $companyPeriodicityValue : 0;
        }
        return $companyPeriodicityValue;
    }
    
    /**
      * get to time
      *
      * @return string
      */
    public  function getToTime() {
        $toTime = date('Y-m-d H:i:s', time()); 
        return $toTime;
    }
    
    /**
      * get from time
      *
      * @return string
      */
    public  function getFromTime($lastPeriod) {
        $lastTime = time() - (60 * 60 * $lastPeriod);
        $fromTime = date('Y-m-d H:i:s', $lastTime);
        return $fromTime;
    }
    
    /**
      * $to last order time for product
      * $from periodicity time
      * @return string
      */
    public function getRemainingTime($to, $from) {
        $seconds = strtotime($to) - strtotime($from);

        $days    = floor($seconds / 86400);
        $hours   = floor(($seconds - ($days * 86400)) / 3600);
		if($hours < 10){
			$hours = '0'.$hours;
		}
        $minutes = floor(($seconds - ($days * 86400) - ($hours * 3600))/60);
		if($minutes < 10){
			$minutes = '0'.$minutes;
		}
		
        $seconds = floor(($seconds - ($days * 86400) - ($hours * 3600) - ($minutes*60)));
		if($seconds < 10){
			$seconds = '0'.$seconds;
		}
		$time = $hours.':'.$minutes.':'.$seconds;
		return $time;
    }

     /**
       * @param int $productId
       * @param float $price
       * @return string
       */
    public function getStockAlertPrice($productId,$price)
    {
        $model = $this->stockFactory->create()->getCollection()->addFieldToFilter('product_id',$productId);
        $stockalertdata = $model->getData();
        $rate = '';
        $currencySymbol = '';
        foreach ($stockalertdata as $key => $value) {
            $store = $this->storeManager->getStore($value['store_id']);
            $currencyCode = $store->getCurrentCurrencyCode();
            $currentcurrency = trim($currencyCode);
            $currency = $this->currencyFactory->create()->load($currentcurrency);
            $currencySymbol = $currency->getCurrencySymbol(); 
            $rate = $this->priceCurrencyInterface->convert($price, $store, $currencySymbol); 
           
          }
          $this->logMessage($currencySymbol." ".$rate);
       return $currencySymbol." ".round($rate,2);
    }



    public function logMessage($message, $filePath = '/var/log/Redington_Product_Price_Alert.log') {        
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}
