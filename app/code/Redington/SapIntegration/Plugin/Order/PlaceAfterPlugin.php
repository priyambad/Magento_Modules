<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_SapIntegration
 */
namespace Redington\SapIntegration\Plugin\Order;

class PlaceAfterPlugin
{

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;
    /**
     * Added Dependencies
     *
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Redington\SapIntegration\Model\OrderReferenceFactory $sapOrderReferenceFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory
     * @param \Amasty\Checkout\Model\AdditionalFields $additionalFields
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSenderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Redington\SapIntegration\Model\OrderReferenceFactory $sapOrderReferenceFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Amasty\Checkout\Model\AdditionalFields $additionalFields,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSenderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->companyHelper = $companyHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->orderReferenceFactory = $sapOrderReferenceFactory;
        $this->serialize = $serialize;
        $this->orderFactory = $orderFactory;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->additionalFields = $additionalFields;
        $this->scopeConfig = $scopeConfig;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->orderSender = $orderSenderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
    }
    /**
     * After Place order function
     *
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagementInterface
     * @param array $order
     * @return void
     */
    public function afterPlace(\Magento\Sales\Api\OrderManagementInterface $orderManagementInterface, $order)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createSO.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('called plugin constructor');

        $this->setAdditionalOrderData($order);
        $bapiUrl = $this->scopeConfig->getValue('redington_sap/general/so_create');
        $requestBody = $this->getRequestBody($order);
        $logger->info(print_r($requestBody, true));

        $response = $this->sendRequest($bapiUrl, $requestBody);
        $orderReference = null;
        if ($response) {
            $response = json_encode($response);

            $response = str_replace("{}", "null", $response);

            $response = json_decode($response, true);

            $logger->info('response from sap is : ');
            $logger->info(print_r($response, true));

            if(array_key_exists('SALESORDER',$response)){
                $orderReference = $response['SALESORDER'];
                $response = $this->serialize->serialize($response);
            }else {
                $orderReference = null;
                $response = null;
            }
        }
        if ($orderReference) {
            $status = 1;
            $orderId = $order->getId();
            $savedOrder = $this->orderFactory->create()->load($orderId);
            $savedOrder->setSapReferenceNumber($orderReference)->save();
        } else {
            $status = 0;
            $orderId = $order->getId();
            $savedOrder = $this->orderFactory->create()->load($orderId);
            $savedOrder->setStatus('failed')->save();

        }
        $orderId = $order->getId();
        $logger->info('order id is : ' . $orderId);
        $orderReference = $this->orderReferenceFactory->create();
        $logger->info('oder referece created');
        $orderReference->setOrderId($orderId);
        $orderReference->setRequestData($this->serialize->serialize($requestBody));
        $orderReference->setResponseData($response);
        $orderReference->setStatus($status);
        $orderReference->save();
        return $order;
    }
    /**
     * Request to so creation
     *
     * @param string $url
     * @param array $requestBody
     * @return void
     */
    public function sendRequest($url, $requestBody)
    {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createSO.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $requestBody = json_encode($requestBody);
        $logger->info('request body :' . $requestBody);
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
        try {
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            return $responseXml;
        } catch (\Exception $e) {
            $logger->info('exception.....');
            return false;
        }
    }
    /**
     * Fetch sap account number
     *
     * @return void
     */
    public function getSapAccountNumber()
    {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_account_number')->getValue();
        return $sapAccountNumber;
    }
    /**
     * Get Sales Orgnisation
     *
     * @return void
     */
    public function getSalesOrg()
    {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_code')->getValue();
        return $sapAccountNumber;
    }
    /**
     * Request Body
     *
     * @param array $order
     * @return void
     */
    public function getRequestBody($order)
    {
        $sapAccountNumber = $this->getSapAccountNumber();
        $forwarderNumber = $this->getForwarderSapNumber($order);
        $shippingAddresNumber = $this->getAddressSapNumber($order);

        if ($forwarderNumber != '') {
            $shippingAddresNumber = $forwarderNumber;
        } else if ($shippingAddresNumber != '' && $forwarderNumber == '') {
            $shippingAddresNumber = $shippingAddresNumber;
        } else {
            $shippingAddresNumber = $sapAccountNumber;
        }

        $requestBody = [
            "IM_HEADER" => [
                "SALES_ORG" => $this->getSalesOrg(),
                "DISTR_CHAN" => $this->getDistributionChannel(),
                "DIVISION" => "00",
                "PURCH_NO" => $this->orderAdditionalData['lponumber'],
                "SALES_GRP" => "115",
                "SALES_OFF" => "103",
                "PARTN_NUMB1" => $sapAccountNumber,
                "PARTN_NUMB2" => $shippingAddresNumber,
                "M_SO_NO" => $order->getId(),
                "BLOB_LINK" => $this->encodeSpecialCharacters($this->orderAdditionalData['lpo_reference_document']),
                "PDC_LINK" => $this->encodeSpecialCharacters($this->orderAdditionalData['pdc_document']),
                "CDC_LINK" => $this->encodeSpecialCharacters($this->orderAdditionalData['cdc_document']),
                "CASH_LINK" => $this->encodeSpecialCharacters($this->orderAdditionalData['cash_document']),
                "PDC_NO" => $this->encodeSpecialCharacters($this->orderAdditionalData['pdc_ref_no']),
                "CDC_NO" => $this->encodeSpecialCharacters($this->orderAdditionalData['cdc_ref_no']),
                "CASH_NO" => $this->encodeSpecialCharacters($this->orderAdditionalData['cash_ref_no']),
            ],
            "IT_ITEM" => [
                "ZSALES_ITEM" => [],
            ],
        ];
        $ZSALES_ITEM = [];
        foreach ($order->getAllItems() as $item) {
            $itemData = [
                "MATERIAL" => $item->getSku(),
                "PLANT" => $this->getPlantCode(),
                "STORE_LOC" => "",
                "TARGET_QTY" => $item->getQtyOrdered(),
                "SELLING_PRICE" => $item->getPrice(),
            ];
            array_push($ZSALES_ITEM, $itemData);
        }
        $requestBody['IT_ITEM']['ZSALES_ITEM'] = $ZSALES_ITEM;

        return $requestBody;
    }

    public function encodeSpecialCharacters($stringVal){
        //$encodedStringVal = str_replace(":", "C7F6DC",$stringVal);
        //$encodedStringVal = str_replace("-", "2XEBHF",$stringVal);
        //$encodedStringVal = str_replace(".", "BQ9D5F",$stringVal);
        $encodedStringVal = str_replace("/", "B438FA",$stringVal);
        $encodedStringVal = str_replace("\\", "6A7E93",$stringVal);
        //$encodedStringVal = str_replace("(", "1XF7E2",$stringVal);
        //$encodedStringVal = str_replace(")", "C738AD7",$stringVal);
        $encodedStringVal = str_replace("&", "C2812D",$stringVal);
        return $encodedStringVal;
    }

    /**
     * Get sap address number
     *
     * @param array $order
     * @return void
     */
    public function getAddressSapNumber($order)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createSO.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $orderId = $order->getId();
        $sapAddressNumber = '';
        try {
                $order = $this->orderFactory->create()->load($orderId);
                $addressId = $order->getShippingAddress()->getCustomerAddressId();
                $address = $this->addressRepository->getById($addressId);
                $sapAddressNumber = $address->getCustomAttribute('sap_address_id')->getValue();
                $forwarderAddressId = $address->getCustomAttribute('sap_address_id')->getValue();
                $logger->info('Shipping Address Id :' . $sapAddressNumber);
        } catch (\Exception $e) {
            $logger->info('Shipping Address Id :' . $e->getMessage());
        }
        return $sapAddressNumber;

    }
    /**
     * Get forwarder address number
     *
     * @param array $order
     * @return void
     */
    public function getForwarderSapNumber($order)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createSO.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $orderId = $order->getId();
        $forwarderAddressNumber = '';
        try {
                $order = $this->orderFactory->create()->load($orderId);
                $addressId = $order->getForwarderAddressId();
                $address = $this->addressRepository->getById($addressId);
                $forwarderAddressNumber = $address->getCustomAttribute('sap_address_id')->getValue();
                $logger->info('Forwarder Address Id :' . $forwarderAddressNumber);
        } catch (\Exception $e) {
                $logger->info('Forwarder Address Id :' . $e->getMessage());
        }
        return $forwarderAddressNumber;

    }
    /**
     * Get Distributional channel
     *
     * @return void
     */
    public function getDistributionChannel()
    {
        $storeCode = $this->_storeManager->getGroup()->getCode();
        $distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code', $storeCode)->getFirstItem();
        return $distribution->getDistributionChannel();
    }
    /**
     * Set additional order data
     *
     * @param array $order
     * @return void
     */
    public function setAdditionalOrderData($order)
    {

        $quoteId = $order->getQuoteId();
        $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
        $this->orderAdditionalData = $additinalInfo->getData();
    }
    /**
     * Get Warehouse code
     *
     * @return void
     */
    public function getPlantCode()
    {
        $salesOrg = $this->getSalesOrg();
        $distribution = $this->getDistributionChannel();
        $sourceCollection = $this->sourceCollectionFactory->create()
            ->addFieldToFilter('enabled', 1)
            ->addFieldToFilter('distribution', $distribution)
            ->addFieldToFilter('sap_account_code', $salesOrg);
        return $sourceCollection->getFirstItem()->getSourceCode();
    }
}
