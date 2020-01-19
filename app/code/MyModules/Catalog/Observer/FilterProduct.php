<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */

namespace Redington\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;


class FilterProduct implements ObserverInterface {

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession

    ) {
        $this->_resource = $resource;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->companyHelper = $companyHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->customerSession = $customerSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $entityIdArray = array();
        $allowedBrands = $this->customerSession->getAllowedBrands();
        $this->logMessage('allowed brands from session for Catalog');
        $this->logMessage($allowedBrands);
        $plantCode = $this->getPlantCode();
        if($plantCode){
            $condition = 'p.source_code = '.$plantCode;
        }else{
            $condition = 'p.source_code = NULL';
        }
        $connection  = $this->_resource->getConnection();
         // $collection = $observer->getData('collection');
         $collection = $observer->getData('collection')->addAttributeToSelect('*')->addCategoriesFilter(array('in' => $allowedBrands));
        $collection->getSelect()->joinLeft(
            ['p' => $connection->getTableName($this->_resource->getTableName('inventory_source_item'))],
            'p.sku = e.sku',
            []
        )->where(
            $condition
        );

        foreach ($collection->getData() as $key => $value) {
            array_push($entityIdArray,$value['entity_id']); 
        }
        $this->customerSession->setEntityId($entityIdArray);
      
    }
    public function getPlantCode(){
        $salesOrg = $this->getSalesOrg();
        $distribution = $this->getDistributionChannel();
        $sourceCollection = $this->sourceCollectionFactory->create()
			->addFieldToFilter('enabled',1)
			->addFieldToFilter('distribution',$distribution)
            ->addFieldToFilter('sap_account_code',$salesOrg);
        return $sourceCollection->getFirstItem()->getPlantCode();
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
    private function logMessage($message)
    {
        $filePath = '/var/log/Redington_CategoryData.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }
}
