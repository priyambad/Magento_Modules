<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */
 
namespace Redington\Checkout\Model;

use Magento\Customer\Model\Context as CustomerContext;

class AdditionalConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

	/**
     * @var \Magento\Checkout\Model\DefaultConfigProvider
     */
	protected $defaultConfigProvider;
	
	/**
     * @var \Magento\Framework\App\Http\Context
     */
	protected $httpContext;
	
	/**
     * @var \Magento\Customer\Model\Session
     */
	protected $customerSession;
	
	/**
     * @var Magento\Customer\Api\CustomerRepositoryInterface
     */
	protected $customerRepository;

	/**
     * @var \Magento\Customer\Model\Address\Mapper
     */
	protected $addressMapper;
	
	/**
     * @var \Magento\Customer\Model\ResourceModel\Address\CollectionFactory
     */
	protected $addressesFactory;
	
	/**
     * @var \Magento\Customer\Model\Address\Config
     */
	protected $addressConfig;
	
	/**
     * @var \Redington\Checkout\Helper\Data
     */
	protected $redingtonCheckoutHelper;
	
    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
	 * @param \Magento\Customer\Model\Address\Mapper $addressMapper
	 * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory
	 * @param \Magento\Customer\Model\Address\Config $addressConfig
	 * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     */
    public function __construct(
		\Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider,
		\Magento\Framework\App\Http\Context $httpContext,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
		\Magento\Customer\Model\Address\Mapper $addressMapper,
		\Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory,
		\Magento\Customer\Model\Address\Config $addressConfig,
		\Redington\Checkout\Helper\Data $redingtonCheckoutHelper,
		\Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
		\Redington\Company\Helper\Data $companyHelper,
		\Magento\Company\Model\CompanyFactory $companyFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory
    ) {
		$this->defaultConfigProvider = $defaultConfigProvider;
		$this->httpContext = $httpContext;
		$this->customerSession = $customerSession;
		$this->customerRepository = $customerRepository; 
		$this->addressMapper = $addressMapper;
		$this->addressesFactory = $addressesFactory;
		$this->addressConfig = $addressConfig;
		$this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
		$this->sourceCollectionFactory = $sourceCollectionFactory;
		$this->companyHelper = $companyHelper;
		$this->companyFactory = $companyFactory;
		$this->_storeManager = $storeManager;
		$this->distributionCollectionFactory = $distributionCollectionFactory;
    }
	/**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
	/**
     * Set additional customer address data
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    private function getCustomerAddressInline($address)
    {
        return $this->addressConfig
			->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($address);
    }
	/**
     * Default function
     */
	public function getConfig()
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_warehouse.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info('called get config');


		$sourceData = [];

		$companyAdminId = $this->companyHelper->getCompanyAdminId();
		$companyAdmin = $this->customerRepository->getById($companyAdminId);
		$websiteId = $companyAdmin->getWebsiteId();
		$companyId = $this->companyHelper->retrieveCompanyId();
		$company = $this->companyFactory->create()->load($companyId);
		$countryId = $company->getCountryId();
		$sapAccountCode = $companyAdmin->getCustomAttribute('z_sap_code')->getValue();

		$logger->info('country id is '.$countryId);
		$logger->info('sap account code id is '.$sapAccountCode);
		$sourceCollection = $this->sourceCollectionFactory->create()
			->addFieldToFilter('enabled',1)
			->addFieldToFilter('distribution',$this->getDistributionChannel())
			->addFieldToFilter('country_id',$countryId)
			->addFieldToFilter('sap_account_code',$sapAccountCode);
		foreach($sourceCollection as $key => $source) {
			array_push($sourceData,$source->getData());
		}
		$customerForwarderAddressData = [];
		$customerForwarderAddressData['warehouse-list'] = $sourceData;
		
		try {
			if($this->isCustomerLoggedIn()){
				if($this->customerSession->getCustomerId()){
					$companyAdminId = $this->redingtonCheckoutHelper->getCompanyAdminId();
					$customer = $this->customerRepository->getById($companyAdminId);
					
					$defaultShippingAddress = $customer->getDefaultShipping();
					$customerForwarderAddressData['defaultShipping'] = $defaultShippingAddress;
					$forwarderAddressesCollection = $this->addressesFactory->create()
									->addAttributeToFilter('is_forwarder','1')
									->addAttributeToFilter('approved','1')
									->addFieldToFilter('is_valid',1)
									->setCustomerFilter($customer)
									->addAttributeToSelect('*');
					
					$customerForwarderAddressData['forwarderAddress'][0]['inline'] = 'Please select forwarder';
					$customerForwarderAddressData['forwarderAddress'][0]['id'] = 0;
					$customerForwarderAddressData['forwarderAddress'][0]['customer_id'] = $companyAdminId;
					$i = 1;
					if(sizeof($forwarderAddressesCollection) > 0) :
					foreach ($forwarderAddressesCollection->getItems() as $key => $address) {
						/*foreach($address->getData() as $subKey=>$val){
							$customerForwarderAddressData['forwarderAddress'][$i][$subKey] = $val;
						}*/
						$customerForwarderAddressData['forwarderAddress'][$i]['inline'] = $this->getCustomerAddressInline($address);
						$customerForwarderAddressData['forwarderAddress'][$i]['id'] = $key;
						$customerForwarderAddressData['forwarderAddress'][$i]['customer_id'] = $this->customerSession->getCustomerId();
						$i++;
					}
					else :
						$customerForwarderAddressData['forwarderAddress'] = 'N/A';
					endif;	
					//$customerForwarderAddressData = $forwarderAddressesCollection->getItems();
				}
			}
			$this->redingtonCheckoutHelper->debugLog('SUCCESS:(AdditionalConfigProvider) Set forwarder address in checkout config data is done.', false);			
		
		} catch (\Exception $e) {
			$this->redingtonCheckoutHelper->debugLog('ERROR:(AdditionalConfigProvider) Set forwarder address in checkout config data: '.$e->getMessage(), false);			
        }
		return $customerForwarderAddressData;
	}

	public function getDistributionChannel(){
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_warehouse.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info('called get distribution');

		$storeCode = $this->_storeManager->getGroup()->getCode();
		$logger->info('store code is '.$storeCode);
		$distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code',$storeCode)->getFirstItem();
		$logger->info('distribution channel is '.$distribution->getDistributionChannel());
		return $distribution->getDistributionChannel();
	}
}