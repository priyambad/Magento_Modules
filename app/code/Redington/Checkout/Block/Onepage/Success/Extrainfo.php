<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Block\Onepage\Success;

use Magento\Framework\View\Element\Template;

class Extrainfo extends Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \Amasty\Checkout\Model\AdditionalFields
     */
    protected $additionalFields;
	
	/**
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $session
     * @param \Amasty\Checkout\Model\AdditionalFields $additionalFields
	 * @param \Redington\PaymentGroup\Model\GroupFactory $paymentGroup
	 * @param \Redington\Company\Helper\Data $companyHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $session,
        \Amasty\Checkout\Model\AdditionalFields $additionalFields,
		\Redington\PaymentGroup\Model\GroupFactory $paymentGroup,
		\Redington\Company\Helper\Data $companyHelper,		
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->session = $session;
        $this->additionalFields = $additionalFields;
		$this->paymentGroup = $paymentGroup;
		$this->companyHelper = $companyHelper;
    }
	
	/**
     * Preparing global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        if (!$this->registry->registry('current_order')) {
            $this->registry->register('current_order', $this->getOrder());
        }

        return parent::_prepareLayout();
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
			$orderObj = $this->registry->registry('current_order');
			return $orderObj;
		}
    }
	/**
     * Retrieve Lpo Number related to Order
     *
     * @return string
     */
    public function getLponumber()
    {
        $resultData = $this->getExtraInfo();
        return ($resultData['lponumber'] !="" ) ? $resultData['lponumber'] : '';
    }
	/**
     * Retrieve Sap Reference Number related to Order
     *
     * @return string
     */
	public function getSapRefNumber(){
		$order = $this->getOrder();
		return $sapRefNumber = $order->getSapReferenceNumber();
		
	}
	/**
     * Retrieve order status related to Order
     *
     * @return string
     */
	public function getOrderStatus(){
		$order = $this->getOrder();
		return $orderStatus = $order->getStatus();
	}	
	
	/**
     * Retrieve Lpo Reference Document related to Order
     *
     * @return string(BLOB URL)
     */
	public function getLpoReferenceDocument()
    {
        return $this->getCheckoutDocument('lpo');
    }
	
	/**
     * Retrieve Purchase Order Document related to Order
     *
     * @return string(BLOB URL)
     */
	public function getPurchaseOrderDocument()
    {
        return $this->getCheckoutDocument('order');
    }
	
	/**
     * Retrieve Lpo Reference & Purchase Order Document related to Order
     *
     * @return string(BLOB URL)
     */
	public function getCheckoutDocument($type)
    {
        $resultData = $this->getExtraInfo();

		if($type == 'lpo')
			return ($resultData['lpo_reference_document'] != "") ? $resultData['lpo_reference_document'] : '';
        else 
			return ($resultData['purchase_order_document'] != "") ? $resultData['purchase_order_document'] : '';
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

}
