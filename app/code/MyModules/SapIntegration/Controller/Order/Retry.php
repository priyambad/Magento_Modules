<?php

namespace Redington\SapIntegration\Controller\Order;
use Magento\Framework\Controller\ResultFactory; 
class Retry extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Redington\SapIntegration\Model\ResourceModel\OrderReference\CollectionFactory $orderReferenceCollectionFactory,
        \Redington\SapIntegration\Model\OrderReferenceFactory $orderReferenceFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->serialize = $serialize;
        $this->orderReferenceCollectionFactory = $orderReferenceCollectionFactory;
        $this->orderReferenceFactory = $orderReferenceFactory;
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $success = false;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createSO.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
        //$resultRedirect = $this->resultPageFactory->create(ResultFactory::TYPE_REDIRECT);

        $orderId = $this->request->getParam('id');
        $orderReference = $this->orderReferenceCollectionFactory->create()->addFieldToFilter('order_id',$orderId)->getFirstItem();
        $referenceId = $orderReference->getEntityId();
        $requestData = $orderReference->getRequestData();
        $unserializedRequestData = unserialize($requestData);
        $requestBody = json_encode($unserializedRequestData);
        
        $bapiUrl = $this->scopeConfig->getValue('redington_sap/general/so_create');
        $response = $this->retryOrder($bapiUrl,$requestBody);
        $orderReference = null;
        if($response){
            $response = json_encode($response);

            $response = str_replace("{}","null",$response);

            $response = json_decode($response,true);

            $logger->info('response from sap is : ');
            $logger->info(print_r($response,true));


            $orderReference = $response['SALESORDER'];
        }
        if($orderReference) {
            $status = 1;
            $savedOrder = $this->orderFactory->create()->load($orderId);
            $savedOrder->setSapReferenceNumber($orderReference);
            $savedOrder->setStatus('pending')->save();
            $response = $this->serialize->serialize($response);
            $success = true;
        }else{
            $status = 0;
            $response = null;
        }
        $logger->info('order id is : '.$orderId);
        $orderReference = $this->orderReferenceFactory->create()->load($referenceId);
        $logger->info('oder referece loaded');
        $orderReference->setResponseData($response);
        $orderReference->setStatus($status);
        $orderReference->save();

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererUrl();
        if($success){
            $this->messageManager->addSuccessMessage(__('Order placed successfully.'));
        }else{
            $this->messageManager->addError(__('We could not process your order.'));
        }
        return $resultRedirect;


    }
    private function retryOrder($url,$requestBody){
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createSO.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
        $logger->info('request body :'.$requestBody);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400); 
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        $logger->info('response received');
        try{
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            return $responseXml;
        }catch(\Exception $e){
            $logger->info('exception.....');
            return false;
        }
    }
}