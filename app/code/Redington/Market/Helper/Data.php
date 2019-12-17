<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Redington_Market
 */

namespace Redington\Market\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
	/**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
	
	/**
     * @var \Redington\Company\Helper\Data
     */
    protected $rednCompanyHelper;
    
	/**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
	
	/**
     * @var \Magento\CompanyCredit\Api\CreditDataProviderInterface
     */
    private $creditDataProvider;
	
	/**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceFormatter;
	
	/**
     * @var \Magento\CompanyCredit\Model\WebsiteCurrency
     */
    private $websiteCurrency;
	
	
	/**
     * @var \Magento\CompanyCredit\Model\CreditDetails\CustomerProvider
     */
    private $customerProvider;
	
	/**
     * @var \Magento\CompanyCredit\Api\Data\CreditDataInterface
     */
    private $credit;

	
    /**
     * 
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Redington\Company\Helper\Data $rednCompanyHelper
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider
	 * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
	 * @param \Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency
     */
    public function __construct(     
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session  $customerSession,
		\Redington\Company\Helper\Data $rednCompanyHelper,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
		\Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
		\Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency,
		\Magento\CompanyCredit\Model\CreditDetails\CustomerProvider $customerProvider
    ) {
		$this->scopeConfig = $scopeConfig; 
        $this->customerSession = $customerSession;  
		$this->rednCompanyHelper = $rednCompanyHelper;
		$this->storeManager = $storeManager;
		$this->creditDataProvider = $creditDataProvider;
		$this->priceFormatter = $priceFormatter;
		$this->websiteCurrency = $websiteCurrency;
		$this->customerProvider = $customerProvider;
    }
	  /**
     * Get scope config value by path, scope type and code
     * 
     * @param type $path
     * @param type $scopeType
     * @param type $scopeCode
     * return string | int 
     */
    public function getConfig($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null) {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }
	/**
     * For Debug
     */
	public function debugLog($logInfo, $fileName) {
		if(!$fileName){
			$filePath = '/var/log/Redington_Design_Modify.log';
		}else{
			$filePath = '/var/log/'.$fileName;
		}
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info($logInfo);
    }
	/**
     * Get logged in user company Admin Id
     */
	public function getCompanyAdminId(){
		return $this->rednCompanyHelper->getCompanyAdminId();
	}
	
	/**
     * Is customer login
     */
	public function isCustomerLogin(){
		return $this->customerSession->isLoggedIn();
	}
	/**
     * @param string $customerMobile
     * Send OTP in sms : call API
     */
	public function callAPI($url){

		//== START CURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);

		$val =	'';
		$data = array("payload"=>$val);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return $output;
	}
	/**
     * Get credit balance
     */
	public function getCreditBalanceFromSAP(){
		$availableCreditApiUrl = $this->getConfig('redington_site_config/general/available_credit_api_url','default','');
		if($availableCreditApiUrl == ""){
			$url = "https://prod-33.westeurope.logic.azure.com:443/workflows/34e4c19da4ce442785d184a12d72f9ae/triggers/manual/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=7IAqTnUIRRy-wQoABbgOtsfbpFJk2UjPCYnn1TjDjlk";
		}
		else{
			$url = $availableCreditApiUrl;
		}
		
		$output = '';
		try{
			$output = $this->callAPI($url);
			$this->debugLog('Success (Redington_Market_Helper_Data): Credit Balance Val: '.$output, false);
			
		} catch (\Exception $e) {
			$this->debugLog('Error (Redington_Market_Helper_Data): Get Credit Balance: '.$e->getMessage(), false);
        }
		if($output == "")
			$output = '0.00';
		$output = $this->storeManager->getStore()->getCurrentCurrency()->getCode()." ".$output;
		return $output;
	}
	/**
     * Get credit balance
     */
	public function getCreditBalanceFromMarketplace(){
		$availableCredit = 0;
		
		try{			
			//$creditBalance = $this->layoutFactory->create()->createBlock('Magento\CompanyCredit\Block\Company\CreditBalance');
			$availableCredit = $this->getAvailableCredit();		
			$this->debugLog('SUCCESS (Redington_Market_Helper_Data-getCreditBalanceFromMarketplace): Credit Balance: '.$availableCredit, false);
		} catch (\Exception $e) {
			$this->debugLog('Error (Redington_Market_Helper_Data-getCreditBalanceFromMarketplace): '.$e->getMessage(), false);
        }
		return $availableCredit;
	}
	/**
     * Get available credit.
     *
     * @return float
     */
    public function getAvailableCredit()
    {
        $creditAvailableLimit = $this->getCredit() ? $this->getCredit()->getAvailableLimit() : 0;
		$this->debugLog('SUCCESS (Redington_Market_Helper_Data-getAvailableCredit): creditAvailableLimit: '.$creditAvailableLimit, false);
        
		return $this->priceFormatter->format(
            $creditAvailableLimit,
            false,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $this->getCreditCurrency()
        );
    }
    /**
     * Get available credit Limit
     *
     * @return void
     */
    public function getCreditLimit(){
        $creditAvailableLimit = $this->getCredit() ? $this->getCredit()->getAvailableLimit() : 0;
        return $creditAvailableLimit;
    }
	/**
     * Get credit object.
     *
     * @return \Magento\CompanyCredit\Api\Data\CreditDataInterface|null
     */
    private function getCredit()
    {
		$this->debugLog('Enter getCredit', false);
		if ($this->credit === null && $this->customerProvider->getCurrentUserCredit()) {
		
            $companyId = $this->customerProvider->getCurrentUserCredit()->getCompanyId();
			$this->debugLog('SUCCESS (Redington_Market_Helper_Data-getCredit): Company Id: '.$companyId, false);
            $this->credit = $this->creditDataProvider->get($companyId);
        }
		//$this->credit = $this->creditDataProvider->get('1');
        return $this->credit;
    }
	
    /**
     * Get credit currency.
     *
     * @return \Magento\Directory\Model\Currency
     */
    private function getCreditCurrency()
    {
        $creditCurrencyCode = null;
        if ($this->getCredit()) {
            $creditCurrencyCode = $this->getCredit()->getCurrencyCode();
        }
        return $this->websiteCurrency->getCurrencyByCode($creditCurrencyCode);
    }
}