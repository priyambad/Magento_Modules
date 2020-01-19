<?php
namespace Redington\Catalog\Plugin;



class FilterProduct
{
    
    /**
     * Plugin constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->_resource = $resource;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Sm\ListingTabs\Block\ListingTabs $subject
     * @param $data
     * @return array
     */
    public function after_getProductsBasic(\Sm\ListingTabs\Block\ListingTabs $subject,$collection)
    {
        if($this->customerSession->isLoggedIn()) {
            if(is_numeric($collection)){
                return $collection;
            }else{
                $plantCode = $this->getPlantCode();
                if($plantCode){
                    $condition = 'p.source_code = '.$plantCode;
                }else{
                    $condition = 'p.source_code = NULL';
                }
                $connection  = $this->_resource->getConnection();
                $collection->getSelect()->joinLeft(
                    ['p' => $connection->getTableName($this->_resource->getTableName('inventory_source_item'))],
                    'p.sku = e.sku',
                    []
                )->where(
                    $condition
                );
                return $collection;
            }
        }else{
            return $collection;
        }
        
        
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
}