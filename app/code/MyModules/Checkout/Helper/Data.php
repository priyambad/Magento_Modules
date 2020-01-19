<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{ 
    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
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
     * @var \Redington\Company\Helper\Data
     */
    protected $rednCompanyHelper;

    
    /**
     * 
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Redington\Company\Helper\Data $rednCompanyHelper
	 * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(        
        \Magento\Customer\Model\SessionFactory  $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Redington\Company\Helper\Data $rednCompanyHelper,
        \Amasty\Checkout\Model\AdditionalFields $additionalFields,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Redington\CompanyLogo\Model\CompanyLogoFactory $companyLogoFactory        
    ) {
        $this->customerSession = $customerSession;  
        $this->rednCompanyHelper = $rednCompanyHelper; 
        $this->addressRepository = $addressRepository;
        $this->addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
        $this->additionalFields = $additionalFields;
		$this->customerFactory = $customerFactory;
        $this->companyLogoFactory = $companyLogoFactory;
    }
    /**
	 * Logger Function
	 *
	 * @var string $logInfo
	 * @var string $fileName
	 * @return array
	 */
    public function debugLog($logInfo, $fileName) {
        if(!$fileName){
            $filePath = '/var/log/Redington_Checkout.log';
        }else{
            $filePath = '/var/log/'.$fileName;
        }
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($logInfo);
    }
	/**
	 * Retrieve company Id
	 * @return integer
	 */
    public function getCompanyAdminId(){
		try{
        return $this->rednCompanyHelper->getCompanyAdminId();
		}catch (\Exception $e){
			$this->debugLog($e->getMessage(),'Redington_Company_Data.log');
		}
    }
    /**
     * Retrieve Forwarder Address 
     *
     * @return array
     */
    public function getForwarderAddress($addressId)
    {
		try{
        $addressObject = $this->addressRepository->getById($addressId);
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($this->addressMapper->toFlatArray($addressObject));
		}catch (\Exception $e){
			 $this->debugLog($e->getMessage(),'Redington_Company_Data.log');
		}
    }
    
    /**
     * Retrieve Extra Info Of Order
     *
     * @return array
     */
    public function getOrderAdditionalInfo($quoteId)
    {
		try{
        $additinalInfo = $this->additionalFields->findByField($quoteId,'quote_id');
        $resultData = $additinalInfo->getData();
        return $resultData;
		}catch (\Exception $e){
			$this->debugLog($e->getMessage(),'Redington_Company_Data.log');
		}
    }
	/**
     * Retrieve company code
     *
     * @return integer
     */
	public function getCompanyCode(){
		try{
		$companyId = $this->getCompanyAdminId();
		$customer = $this->customerFactory->create()->load($companyId);
		return $customer->getZSapCode();
		}catch (\Exception $e){
			$this->debugLog($e->getMessage(),'Redington_Company_Data.log');
		}
		
	}
    /**
     * Retrieve company logo data
     *
	 * @var int $companyCode
     * @return array
     */    
	public function getCompanyLogoData($companyCode){
		try{
			if($companyCode) {
				$companyCollection = $this->companyLogoFactory->create()->getCollection()->addFieldToFilter('company_code',$companyCode);
			} else {                
				$companyCollection = $this->companyLogoFactory->create()->getCollection();
			}        
			return $companyCollection;
		}catch (\Exception $e){
			$this->debugLog($e->getMessage(),'Redington_Company_Data.log');
		}
	}
	public function getLpoNumber($quoteId){
		
		try{
        $additinalInfo = $this->additionalFields->findByField($quoteId,'quote_id');
        $resultData = $additinalInfo->getData();
		$lpodocument = 	$resultData['lpo_reference_document'];
		if(isset($resultData['lponumber']) && $resultData['lponumber']!=''){
        return '<a href="'.$lpodocument.'" target="_blank">'.$resultData['lponumber'].'</a>';
		}else{
			return 'N/A';
		}
		}catch (\Exception $e){
			$this->debugLog($e->getMessage(),'Redington_Order_Data.log');
		}
		
	}	
}
