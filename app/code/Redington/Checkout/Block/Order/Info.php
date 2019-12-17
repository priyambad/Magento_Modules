<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Block\Order;

use Magento\Sales\Model\Order\Address;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;

class Info extends \Magento\Sales\Block\Order\Info
{
	/**
     * @var string
     */
    protected $_template = 'Redington_Checkout::order/info.phtml';
	/**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;
	
	/**
     * @var \Magento\Customer\Api\AddressRepositoryInterface 
     */
    protected $addressRepository;
	
	/**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

	/**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
	 * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
	 * @param \Magento\Customer\Model\Address\Config $addressConfig
	 * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
		\Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
		\Magento\Customer\Model\Address\Config $addressConfig,
		\Magento\Customer\Model\Address\Mapper $addressMapper,
        \Redington\SapIntegration\Model\ResourceModel\OrderReference\CollectionFactory $orderReferenceCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Redington\PaymentGroup\Model\GroupFactory $paymentGroup,
		\Redington\Company\Helper\Data $companyHelper,
		\Amasty\Checkout\Model\AdditionalFields $additionalFields,
	    \Magento\Checkout\Model\Session $session,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
		$this->addressRepository = $addressRepository;
		$this->addressConfig = $addressConfig;
		$this->addressMapper = $addressMapper;
        $this->orderReferenceCollectionFactory = $orderReferenceCollectionFactory;
        $this->scopeConfig = $scopeConfig;
		$this->paymentGroup = $paymentGroup;
		$this->companyHelper = $companyHelper;
		$this->session = $session;
		$this->additionalFields = $additionalFields;
		$this->_countryFactory = $countryFactory;
		$this->customerFactory = $customerFactory;
		$this->customerSession = $customerSession;
        parent::__construct($context, $this->coreRegistry, $this->paymentHelper, $this->addressRenderer, $data);
    }
	
	/**
     * Retrieve Forwarder Address Id related to Order
     *
     * @return int
     */
    public function getForwarderAddressId()
    {
		$order = $this->getOrder();
        $forwarderAddressId = $order->getForwarderAddressId();
		return $forwarderAddressId;
	}
	
	/**
     * Retrieve Forwarder Address 
     *
     * @return array
     */
    public function getForwarderAddress()
    {
        $forwarderAddressId = $this->getForwarderAddressId();
        $addressObject = $this->addressRepository->getById($forwarderAddressId);
        return $addressObject;
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($this->addressMapper->toFlatArray($addressObject));
    }
    public function getOrderError(){
        try{
            $orderId = $this->getOrder()->getId();
            $orderReference = $this->orderReferenceCollectionFactory->create()->addFieldToFilter('order_id',$orderId)->getFirstItem();
            $responseData = $orderReference->getResponseData();
            $response = unserialize($responseData);
            $orderError = $response['RETURN']['BAPIRET2']['MESSAGE'];
            return $orderError;
        }catch(\Exception $e){
            return false;
        }
    }
    public function getErrorMessage(){
        return $this->scopeConfig->getValue('redington_sap/general/so_message');
    }
	
	public function getDefaultShippingAddressId(){
		  $custId = $this->customerSession->getCustomer()->getId();
		  $customer = $this->customerFactory->create()->load($custId);
		 return $shippingAddressId = $customer->getDefaultShipping();
	}
	
	/**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
		$orderObj = $this->session->getLastRealOrder();
		$orderObj->getSize();
		if($orderObj->getSize()){
			return $orderObj;
		}else{
			$orderObj = $this->coreRegistry->registry('current_order');
			return $orderObj;
		}
    }
	/**
     * Retrieve Additional Informations related to Order
     *
     * @return array
     */
	public function getExtraInfo(){
		$order = $this->getOrder();
        $quoteId = $order->getQuoteId();
	    $additinalInfo = $this->additionalFields->findByField($quoteId,'quote_id');
        $resultData = $additinalInfo->getData();
		return $resultData;
    }
	/**
     * Retrieve payment reference document related to Order
     *
     * @return array
     */
    public function getPaymentRefDoc()
    {
        $resultData = $this->getExtraInfo();
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        if ($paymentMethod == 'pdc') :
            return ($resultData['pdc_document'] != "") ? $resultData['pdc_document'] : '';
        endif;
        if ($paymentMethod == 'cdc') :
            return ($resultData['cdc_document'] != "") ? $resultData['cdc_document'] : '';
        endif;
        if ($paymentMethod == 'cashpayment') :
            return ($resultData['cash_document'] != "") ? $resultData['cash_document'] : '';
        endif;
    }
    /**
     * Retrieve payment reference number related to Order
     *
     * @return array
     */
    public function getPaymentRefNum(){
        $resultData = $this->getExtraInfo();
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        if ($paymentMethod == 'pdc') :
            return ($resultData['pdc_ref_no'] != "") ? $resultData['pdc_ref_no'] : '';
        endif;
        if ($paymentMethod == 'cdc') :
            return ($resultData['cdc_ref_no'] != "") ? $resultData['cdc_ref_no'] : '';
        endif;
        if ($paymentMethod == 'cashpayment') :
            return ($resultData['cash_ref_no'] != "") ? $resultData['cash_ref_no'] : '';
        endif;

    }

	public function getPaymentTerm(){
		$paymentMethod = $this->getOrder()->getPayment()->getMethod();
		$companyId = $this->companyHelper->retrieveCompanyId();
		$paymentTerm = [];
		$collection = $this->paymentGroup->create()->getCollection()
            ->addFieldToFilter('partner_id', array('eq' => $companyId));
        $data = $collection->getData();
		
		foreach($data as $value) :
                $paymentTermCode = $value['sap_ref_code'];
                $paymentTerm = explode(',', $paymentTermCode);
        endforeach;
		if ($paymentMethod == 'pdc') :
			
        try {
            
				
            if (in_array('R128', $paymentTerm)) :
                $date = $this->getOrder()->getCreatedAt();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 8 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 7 days'));
                }
                return 'Please do payment on ' . $date.' or before.';
            endif;
            if (in_array('R014', $paymentTerm)) :
                $date = $this->getOrder()->getCreatedAt();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 30 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 29 days'));
                }
               return 'Please do payment on ' . $date.' or before.';
            endif;
		}catch(\Exception $e){
			//$e->getMessage();
		}	
		endif;
		
		if($paymentMethod =='companycredit') :
			try {
            if (in_array('R002', $paymentTerm)) :
                return 'Please ensure that order payment is done within 7 days.';
            endif;
            if (in_array('R003', $paymentTerm)) :
                return 'Please ensure that order payment is done within 15 days.';
            endif;
            if (in_array('R006', $paymentTerm)) :
               return 'Please ensure that order payment is done within 30 days.';
            endif;
            if (in_array('R129', $paymentTerm)) :
                return 'Please ensure that order payment is done within 8 days.';
            endif;
			}catch(\Exception $e){
				//$e->getMessage();
			}
			
		endif;
		
	}	
	
	public function getPaymentMethod(){

        return $this->getOrder()->getPayment()->getMethod();
    }
	
	public function getCountryname($countryCode){    
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }
}
