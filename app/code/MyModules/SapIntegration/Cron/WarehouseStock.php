<?php

namespace Redington\SapIntegration\Cron;

use Magento\Framework\App\ResourceConnection;

class WarehouseStock {
    /*
    *catalog inventory indexer
    */
    CONST INVENTORY_STOCK_INDEX = 'inventory';

    /**
     * construct function
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory
     * @param \Magento\Inventory\Model\SourceItemFactory $sourceItemFactory
     * @param \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Indexer\Model\IndexerFactory $indexerFactory
     * @param \Magento\Catalog\Model\Product $product
     * @param ResourceConnection $resource
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Magento\Inventory\Model\SourceItemFactory $sourceItemFactory,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Catalog\Model\Product $product,
        ResourceConnection $resource
    ){
        $this->productCollectionFactory = $productCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->indexerFactory = $indexerFactory;
        $this->_product = $product;
        $this->resource = $resource;
    }
    public function updateStock(){
        echo 'Started execution';
        $updatedStock = $this->getUpdatedData();
        if(is_array($updatedStock['ZRFC_WAREHOUSE_STOCK2'])){
            foreach($updatedStock['ZRFC_WAREHOUSE_STOCK2'] as $stock){
                $this->logMessage('warehouse : '.$stock['EX_WERKS'].' sku '.$stock['EX_MATNR'].' qty '.$stock['EX_LABST']);
                $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku',$stock['EX_MATNR'])->addFieldToFilter('source_code',$stock['EX_WERKS']);
                if(sizeof($sourceCollection) > 0){
                    $this->logMessage('assigning.....');
                    $sourceId = $sourceCollection->getFirstItem()->getId();
                    $source = $this->sourceItemFactory->create()->load($sourceId);
                    $source->setQuantity($stock['EX_LABST']);
                    $source->setStatus(1);
                    $source->save();
                    $this->logMessage('assigned.....');
                }else{
                    try{
                        if($this->_product->getIdBySku($stock['EX_MATNR'])){
                            $this->logMessage('assigning.....');
                            $source = $this->sourceItemFactory->create();
                            $source->setSourceCode($stock['EX_WERKS']);
                            $source->setSku($stock['EX_MATNR']);
                            $source->setQuantity($stock['EX_LABST']);
                            $source->save();
                            $this->logMessage('assigned.....');
                        }
                    }catch(\Exception $e){
                        continue;
                    }
                }
            }
        }
        $this->clearReservationData();
        $this->reindexOne(self::INVENTORY_STOCK_INDEX);
        echo 'Execution completed, please check log Redington_stockUpdate';
    }

    public function clearReservationData(){
        $connection = $this->resource->getConnection();
        $reservationTable = $this->resource->getTableName('inventory_reservation');
        $connection->truncateTable($reservationTable);
    }
    /**
     * Regenerate single index
     *
     * @return void
     * @throws \Exception
     */
    private function reindexOne($indexId){
        $indexer = $this->indexerFactory->create()->load($indexId);
        $indexer->reindexAll();
    }

    private function getUpdatedData(){
        $stockUrl = $this->scopeConfig->getValue('redington_sap/general/plant_data');
        $data = $this->getActualBody();
        $data = json_encode($data);
        $this->logMessage('Request '.$data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $stockUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        try{
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($responseXml);
            $json = str_replace("{}","null",$json);
            $this->logMessage('rsponse '.$json);
            $responseArray = json_decode($json,TRUE);        
            if($responseArray['STOCK_DETAILS']) {
                return $responseArray['STOCK_DETAILS'];
            }else{
                return false;
            }
        }catch(\Exception $e){
            return false;
        }
    }


    private function getProductSkus(){
        $productCollection = $this->productCollectionFactory->create();
        $productSkus = [];
        foreach ($productCollection as $product) {
            $productSkus[$product->getId()] = $product->getSku();
        }
        return $productSkus;
    }
    private function getWarehouseCodes(){
        $sourceCollection = $this->sourceCollectionFactory->create()->addFieldToFilter('enabled',1);
        // $warehouseCodes = [];
        // foreach($sourceCollection as $source) {
        //     if($source->getSourceCode()!='default')
        //         array_push($warehouseCodes, $source->getSourceCode());
        // }
        // return $warehouseCodes;
        return $sourceCollection;
    }
    private function logMessage($message) {
        $filePath = '/var/log/Redington_stockUpdate.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
    private function getActualBody(){
        $actualBody = [
            "IM_MATNR"=>[  
                "ZTS_MATNR"=>[
                    
                ]
            ],
            "IM_WERKS"=>[  
                "ZTS_WERKS"=>[]
            ],
            "IM_VTWEG"=>[
                "ZTS_VTWEG"=>[]
            ],
            "IM_BUKRS"=>[
                "ZTS_BUKRS"=>[]
            ]
        ];
        $productSkus = $this->getProductSkus();
//        foreach($productSkus as $id => $sku){
            $itemArray = [
//                "MATNR"=>$sku
                "MATNR"=>""
            ];
//            array_push($actualBody['IM_MATNR']['ZTS_MATNR'], $itemArray);
//        }
        $warehouseStocks = $this->getWarehouseCodes();
        foreach($warehouseStocks as $plants){
            if($plants->getSourceCode() != 'default') {
                $plantArray = [
                    "WERKS"=> $plants->getPlantCode()
                ];
                $companyCodeArray = [
                    "BUKRS"=>$plants->getSapAccountCode()
                ];
                $distributionChannel = [
                    "VTWEG"=>$plants->getDistribution()
                ];
                array_push($actualBody['IM_WERKS']['ZTS_WERKS'],$plantArray);
                array_push($actualBody['IM_VTWEG']['ZTS_VTWEG'],$distributionChannel);
                array_push($actualBody['IM_BUKRS']['ZTS_BUKRS'],$companyCodeArray);

                // echo 'plant code '.$plants->getPlantCode().' company code '.$plants->getSapAccountCode().' distribution '.$plants->getDistribution();
            }
        }
        // var_dump($actualBody);exit;
        return $actualBody;
    }
}