<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Redington_Market
 */

namespace Redington\Checkout\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;

class AddExtraTransportData implements ObserverInterface
{
    /**
     * Redington Market Helper
     *
     * @var \ Redington\Checkout\Helper\Data
     */
    protected $redingtonCheckoutHelper;

    /**
     * @var Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
	/**
     * @var Magento\Store\Model\StoreManagerInterface
     */
	protected $_storeManager;

    /**
     *
     * @param RedirectInterface $redirect
	 * @param QuoteFactory $quoteFactory
	 * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Redington\Checkout\Helper\Data $redingtonCheckoutHelper,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Customer\Model\Session $customerSession
    ) {
        $this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
        $this->quoteFactory = $quoteFactory;
		$this->_storeManager = $storeManager;
		$this->_scopeConfig = $scopeConfig;
		$this->_countryFactory = $countryFactory;
		$this->customerFactory = $customerFactory;
		$this->customerSession = $customerSession;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {

            $transport['formattedForwarderAddress'] = '';
            $transport = $observer->getEvent()->getTransport();
			
            $order = $transport['order'];
            $forwarderAddressId = $order->getForwarderAddressId();
            if ($forwarderAddressId) {
                $transport['formattedForwarderAddress'] = $this->redingtonCheckoutHelper->getForwarderAddress($forwarderAddressId);
            }

            $transport['referenceNumber'] = "";
            $transport['referenceDocument'] = "";
            $quoteId = $order->getQuoteId();
            $quote = $this->quoteFactory->create()->load($quoteId);
            if ($quoteId != "") {
                $additionalData = $this->redingtonCheckoutHelper->getOrderAdditionalInfo($quoteId);
                $transport['referenceNumber'] = array_key_exists('lponumber', $additionalData) ? $additionalData['lponumber'] : "";
                $transport['referenceDocument'] = array_key_exists('lpo_reference_document', $additionalData) ? $additionalData['lpo_reference_document'] : "";
                $transport['cdcreferenceNumber'] = array_key_exists('cdc_ref_no', $additionalData) ? $additionalData['cdc_ref_no'] : "";
                $transport['cdcreferenceDocument'] = array_key_exists('cdc_document', $additionalData) ? $additionalData['cdc_document'] : "";
                $transport['pdcreferenceNumber'] = array_key_exists('pdc_ref_no', $additionalData) ? $additionalData['pdc_ref_no'] : "";
                $transport['pdcreferenceDocument'] = array_key_exists('pdc_document', $additionalData) ? $additionalData['pdc_document'] : "";
                $transport['cashreferenceNumber'] = array_key_exists('cash_ref_no', $additionalData) ? $additionalData['cash_ref_no'] : "";
                $transport['cashreferenceDocument'] = array_key_exists('cash_document', $additionalData) ? $additionalData['cash_document'] : "";
            }
			$transport['paymentcurrency'] = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
            if ($order->getWarehouseCode() != '') {
                $street = $quote->getShippingAddress()->getStreet();
                $street1='';
				if ($street[0] != '') {
                    $street1 = $street[0];
                }
				
                $transport['shippingAddressLabel'] = __('Pickup Info');
                $transport['formattedShippingAddress'] = $quote->getShippingAddress()->getLastname() . '<br>' . $quote->getShippingAddress()->getFirstname() . '<br>' . $street1 . '<br>' . $quote->getShippingAddress()->getCity() . ',' . $quote->getShippingAddress()->getPostcode() . '<br>' . $this->getCountryname($quote->getShippingAddress()->getCountry()) . '<br>T:' . $quote->getShippingAddress()->getTelephone();
            } else {
                $transport['shippingAddressLabel'] = __('Shipping Info');
				$defaultShipping = $this->getDefaultShippingAddressId();
				$shippingId = $order->getShippingAddress()->getCustomerAddressId();
				$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_emaildata.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer); 
				
				$address = $order->getShippingAddress();
                    $street=$quote->getShippingAddress()->getStreet();	
                    $street1='';
                    $street2='';
                    $street3='';
					if (isset($street[0]) && $street[0] != '') {
                        $street1 = $street[0];
                    }
                    if (isset($street[1]) && $street[1] != '') {
                        $street2 = $street[1];
                    }	
                    if (isset($street[2]) && $street[2] != '') {
                        $street3 = $street[2];
                    }
                    if($street2!=''){
                        $street2 = '<br>'.$street2;
                    }else{
                        $street2 = '';
                    }
                    if($street3!=''){
                        $street3 = '<br>'.$street3;
                    }else{
                        $street3 = '';
                    }
                if($defaultShipping == $shippingId) :
                    
					$transport['formattedShippingAddress'] = $address->getFirstname().' '.$address->getLastname().'<br>'.$address->getCompany().'<br>'.$street1.$street2.$street3.'<br>'.$address->getCity().', '.$address->getPostcode().'<br>'.$this->getCountryname($address->getCountryId()).'<br>T: <a style="color: #1979c3 ; text-decoration: none">' .$address->getTelephone().'</a>';
				endif;
            }
			$address = $order->getBillingAddress();
                $street=$address->getStreet();
                $street1='';
                $street2='';
                $street3='';
                if (isset($street[0]) && $street[0] != '') {
                    $street1 = $street[0];
                }
                if (isset($street[1]) && $street[1] != '') {
                    $street2 = $street[1];
                }	
                if (isset($street[2]) && $street[2] != '') {
                    $street3 = $street[2];
                }
                if($street2!=''){
                    $street2 = '<br>'.$street2;
                }else{
                    $street2 = '';
                }
                if($street3!=''){
                    $street3 = '<br>'.$street3;
                }else{
                    $street3 = '';
                }
			$transport['formattedBillingAddress'] = $address->getFirstname().' '.$address->getLastname().'<br>'.$address->getCompany().'<br>'.$street1.$street2.$street3.'<br>'.$address->getCity().', '.$address->getPostcode().'<br>'.$this->getCountryname($address->getCountryId()).'<br>T: <a style="color: #1979c3 ; text-decoration: none">' .$address->getTelephone().'</a>';
			$transport['timezone'] = $this->_scopeConfig->getValue('general/locale/timezone', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $this->redingtonCheckoutHelper->debugLog('SUCCESS:(AddExtraTransportData) Forwarder Address id  : ' . $forwarderAddressId, false);
        } catch (\Exception $e) {
            $this->redingtonCheckoutHelper->debugLog('ERROR:(AddExtraTransportData) During add forwarder information in email content: ' . $e->getMessage(), false);
        }

    }
	
	 public function getCountryname($countryCode){    
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }
	
	public function getDefaultShippingAddressId(){
		  $custId = $this->customerSession->getCustomer()->getId();
		  $customer = $this->customerFactory->create()->load($custId);
		 return $shippingAddressId = $customer->getDefaultShipping();
	}
}