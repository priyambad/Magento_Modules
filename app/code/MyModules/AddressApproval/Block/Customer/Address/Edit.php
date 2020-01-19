<?php

namespace Redington\AddressApproval\Block\Customer\Address;

class Edit extends \Magento\Customer\Block\Address\Edit {
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Directory\Helper\Data $directoryHelper,
            \Magento\Framework\Json\EncoderInterface $jsonEncoder,
            \Magento\Framework\App\Cache\Type\Config $configCacheType,
            \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
            \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
            \Magento\Customer\Model\Session $customerSession,
            \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
            \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
            \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
            \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
            \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
            \Magento\Framework\Serialize\Serializer\Serialize $serialize,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Redington\Company\Helper\Data $companyHelper,
            array $data = array()) {
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->companyHelper = $companyHelper;
        $this->serialize = $serialize;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionCollectionFactory, $countryCollectionFactory, $customerSession, $addressRepository, $addressDataFactory, $currentCustomer, $dataObjectHelper, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->initAddressObject();

        $this->pageConfig->getTitle()->set($this->getTitle());

        if ($postedData = $this->_customerSession->getAddressFormData(true)) {
            $postedData['region'] = [
                'region_id' => isset($postedData['region_id']) ? $postedData['region_id'] : null,
                'region' => $postedData['region'],
            ];
            $this->dataObjectHelper->populateWithArray(
                $this->_address,
                $postedData,
                \Magento\Customer\Api\Data\AddressInterface::class
            );
        }
        $this->precheckRequiredAttributes();
        return $this;
    }

    private function precheckRequiredAttributes()
    {
        $precheckAttributes = $this->getData('check_attributes_on_render');
        $requiredAttributesPrechecked = [];
        if (!empty($precheckAttributes) && is_array($precheckAttributes)) {
            foreach ($precheckAttributes as $attributeCode) {
                $attributeMetadata = $this->addressMetadata->getAttributeMetadata($attributeCode);
                if ($attributeMetadata && $attributeMetadata->isRequired()) {
                    $requiredAttributesPrechecked[$attributeCode] = $attributeCode;
                }
            }
        }
        $this->setData('required_attributes_prechecked', $requiredAttributesPrechecked);
    }

    private function initAddressObject()
    {
        // Init address object
        if ($addressId = $this->getRequest()->getParam('id')) {
            try {
                $this->_address = $this->_addressRepository->getById($addressId);
                if ($this->_address->getCustomerId() != $this->companyHelper->getCompanyAdminId()) {
                    $this->_address = null;
                }
            } catch (NoSuchEntityException $e) {
                $this->_address = null;
            }
        }

        if ($this->_address === null || !$this->_address->getId()) {
            $this->_address = $this->addressDataFactory->create();
            $customer = $this->getCustomer();
            $this->_address->setPrefix($customer->getPrefix());
            $this->_address->setFirstname($customer->getFirstname());
            $this->_address->setMiddlename($customer->getMiddlename());
            $this->_address->setLastname($customer->getLastname());
            $this->_address->setSuffix($customer->getSuffix());
        }
    }

    public function getAddressApproval($addressId) {
        $addressApproval = $this->addressApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$addressId);
        return $existInQueue->getFirstItem();
    }
    public function getDocuments() {
      
        $addressId = $this->getAddress()->getId();
        $addressApprovalData = $this->getAddressApproval($addressId);
        $documentData = $addressApprovalData->getPendingDocuments();
        if($documentData)
            return $this->serialize->unserialize($documentData);
        else
            return false;
    }
    public function getCodeFromDocumentName($name) {
        $nameArray = explode(' ', $name);
        $code = $nameArray[0][0];
        for ($i = 1; $i < sizeof($nameArray); $i++) {
            $code .= '_' . $nameArray[$i][0];
        }
        return strtolower($code);
    }
    function getAddressData() {
        $addressData = [
            'firstname' => '',
            'lastname' => '',
            'company' => '',
            'telephone' => '',
            'street1' => '',
            'street2' => '',
            'city' => '',
            'postcode' => '',
            'country_id' => ''
        ];
        $address = $this->getAddress();
        if($address){
            $addressData['firstname'] = $address->getFirstName();
            $addressData['lastname'] = $address->getLastName();
            $addressData['company'] = $address->getCompany();
            $addressData['telephone'] = $this->getTelephone($address->getTelephone(),$address->getCountryId());
            $addressData['street1'] = $address->getStreet()[0];
            $addressData['street2'] = $address->getStreet()!= null ? (sizeof($address->getStreet()) > 1 ? $address->getStreet()[1] :'' ) : '';
            $addressData['city'] = $address->getCity();
            $addressData['postcode'] = $address->getPostcode();
            $addressData['country_id'] = $address->getCountryId(); 
        }
        return $addressData;
    }
    public function getTelephone($telephone,$countryId){
        switch ($countryId) {
            case "AE":
            case "KE":
                return substr($telephone, 4);
        }
    }
    public function getDocumentsApi() {
        return $this->scopeConfig->getValue('redington_documents/general/document_fetch_api');
    }
    public function getTelephonePattern(){
        return $this->scopeConfig->getValue('redington_approval/general/phone_regex');
    }
    public function getPostalPattern(){
        $postalData =  $this->scopeConfig->getValue('redington_approval/general/postal_regex');
        return $postalData;
    }

    public function getRequestId(){
            $addressId = $this->getAddress()->getId();
            $addressApprovalData = $this->getAddressApproval($addressId);
            $requestId = $addressApprovalData->getEntityId();
            return $requestId;
    }
}