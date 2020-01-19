<?php

namespace Redington\AddressApproval\Block\Customer\Address;

class Book extends \Magento\Customer\Block\Address\Book {
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
            \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
            \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
            \Magento\Customer\Model\Address\Config $addressConfig,
            \Magento\Customer\Model\Address\Mapper $addressMapper,
            \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
            \Redington\Company\Helper\Data $helperData,
            \Redington\AddressApproval\Helper\Data $redingtonHelper,
            \Magento\Framework\Serialize\Serializer\Serialize $serialize,
            \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory,
			\Magento\Directory\Model\CountryFactory $countryFactory,
            array $data = array()) {
        $this->customerRepository = $customerRepository;
        $this->helerData = $helperData;
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->redingtonHelper = $redingtonHelper;
        $this->serialize = $serialize;
        $this->addressRepository = $addressRepository;
        $this->documentCollectionFactory = $documentCollectionFactory;
        $this->currentCustomer = $currentCustomer;
		$this->_countryFactory = $countryFactory;
        parent::__construct($context, $customerRepository, $addressRepository, $currentCustomer, $addressConfig, $addressMapper, $data);
    }
    /**
     * getCustomer function
     *
     * @return customer
     */
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
            ->addFieldToFilter('status', array('neq' => 2))
            ->addFieldToFilter('request_type', array('eq' => 'Shipping'));
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
        try{
            $addressCollection = $this->addressApprovalFactory->create()->getCollection()->addFieldToFilter('address_id',$addressId);
            return $addressCollection->getFirstItem()->getEntityId();
        }catch(\Exception $e){
            return false;
        }
    }

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
     * @return $documentData
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
     * getRequestedAddressDocuments function
     *
     * @param [type] $addressId
     * @return $documentData
     */
    public function getRequestedAddressDocuments($addressId){
        try {
            $addressRequest = $this->getAddressRequestData($addressId);
            $documents = $addressRequest->getPendingDocuments();
            return $this->serialize->unserialize($documents);
        } catch (\Exception $e) {
            return false;
        }
    }

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
     * getShippingDocuments
     *
     * @return documents
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
     * areDefaultAddressesSame function
     *
     * @return Boolean
     */
    public function areDefaultAddressesSame(){
        try{
            $billingAddressSapIdObj = $this->addressRepository->getById($this->getDefaultBilling())->getCustomAttribute('sap_address_id');
            $billingAddressSapId = $billingAddressSapIdObj ? $billingAddressSapIdObj->getValue() : false;
            $shippingAddressSapIdObj = $this->addressRepository->getById($this->getDefaultShipping())->getCustomAttribute('sap_address_id');
            $shippingAddressSapId = $shippingAddressSapIdObj ? $shippingAddressSapIdObj->getValue() : false;
            if($shippingAddressSapId == $billingAddressSapId) {
                return true;
            }else{
                return false;
            }
        }catch(\Exception $e) {
            return false;
        }
    }
    /**
     * getDefaultAddressStatus function
     *
     * @param [type] $addressId
     * @return $addressStatus
     */
    public function getDefaultAddressStatus($addressId){
        try{
            $addressCollection = $this->addressApprovalFactory->create()->getCollection()->addFieldToFilter('address_id',$addressId);
            $status = $addressCollection->getFirstItem()->getStatus();
            return $status;
        }catch(\Exception $e){
            return null;
        }
    }

	public function getCountryname($countryCode){    
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    public function getPendingDocuments($addressId){
        $pendingDocuments = [];

        try{
            $addressCollection = $this->addressApprovalFactory->create()->getCollection()->addFieldToFilter('address_id',$addressId);
            $status = $addressCollection->getFirstItem()->getStatus();
            if($status === '2'){
                return false;
            }else{
                $documents = $addressCollection->getFirstItem()->getPendingDocuments();

                $documents = $this->serialize->unserialize($documents);
                foreach ($documents as $key => $document) {
                    $docData = [
                        'document_name' => $document['documentName'],
                        'document_number' => $document['documentNumber'],
                        'expiry_date' => $document['documentExpiry'],
                        'issue_date' => $document['documentIssue'],
                        'document_url' => $document['docUrl']
                    ];
                    array_push($pendingDocuments, $docData);
                }
                return $pendingDocuments;
            }
        }catch(\Exception $e){
            // echo $e->getMessage();exit;
            return false;
        }
    }

    /**
     * getRequestedId function
     *
     * @param [type] $addressId
     * @return $requestId
     */
    public function getRequestedId($addressId) {
        try{
            $addressCollection = $this->addressApprovalFactory->create()->getCollection()->addFieldToFilter('address_id',$addressId);
            $requestId = $addressCollection->getFirstItem()->getEntityId();
            return $requestId;
        }catch(\Exception $e){
            return null;
        }
    }
}