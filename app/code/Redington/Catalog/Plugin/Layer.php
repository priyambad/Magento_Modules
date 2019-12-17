<?php

namespace Redington\Catalog\Plugin;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class Layer
{

    /**
     * Undocumented function
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory
     * @param \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory
     * @param \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $data
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->_resource = $resource;
    }

    

    public function aroundGetProductCollection(\Magento\Catalog\Model\Layer $subject, \Closure $proceed)
    {
        $this->logMessage('Overriden child class function is called');
        if (isset($this->_productCollections[$subject->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$subject->getCurrentCategory()->getId()];
        } else {
            $collection = $subject->collectionProvider->getCollection($subject->getCurrentCategory());
            $collection = $this->plantWiseCollectionFilter($collection);
            $this->prepareProductCollection($collection);
            $this->_productCollections[$subject->getCurrentCategory()->getId()] = $collection;
        }

        return $collection;
    }
    

    private function plantWiseCollectionFilter($collection){
        $this->logMessage('filter function called');
        $tableName = 'x_'.uniqid();
        $plantCode = $this->getPlantCode();
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
    

    /**
    * Get plant of the partner based on DC & sales org
    **/
    public function getPlantCode()
    {
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try {
            $salesOrg = $this->getSalesOrg();
            $distribution = $this->getDistributionChannel();
            $sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled', 1)
                ->addFieldToFilter('distribution', $distribution)
                ->addFieldToFilter('sap_account_code', $salesOrg);
            return $sourceCollection->getFirstItem()->getPlantCode();
        } catch (\Exception $e) {
            $this->logMessage('Error in getting pant code'.$e->getMessage(), $filePath);
            return false;
        }
    }

    /**
    * Get sales org or SAP of the partner
    **/
    public function getSalesOrg()
    {
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try {
            $adminId = $this->companyHelper->getCompanyAdminId();
            $adminUser = $this->customerRepositoryInterface->getById($adminId);
            if ($sapAccountNumber = $adminUser->getCustomAttribute('z_sap_code')) {
                $sapAccountNumber = $sapAccountNumber->getValue();
                return $sapAccountNumber;
            }
        } catch (\Exception $e) {
            $this->logMessage('Error in getting sales org of partner '.$adminId.' '.$e->getMessage(), $filePath);
            return false;
        }
    }

    /**
    * Getting distribution channel of the partner.
    **/
    public function getDistributionChannel()
    {
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try {
            $storeCode = $this->_storeManager->getGroup()->getCode();
            $distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code', $storeCode)->getFirstItem();
            return $distribution->getDistributionChannel();
        } catch (\Exception $e) {
            $this->logMessage('Error in getting DC'.$e->getMessage(), $filePath);
            return false;
        }
    }

    private function logMessage($message)
    {
        $filePath = '/var/log/Redington_Layered.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }
}
