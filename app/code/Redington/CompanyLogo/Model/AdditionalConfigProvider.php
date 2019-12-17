<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */

namespace Redington\CompanyLogo\Model;

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
     * @var \Redington\Checkout\Helper\Data
     */
    protected $redingtonCheckoutHelper;

    /**
     * @var \Redington\CompanyLogo\Model\CompanyLogo
     */
    protected $companyLogo;

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     * @param \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
     * @param \Redington\Company\Helper\Data $companyHelper,
     * @param \Magento\Company\Model\CompanyFactory $companyFactory,
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
     * @param \Redington\CompanyLogo\Model\CompanyLogoFactory $companyLogo
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date,
     * @param \Magento\CompanyPayment\Model\CompanyPaymentMethod $companyPaymentMethod
     */
    public function __construct(
        \Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Redington\Checkout\Helper\Data $redingtonCheckoutHelper,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Redington\CompanyLogo\Model\CompanyLogoFactory $companyLogo,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Magento\CompanyPayment\Model\CompanyPaymentMethod $companyPaymentMethod
    ) {
        $this->defaultConfigProvider = $defaultConfigProvider;
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->companyHelper = $companyHelper;
        $this->companyFactory = $companyFactory;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->companyLogo = $companyLogo;
        $this->date = $date;
		$this->companyPaymentMethod = $companyPaymentMethod;
    }
    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn()
    {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
    /**
     * Set additional customer address data
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */

    /**
     * Default function
     */
    public function getConfig()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_payment.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('called get config');
		$bankdata=[];
        try {
                $companyAdminId = $this->companyHelper->getCompanyAdminId();
                $companyAdmin = $this->customerRepository->getById($companyAdminId);
                $websiteId = $companyAdmin->getWebsiteId();
                $companyId = $this->companyHelper->retrieveCompanyId();
                $company = $this->companyFactory->create()->load($companyId);
                $countryId = $company->getCountryId();
                $sapcode = $companyAdmin->getCustomAttribute('z_sap_code')->getValue();
				
				$bankdata['accountName'] = '';
				$bankdata['bankTransfer'] ='';
                $bankdetails = $this->companyLogo->create()->getCollection() 
                                ->addFieldToFilter('company_code',array('eq' => $sapcode));
                $data = $bankdetails->getData();
				
				if(sizeof($data) > 0){
					$accountName = '';
					$bankTransfer = '';
					foreach($data as $bankdata) {
						if(!empty($bankdata)){
							$accountName = $bankdata['accountname']; 
							$bankTransfer =  $bankdata['onlinetransfer'];
						}
					}
					   if(isset($accountName) && $accountName!=''){
							$bankdata['accountName'] = $accountName;
					   }else{
						   $bankdata['accountName'] =  '';
					   }
					   if(isset($bankTransfer) && $bankTransfer!=''){
							$bankdata['bankTransfer'] = html_entity_decode($bankTransfer);
					   }else{
						   $bankdata['bankTransfer'] = '';
					   }
					  
                }
               return $bankdata;  
        		
            } catch (\Exception $e) {
                $logger->info($e->getMessage());
            }
			
    }

}
