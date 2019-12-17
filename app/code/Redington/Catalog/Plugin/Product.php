<?php
namespace Redington\Catalog\Plugin;

class Product
{
	 /**
     * Plugin constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory
     * @param \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory
     * @param \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
    	\Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
    	\Redington\Company\Helper\Data $companyHelper,
    	\Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
    	\Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
    	\Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
    	\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Quotation\Helper\Reservation $reservationHelper
    ){
    	$this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->reservationHelper = $reservationHelper;
    }

    /**
     * Check is product available for sale
     *
     * @return bool
     */
    public function afterIsSalable(\Magento\Catalog\Model\Product $product, $data)
    {
    	if($data){
            $productQtyInPlant = $this->getProductQtyInPlant($product->getSku());
            $reservedQty = $this->reservationHelper->getReservedQty($product->getSku());
    		if(($productQtyInPlant - $reservedQty) > 0){
    			return $data;
    		}else{
    			return false;
    		}	
    	}else{
    		return $data;
    	}
    }

    /**
     * Check whether the product type or stock allows to purchase the product
     *
     * @return bool
     */
    public function afterIsAvailable(\Magento\Catalog\Model\Product $product, $data)
    {
        if($data){
            $productQtyInPlant = $this->getProductQtyInPlant($product->getSku());
            $reservedQty = $this->reservationHelper->getReservedQty($product->getSku());
    		if(($productQtyInPlant - $reservedQty) > 0){
    			return $data;
    		}else{
    			return false;
    		}	
    	}else{
    		return $data;
    	}
    }

    /**
    * Return QTY of product or false if product not exist
    * @param $productSku
    * @return $qtyInPlant
    **/
    public function getProductQtyInPlant($productSku){
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try{
            $plantCode = $this->getPlantCode();
            $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku',$productSku)->addFieldToFilter('source_code',$plantCode);
            if(sizeof($sourceCollection) > 0){
                $sourceData = $sourceCollection->getFirstItem();
                $qtyInPlant = $sourceData->getQuantity();
                $this->logMessage('Available qty in plant '.$plantCode. ' is '.$qtyInPlant .' for product ' .$productSku, $filePath);
                return $qtyInPlant;
            }else{
                return false;
            }
        }catch(\Exception $e){
            $this->logMessage('Error in getting product qty'.$e->getMessage(), $filePath);
            return false;
        }
    }

    /**
    * Get plant of the partner based on DC & sales org
    **/
    public function getPlantCode(){
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try{
            $salesOrg = $this->getSalesOrg();
            $distribution = $this->getDistributionChannel();
            $sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled',1)
                ->addFieldToFilter('distribution',$distribution)
                ->addFieldToFilter('sap_account_code',$salesOrg);
            return $sourceCollection->getFirstItem()->getPlantCode();
        }catch(\Exception $e){
            $this->logMessage('Error in getting pant code'.$e->getMessage(), $filePath);
            return false;
        }
    }

    /**
    * Get sales org or SAP of the partner
    **/
    public function getSalesOrg() {
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try{
            $adminId = $this->companyHelper->getCompanyAdminId();
            $adminUser = $this->customerRepositoryInterface->getById($adminId);
            if($sapAccountNumber = $adminUser->getCustomAttribute('z_sap_code')){
                $sapAccountNumber = $sapAccountNumber->getValue();
                return $sapAccountNumber;
            }
            }catch(\Exception $e){
            $this->logMessage('Error in getting sales org of partner '.$adminId.' '.$e->getMessage(), $filePath);
            return false;
        }
    }

    /**
    * Getting distribution channel of the partner.
    **/
    public function getDistributionChannel(){
        $filePath = '/var/log/Redington_Catalog_Qty.log';
        try{
            $storeCode = $this->_storeManager->getGroup()->getCode();
            $distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code',$storeCode)->getFirstItem();
            return $distribution->getDistributionChannel();
        }catch(\Exception $e){
            $this->logMessage('Error in getting DC'.$e->getMessage(), $filePath);
            return false;
        }
	}

    /**
    * Write log
    */
    public function logMessage($message, $filePath = '/var/log/system.log') {        
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }

}