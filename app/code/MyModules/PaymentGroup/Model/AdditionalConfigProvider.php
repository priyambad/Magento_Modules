<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\PaymentGroup\Model;

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
     * @var \Redington\PaymentGroup\Model\Group
     */
    protected $paymentGroup;

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     * @param \Redington\PaymentGroup\Model\Group $paymentGroup
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
        \Redington\PaymentGroup\Model\GroupFactory $paymentGroup,
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
        $this->paymentGroup = $paymentGroup;
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

        $paymentGroupData = [];
        $paymentTerm = [];
        $companyAdminId = $this->companyHelper->getCompanyAdminId();
        $companyAdmin = $this->customerRepository->getById($companyAdminId);
        $websiteId = $companyAdmin->getWebsiteId();
        $companyId = $this->companyHelper->retrieveCompanyId();
        $company = $this->companyFactory->create()->load($companyId);
        $countryId = $company->getCountryId();

        $collection = $this->paymentGroup->create()->getCollection()
            ->addFieldToFilter('partner_id', array('eq' => $companyId));
        $data = $collection->getData();
        try {
            foreach($data as $value) :
                $paymentTermCode = $value['sap_ref_code'];
                $paymentTerm = explode(',', $paymentTermCode);
            endforeach;

            if (in_array('R128', $paymentTerm)) {
                $date = $this->date->gmtDate();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 8 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 7 days'));
                }
                $paymentGroupData['PdcTerm'] = 'Please prepare and upload PDC with date ' . $date.' or before.';
            }
            if (in_array('R014', $paymentTerm)) {
                $date = $this->date->gmtDate();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 30 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 29 days'));
                }
                $paymentGroupData['PdcTerm'] = 'Please prepare and upload PDC with date ' . $date.' or before.';
            }
            if (in_array('R002', $paymentTerm)) {
                /*$date = $this->date->gmtDate();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 7 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 6 days'));
                }*/
                $paymentGroupData['creditTerm'] = 'Please ensure that order payment is done within 7 days from date of invoice.';
            }
            if (in_array('R003', $paymentTerm)) {
                /*$date = $this->date->gmtDate();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 15 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 14 days'));
                }*/
                $paymentGroupData['creditTerm'] = 'Please ensure that order payment is done within 15 days from date of invoice.';
            }
            if (in_array('R006', $paymentTerm)) {
               /* $date = $this->date->gmtDate();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 30 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 29 days'));
                }*/
                $paymentGroupData['creditTerm'] = 'Please ensure that order payment is done within 30 days from date of invoice.';
            }
            if (in_array('R129', $paymentTerm)) {
                /*$date = $this->date->gmtDate();
                if (time() < strtotime('12 pm')) {
                    $date = date('d-m-Y', strtotime($date . ' + 8 days'));
                } else {
                    $date = date('d-m-Y', strtotime($date . ' + 7 days'));
                }*/
                $paymentGroupData['creditTerm'] = 'Please ensure that order payment is done within 8 days from date of invoice.';
            }
			$paymentMethod = array();
			$availableMethods = $this->companyPaymentMethod->load($companyId);
			$paymentMethods = $availableMethods->getAvailablePaymentMethods();
			$paymentMethod = explode(',', $paymentMethods);
			if(in_array('companycredit',$paymentMethod) || in_array('pdc',$paymentMethod)){
			
				$paymentGroupData['avilablepaymentMethod'] = 'true';
				
			}else{
				$paymentGroupData['avilablepaymentMethod'] = 'false';
			}	
        } catch (\Exception $e) {

        }
		
		
		
        return $paymentGroupData;
    }

}
