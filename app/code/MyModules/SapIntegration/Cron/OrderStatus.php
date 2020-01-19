<?php

namespace Redington\SapIntegration\Cron;

class OrderStatus
{
    /**
     * Construct
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * updateOrderStatus function
     *
     * @return void
     */
    public function updateOrderStatus()
    {
        echo 'Started execution';
        $sapReferenceNumberCollection = [];
        $processedOrderData = [];
        $orderStatusArray = [];
        $sapReferenceNumberCollectionOrderSplit = [];
        $sapReferenceNumberCollectionOrderSplitValue= [];
        $orderCollection = $this->orderCollectionFactory->create();
        foreach ($orderCollection as $order) {
            $orderStatus = $order->getStatus();
            if ($orderStatus != 'failed' && $orderStatus != 'delivered' && $orderStatus != 'canceled') {
                $referenceNumber = $order->getSapReferenceNumber();
                if ($referenceNumber) {
                    $sapReferenceNumberCollection[] =
                       [ 'VBELN' => $referenceNumber
                    ];
                    $processedOrderData[$referenceNumber] = $order->getEntityId();
                }
            }
        }
        $rounfOfSapReferenceNumber = round(count($sapReferenceNumberCollection)/10);
        $sapReferenceNumberCollectionOrderSplit =array_chunk($sapReferenceNumberCollection, 10);
        for($i = 0; $i < $rounfOfSapReferenceNumber; $i++)
        {
            $sapReferenceNumberCollectionOrderSplitValue['ZTS_VBELN'] = $sapReferenceNumberCollectionOrderSplit[$i];
            $sapReferenceNumberJson = json_encode($sapReferenceNumberCollectionOrderSplitValue);
            $orderStatusArray=$this->getStatus($sapReferenceNumberJson);
            if (!empty($orderStatusArray)) {
                foreach ($orderStatusArray as $orderStatusArrayValue) {
                    if ($orderStatusArrayValue['SONUMBER'] && $orderStatusArrayValue['STATUS']) {
                        try{
                            $orderId = $processedOrderData[ltrim($orderStatusArrayValue['SONUMBER'],'0')];
                            $this->setStatus($orderId, $orderStatusArrayValue['STATUS']);
                        }catch(\Exception $e){
                            $this->logMessage($e->getMessage());
                        }
                    }
                }
            } 
        }
        echo 'Execution completed, please check log Redington_OrderStatus';
    }
    
    /**
     * setStatus function
     *
     * @param [type] $orderId
     * @param [type] $status
     * @return void
     */
    private function setStatus($orderId, $status)
    {
        $this->logMessage('set status called for ' . $orderId . ' status is ' . $status);
        $order = $this->orderFactory->create()->load($orderId);
        switch (strtolower($status)) {
            case strtolower("Order Confirmed"):
                $order->setState('processing')->setStatus('order_confirmed');
                break;
            case strtolower("Preparing Dispatch"):
                $order->setState('processing')->setStatus('preparing_dispatch');
                break;
            case strtolower("On the way"):
                $order->setState('processing')->setStatus('on_the_way');
                break;
            case strtolower("Delivered"):
            case strtolower("Invoiced"):
                $order->setState('complete')->setStatus('delivered');
                break;
        }
        $order->save();
    }

    /**
     * getStatus function
     *
     * @param [type] $sapNumber
     * @return void
     */
    private function getStatus($sapNumber)
    {
        $orderUrl = $this->scopeConfig->getValue('redington_sap/general/so_status');

        $data = $sapNumber;
        $this->logMessage('Request ' . $data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $orderUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        try {
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($responseXml);
            $json = str_replace("{}", "null", $json);
            $responseArray = json_decode($json, true);

            $this->logMessage('rsponse : '.$json);
            if ($responseArray['SO_STATUS']) {
                return $responseArray['SO_STATUS']['ZSO_STATUS'];
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    private function logMessage($message)
    {
        $filePath = '/var/log/Redington_OrderStatus.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }
}
