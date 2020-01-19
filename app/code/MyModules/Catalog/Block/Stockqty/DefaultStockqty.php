<?php

namespace Redington\Catalog\Block\Stockqty;

class DefaultStockqty extends \Magento\CatalogInventory\Block\Stockqty\DefaultStockqty {

    /**
     * construct function
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory
     * @param \Redington\Configuration\Helper\Data $configurationHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Redington\Configuration\Helper\Data $configurationHelper,
        \Redington\Quotation\Helper\Reservation $reservationHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->configurationHelper = $configurationHelper;
        $this->reservationHelper = $reservationHelper;
        parent::__construct($context, $registry, $stockState, $stockRegistry, $data);
    }

    /**
     * toHtml function
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->shouldShowThreshold()) {
            return '';
        }
        return \Magento\Framework\View\Element\Template::_toHtml();
    }

    /**
     * shouldShowThreshold function
     *
     * @return boolean
     */
    public function shouldShowThreshold(){
        try{
            $productThreshold = $this->_coreRegistry->registry('current_product')->getZThreshold();
            $threshold = $productThreshold;
        }catch(\Exception $e){
            $this->logMessage('error is '.$e->getMessage());
            $threshold = false;
        }
        if(!$threshold){
            $threshold = $this->getThresholdQty();
        }
        $plantQty = $this->getStockQtyLeftInPlant();
        $this->logMessage('threshold is '.$threshold.' and plantQty is '.$plantQty);
        $reservedQty = $this->reservationHelper->getReservedQty($this->_coreRegistry->registry('current_product')->getSku());
        $this->logMessage('reserved qty is '.$reservedQty);
        return $threshold >= $plantQty - $reservedQty;
    }

    /**
     * getStockQtyLeftInPlant function
     *
     * @return number
     */
    public function getStockQtyLeftInPlant(){
        try{
            $productSku = $this->_coreRegistry->registry('current_product')->getSku();
            $plantCode = $this->configurationHelper->getPlantData()->getPlantCode();
            $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku',$productSku)->addFieldToFilter('source_code',$plantCode);
            if(sizeof($sourceCollection) > 0){
                $sourceData = $sourceCollection->getFirstItem();
                $qtyInPlant = $sourceData->getQuantity();
                $this->logMessage('Available qty in plant '.$plantCode. ' is '.$qtyInPlant .' for product ' .$productSku);
                $reservedQty = $this->reservationHelper->getReservedQty($productSku);
                $this->logMessage('reserved qty in plant '.$plantCode. ' is '.$reservedQty .' for product ' .$productSku);
                return $qtyInPlant - $reservedQty;
            }else{
                return false;
            }
        }catch(\Exception $e){
            $this->logMessage('Error in getting product qty'.$e->getMessage());
            return false;
        }
    }

    private function logMessage($message)
    {
        $filePath = '/var/log/Redington_Threshold.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }
}