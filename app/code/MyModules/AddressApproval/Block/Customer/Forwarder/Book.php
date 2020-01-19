<?php


namespace Redington\AddressApproval\Block\Customer\Forwarder;

class Book extends \Redington\AddressApproval\Block\Customer\Address\Book {
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
            \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
            \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
            \Magento\Customer\Model\Address\Config $addressConfig,
            \Magento\Customer\Model\Address\Mapper $addressMapper,
            \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
            \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory,
            \Redington\Company\Helper\Data $companyHelper,
            \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory,
            \Redington\AddressApproval\Helper\Data $redingtonHelper,
            \Magento\Framework\Serialize\Serializer\Serialize $serialize,
            \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory,
            \Magento\Directory\Model\CountryFactory $countryFactory,
            array $data = array()) {
        $this->companyHelper = $companyHelper;
        $this->addressesFactory = $addressesFactory;
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
        $this->serialize = $serialize;
        parent::__construct($context, $customerRepository, $addressRepository, $currentCustomer, $addressConfig, $addressMapper, $addressApprovalFactory, $companyHelper, $redingtonHelper, $serialize, $documentCollectionFactory, $countryFactory, $data);
    }

    public function getApprovedForwarders(){
        $customer = $this->customerRepository->getById($this->companyHelper->getCompanyAdminId());
        $forwarderCollection = $this->addressesFactory->create()
            ->addAttributeToFilter('is_forwarder','1')
            ->addAttributeToFilter('approved',1)
            ->setCustomerFilter($customer);
        return $forwarderCollection;
    }

    public function getRequestedForwardersIds() {
        $forwarderApproval = $this->forwarderApprovalFactory->create();
        $requestedAddress = $forwarderApproval->getCollection()
            ->addFieldToFilter('parent_id',$this->companyHelper->getCompanyAdminId())
            ->addFieldToFilter('status', array('neq' => 2));
        return $requestedAddress;
    }
    /**
     * getStatus function
     *
     * @param [type] $num
     * this function returns string status using integer status stored in database.
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
    public function getForwarderRequestId($addressId){
        $forwarderCollection = $this->forwarderApprovalFactory->create()->getCollection()->addFieldToFilter('address_id',$addressId);
        return $forwarderCollection->getFirstItem()->getEntityId();
    }
    /**
     * getForwarderRequestData function
     *
     * @param [type] $addressId
     * @return $forwarderRequest
     */
    public function getForwarderRequestData($addressId){
        try{
            $forwarderApproval = $this->forwarderApprovalFactory->create();
            $requestedAddress = $forwarderApproval->getCollection()
                ->addFieldToFilter('address_id',$addressId);
            return $requestedAddress->getFirstItem();
        }catch(\Exception $e){
            return null;
        }
    }
    /**
     * getForwarderDocuments function
     *
     * @param [type] $addressId
     * @return $documentData
     */
    public function getForwarderDocuments($addressId){
        try {
            $forwarderRequest = $this->getForwarderRequestData($addressId);
            $documents = $forwarderRequest->getDocuments();
            return $this->serialize->unserialize($documents);
        } catch (\Exception $th) {
            return false;
        }
    }
    /**
     * getRequestedForwarderDocuments function
     *
     * @param [type] $addressId
     * @return $documentData
     */
    public function getRequestedForwarderDocuments($addressId){
        try {
            $forwarderRequest = $this->getForwarderRequestData($addressId);
            $documents = $forwarderRequest->getPendingDocuments();
            return $this->serialize->unserialize($documents);
        } catch (\Exception $th) {
            return false;
        }
    }
    
}
