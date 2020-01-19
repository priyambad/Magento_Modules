<?php
/** 
 * Copyright � Redington, Inc. All rights reserved.
 *
 */

namespace Redington\Configuration\Helper;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\ServiceException;

/**
 * Class Data Helper
 *
 */
 
class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
            \Redington\Company\Helper\Data $companyHelper,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
            \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
            \Magento\Framework\App\ResourceConnection $resource
            ) {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->companyHelper = $companyHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->_resource = $resource;
        parent::__construct($context);
    }
    /**
     * Get Connection string
     *
     * 
     */
    public function saveBlobStorage($content,$fileName,$type,$path) {
        
        $blobEndPoint = $this->scopeConfig->getValue('redington_documents/general/blob_endpoint');
        $accountName = $this->scopeConfig->getValue('redington_documents/general/blob_account_name');
        $key = $this->scopeConfig->getValue('redington_documents/general/blob_key');
        
        
//        $blobEndPoint = 'blob.core.windows.net';
//        $accountName = 'rempdevdiag309';
//        $key = 'IKIwd+g8ST5JvZdBhhxsy9i1naJ+JtwIEA0Dh1cNXKTHN8YVTxdGrALseQfT+muzexW+ZW+NmEcNZsMLldsk0g==';
        
        $connectionString = 'DefaultEndpointsProtocol=https;AccountName='.$accountName.';AccountKey='.$key.';';
        $blobClient = BlobRestProxy::createBlobService($connectionString);
       
        try {
            //Upload blob
            $blobClient->createBlockBlob($path, $fileName, $content);
            $blobClient->setBlobProperties($path, $fileName, null, array(
                'x-ms-blob-content-type' => $type
            ));
        } catch (ServiceException $e) {
            //echo $code = $e->getCode();
            //die;
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
        }
        $url = 'https://'.$accountName.'.'.$blobEndPoint.'/'.$path.'/'.$fileName;
        return $url;
    }
      public function getDistributionChannel()
        {
        try {
            $storeCode = $this->_storeManager->getGroup()->getCode();
            $distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code', $storeCode)->getFirstItem();
            return $distribution->getDistributionChannel();
        } catch (\Exception $e) {
            
            return false;
        }
    }
   public function getSalesOrg()
    {
        try {
            $adminId = $this->companyHelper->getCompanyAdminId();
            $adminUser = $this->customerRepositoryInterface->getById($adminId);
            if ($sapAccountNumber = $adminUser->getCustomAttribute('z_sap_code')) {
                $sapAccountNumber = $sapAccountNumber->getValue();
                return $sapAccountNumber;
            }
        } catch (\Exception $e) {
            
            return false;
        }
    }
     public function getPlantData()
    {
       
        try {
            $salesOrg = $this->getSalesOrg();
            $distribution = $this->getDistributionChannel();
            $sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled', 1)
                ->addFieldToFilter('distribution', $distribution)
                ->addFieldToFilter('sap_account_code', $salesOrg);
            return $sourceCollection->getFirstItem();
        } catch (\Exception $e) {
           
            return false;
        }
    }
        public  function plantWiseCollectionFilter($collection){
           
            $tableName = 'x_'.uniqid();
            $plantCode = $this->getPlantData()->getPlantCode();
            if ($plantCode) {
                $condition = $tableName.'.source_code = '.$plantCode;
            } else {
                $condition = $tableName.'.source_code = NULL';
            }
            $connection  = $this->_resource->getConnection();
            $collection->getSelect()->joinLeft(
            [$tableName => $connection->getTableName($this->_resource->getTableName('inventory_source_item'))],
            $tableName.'.sku = e.sku',
            []
            )->where(
                $condition
            );
          
            return $collection;
    }
 

}
