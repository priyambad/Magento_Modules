<?php

namespace Redington\AddressApproval\Block\Customer\Address;

class DefaultUpdate extends \Magento\Customer\Block\Address\Edit {
     protected $_customerFactory;
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Customer\Model\CustomerFactory $customerFactory,
           
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
            \Redington\Company\Helper\Data $helperData,
             \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
            array $data = array()) {
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->_customerFactory = $customerFactory;
        $this->companyHelper = $companyHelper;
        $this->serialize = $serialize;
        $this->scopeConfig = $scopeConfig;
        $this->helerData = $helperData;
        $this->customerSession = $customerSession;
         $this->documentCollectionFactory = $documentCollectionFactory;
        $this->customerRepository = $customerRepository;
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
      
        try{
            $addressId = $this->getAddress()->getId();
            $addressApprovalData = $this->getAddressApproval($addressId);
            $documentData = $addressApprovalData->getPendingDocuments();
            if($documentData)
                return $this->serialize->unserialize($documentData);
            else
                return false;
        }catch(\Exception $e){
            return false;
        }
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
        public function getCustomer()
    {
        $customer = $this->getData('customer');
        if ($customer === null) {
            $adminUserId = $this->helerData->getCompanyAdminId();
            try {
                $customer = $this->customerRepository->getById($adminUserId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->redingtonHelper->logMessage('error in Redington\AddressApproval\Block\Customer\Address\Book '.$e->getMessage());
                return null;
            }
            $this->setData('customer', $customer);
        }
        return $customer;
    }
    /**
     * getAdditionalAddresses function
     *
     * @return address
     */
    public function getAdditionalAddresses()
    {
        $adminUserId = $this->helerData->getCompanyAdminId();
        try {
            $addresses = $this->customerRepository->getById($adminUserId)->getAddresses();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->redingtonHelper->logMessage('error in Redington\AddressApproval\Block\Customer\Address\Book '.$e->getMessage());
            return false;
        }
        $primaryAddressIds = [$this->getDefaultBilling(), $this->getDefaultShipping()];
        foreach ($addresses as $address) {
            if (!in_array($address->getId(), $primaryAddressIds)) {
                $additional[] = $address;
            }
        }
        return empty($additional) ? false : $additional;
    }
   /**
    * getRequestedAddressesIds function
    * this function returns requested addersses.
    * @return address
    */
    public function getRequestedAddressesIds() {
        $addressApproval = $this->addressApprovalFactory->create();
        $requestedAddress = $addressApproval->getCollection()
            ->addFieldToFilter('parent_id',$this->helerData->getCompanyAdminId())
            ->addFieldToFilter('status', array('neq' => 2));
        return $requestedAddress;
    }
    /**
     * getAddressObject function
     *
     * @param [type] $addressId
     * this function returns address object from address id
     * @return void
     */
    public function getAddressObject($addressId) {
        try{
            $address = $this->addressRepository->getById($addressId);
            return $address;
        }catch(\Exception $e){
            $this->redingtonHelper->logMessage('error in Redington\AddressApproval\Block\Customer\Address\Book '.$e->getMessage());
        }
    }
    /**
     * getStatus function
     *
     * @param [type] $num
     * this function returns status against value stored in database.
     * @return void
     */
    public function getStatus($num){
        if($num == 1)
            return 'Rejected';
        if($num == 0)
            return 'Pending';
        if($num == 100)
            return 'Incomplete';
    }
    /**
     * isAddressApproved function
     *
     * @param [type] $addressId
     * function to check if a address has been approved.
     * @return boolean
     */
    public function isAddressApproved($addressId) {
        $address = $this->getAddressObject($addressId);
        //return $address->getCustomAttributes();
        if($address->getCustomAttribute('approved') == NULL){
            return false;
        }else{
            if($address->getCustomAttribute('approved')->getValue()==0){
                return false;
            }else{
                if($address->getCustomAttribute('is_forwarder') !== NULL && $address->getCustomAttribute('is_forwarder')->getValue() ==1) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * isAddressValid function
     *
     * @param [type] $addressId
     * this function checks if trade license for adderss is valid or not
     * @return boolean
     */
    public function isAddressValid($addressId) {
        try{
            $address = $this->getAddressObject($addressId);
            //return $address->getCustomAttributes();
            if($address->getCustomAttribute('is_valid')){
                return $address->getCustomAttribute('is_valid')->getValue();
            }else{
                return false;
            }
        }catch(\Exception $e){
            return false;
        }
    }
    /**
     * getAddressRequestId function
     *
     * @param [type] $addressId
     * this function returns id of given address
     * @return integer
     */
    public function getAddressRequestId($addressId){
        $addressCollection = $this->addressApprovalFactory->create()->getCollection()->addFieldToFilter('address_id',$addressId);
        return $addressCollection->getFirstItem()->getEntityId();
    }

    /**
     * getAddressRequestData function
     *
     * @param [type] $addressId
     * @return $approvalRequest
     */
    public function getAddressRequestData($addressId){
        try {
            $addressApproval = $this->addressApprovalFactory->create();
            $requestedAddress = $addressApproval->getCollection()
                ->addFieldToFilter('address_id',$addressId);
            return $requestedAddress->getFirstItem();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * getAddressDocuments function
     *
     * @param [type] $addressId
     * @return $addressDocuments
     */
    public function getAddressDocuments($addressId){
        try {
            $addressRequest = $this->getAddressRequestData($addressId);
            $documents = $addressRequest->getDocuments();
            return $this->serialize->unserialize($documents);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * getBillingDocuments function
     *
     * @return $documents
     */
    public function getBillingDocuments(){
        try {
            $companyId = $this->helerData->retrieveCompanyId();
            $defaultDcouments = $this->documentCollectionFactory->create()->addFieldToFilter('company_id', $companyId)->getFirstItem()->getDocumentDetails();
            return $this->serialize->unserialize($defaultDcouments)['billing'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * getShippingDocuments function
     *
     * @return $documents
     */
    public function getShippingDocuments(){
        try {
            $companyId = $this->helerData->retrieveCompanyId();
            $defaultDcouments = $this->documentCollectionFactory->create()->addFieldToFilter('company_id', $companyId)->getFirstItem()->getDocumentDetails();
            $defaultDcouments = $this->serialize->unserialize($defaultDcouments);
            if(count($defaultDcouments['shipping']) > 0){
                return $defaultDcouments['shipping'];
            }else{
                return $defaultDcouments['billing'];
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * getDefaultDocuments function
     *
     * @return $documents
     */
    public function getDefaultDocuments(){
        $documentData = $this->getDocuments();
        // echo '<pre>';print_r($documentData);exit;
        if($documentData){
            $documentData[0]['document_name'] = $documentData[0]['documentName'];
            $documentData[0]['document_number'] = $documentData[0]['documentNumber'];
            $documentData[0]['expiry_date'] = $documentData[0]['documentExpiry'];
            $documentData[0]['issue_date'] = $documentData[0]['documentIssue'];
            $documentData[0]['document_url'] = $documentData[0]['docUrl'];
        }
        else{
            $addressType = $this->customerSession->getAddressType();
            try{
                if($addressType == 'shipping'){
                    $documentData = $this->getShippingDocuments();
                }else{
                    if($addressType == 'billing'){
                        $documentData = $this->getBillingDocuments();
                    }else{
                        $documentData = false;
                    }
                }
            }catch(\Exception $e){
                $documentData = false;
            }
        }
       return $documentData;
    }
    /**
     * getDefaultAddressid function
     *
     * @return $addressId
     */
    public function getDefaultAddressid(){
        return $this->customerSession->getAddressRequestId();
    }
}