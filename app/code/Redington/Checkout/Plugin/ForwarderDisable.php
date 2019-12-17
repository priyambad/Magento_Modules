<?php

namespace Redington\Checkout\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Class SidebarDisable
 *
 * @package Vendorname\SidebarActivation\Plugin
 */
class ForwarderDisable {

	/**
     * @var \Magento\Customer\Model\Session
     */
	protected $customerSession;
	
	/**
     * @var Magento\Customer\Api\CustomerRepositoryInterface
     */
	protected $customerRepository;

	
	/**
     * @var \Magento\Customer\Model\ResourceModel\Address\CollectionFactory
     */
	protected $addressesFactory;
	
	
	/**
     * @var \Redington\Checkout\Helper\Data
     */
	protected $redingtonCheckoutHelper;
	
    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider
     * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
	 * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory
	 * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     */
    public function __construct(
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
		\Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory,
		\Redington\Checkout\Helper\Data $redingtonCheckoutHelper,
		\Redington\Company\Helper\Data $companyHelper,
		\Magento\Company\Model\CompanyFactory $companyFactory
    ) {
		$this->customerSession = $customerSession;
		$this->customerRepository = $customerRepository; 
		$this->addressesFactory = $addressesFactory;
		$this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
		$this->companyHelper = $companyHelper;
		$this->companyFactory = $companyFactory;
	}

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $processor
     * @param array $jsLayout
     *
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $processor,
        array $jsLayout
    ){
		
        $companyAdminId = $this->companyHelper->getCompanyAdminId();
		$companyAdmin = $this->customerRepository->getById($companyAdminId);
		$websiteId = $companyAdmin->getWebsiteId();
		$companyId = $this->companyHelper->retrieveCompanyId();
		$company = $this->companyFactory->create()->load($companyId); 
		if($this->customerSession->getCustomerId()){
					$companyAdminId = $this->redingtonCheckoutHelper->getCompanyAdminId();
					$customer = $this->customerRepository->getById($companyAdminId);
					$forwarderAddressesCollection = $this->addressesFactory->create()
									->addAttributeToFilter('is_forwarder','1')
									->addAttributeToFilter('approved','1')
									->addFieldToFilter('is_valid',1)
									->setCustomerFilter($customer)
									->addAttributeToSelect('*');
					
					if(sizeof($forwarderAddressesCollection) == 0) {
						$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
						['children']['shippingAddress']['children']['after-shipping-address-list']['children']['forwarder-section']['componentDisabled'] = true;
					}
		}
		 
        return $jsLayout;
    }
}