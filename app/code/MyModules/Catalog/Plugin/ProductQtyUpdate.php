<?php
namespace Redington\Catalog\Plugin;

use \Magento\Framework\Message\ManagerInterface;


class ProductQtyUpdate
{
    /**
     * @var Rationinglogic|Data
     */
    protected $_helperData;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * Plugin constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        ManagerInterface $messageManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Quotation\Helper\Reservation $reservationHelper
    ) {
        $this->quote = $checkoutSession->getQuote();
        $this->_messageManager = $messageManager;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->reservationHelper = $reservationHelper;
    }

    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param $data
     * @return array
     */
    public function beforeupdateItems(\Magento\Checkout\Model\Cart $subject,$data)
    {
        $quote = $subject->getQuote();
        $availableItems = $quote->getAllVisibleItems();
        foreach($availableItems as $key => $item){
            $itemId = $item->getItemId();
            $sku = $item->getSku();
            $itemQty = $data[$itemId]['qty'];
            $availableQty = $this->getProductQtyInPlant($sku);
            $reservedQty = $this->reservationHelper->getReservedQty($sku);
            if($itemQty > ($availableQty - $reservedQty)){
                try{
                    $data[$itemId]['qty'] = $availableQty - $reservedQty;
                    //$this->_messageManager->addError(__('The requested qty for "%1" is not available (available qty - %2).', $item->getName(), $availableQty));
                }catch(\Exception $e){
                    return [$data];
                }
            }
        }
        return [$data];

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