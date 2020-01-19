<?php

namespace Redington\AddressApproval\Controller\Address;

class DefaultUpdate extends \Magento\Framework\App\Action\Action
{
    protected $_customerFactory;
    protected $_addressFactory;
    private $customerAddressMapper;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Company\Model\CompanyFactory $companyFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_customerFactory = $customerFactory;
        $this->_addressFactory = $addressFactory;
        $this->_formKeyValidator = $formKeyValidator;
        $this->request = $request;
        $this->_coreRegistry = $registry;
        $this->customerSession = $customerSession;
        $this->_addressRepository = $addressRepository;
        $this->resultJsonFactory = $jsonFactory;
        $this->companyHelper = $companyHelper;
        $this->countryFactory =$countryFactory;
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->companyUser = $companyUser;
        $this->companyFactory = $companyFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('address_book');
        $resultJson = $this->resultJsonFactory->create();
        $customer = $this->_customerFactory->create()->load($this->customerSession->getCustomer()->getId());
        $billingAddress = $this->_addressFactory->create()->load($this->request->getParam('id'));

        $postTelephone = $billingAddress->getTelephone();
        $postCountryId = $billingAddress->getCountryId();
        $companyId = $this->companyUser->getCurrentCompanyId();

        $companyName = $this->companyFactory->create()->load($companyId)->getData('company_name');
        $this->customerSession->setIsForwarderRequest('0');
        $this->customerSession->setAddressRequestId($this->request->getParam('id'));
        $this->setAddressApprovalData($billingAddress);

        $customer = $billingAddress->getCustomer();
        $defaultBilling = $customer->getDefaultBillingAddress();
        $defaultShipping =  $customer->getDefaultShippingAddress();

        if ($defaultBilling) {
            if ($defaultBilling->getId() == $this->request->getParam('id')) {
                $this->customerSession->setAddressType('billing');
            }
        }
        if ($defaultShipping) {
            if ($defaultShipping->getId() == $this->request->getParam('id')) {
                $this->customerSession->setAddressType('shipping');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('Update Address');

        return $resultPage;
    }

    public function setAddressApprovalData($address)
    {
        $addressApproval = $this->addressApprovalFactory->create();
        $customer = $this->_customerFactory->create()->load($this->customerSession->getCustomer()->getId());
        $address = $this->_addressFactory->create()->load($this->request->getParam('id'));
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id', $this->request->getParam('id'));
        if ($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
        }

        $customer = $address->getCustomer();
        $defaultBilling = $customer->getDefaultBillingAddress();
        $defaultShipping =  $customer->getDefaultShippingAddress();

        $addressId =  $this->request->getParam('id');
        $street = $address->getStreet();
        $city = $address->getCity();
        $parentId = $this->companyHelper->getCompanyAdminId();
        $requestedBy = $this->customerSession->getCustomer()->getId();
        $phone = $address->getTelephone();
        $postCode = $address->getPostCode();
        $region = $address->getRegionId();
        $countryId = $address->getCountryId();
        $countryName = $this->countryFactory->create()->loadByCode($countryId)->getName();
        $companyId = $this->companyUser->getCurrentCompanyId();
        $companyName = $address->getCompany();

        $addressString = $city . ',\\n' . $region . ',' . $postCode . '\\n' . $countryName;

        //        Save Data in Address Approval Table
        $addressApproval->setAddressId($this->request->getParam('id'));
        $addressApproval->setCompanyId($companyId);
        $addressApproval->setParentId($parentId);
        $addressApproval->setRequestedBy($requestedBy);
        $addressApproval->setCompanyName($companyName);
        $addressApproval->setPhone($phone);
        $addressApproval->setCity($city);
        $addressApproval->setPostcode($postCode);
        $addressApproval->setCountry($countryName);
        $addressApproval->setAddress($addressString);
        // $addressApproval->setStatus(100);

        $requestType;
        if ($defaultBilling) {
            if ($defaultBilling->getId() == $this->request->getParam('id')) {
                $addressApproval->setRequestType("DefaultBilling");
                $requestType = "Default Billing";
            }
        }
        if ($defaultShipping) {
            if ($defaultShipping->getId() == $this->request->getParam('id')) {
                $addressApproval->setRequestType("DefaultShipping");
                $requestType = "Default Shipping";
            }
        }

        $addressApproval->save();
        $this->customerSession->setApprovalRequestId($addressApproval->getEntityId());
        $this->customerSession->setIsForwarderRequest(false);
        // $this->approvalHeler->sendApprovalRequestMail('Shipping Address',$address,$parentId,$requestedBy);

        return $companyName;
    }
}
