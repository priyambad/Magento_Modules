<?php

namespace Redington\AddressApproval\Block\Customer\Forwarder;

class Edit extends \Redington\AddressApproval\Block\Customer\Address\Edit {
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
            \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory,
            \Magento\Framework\Serialize\Serializer\Serialize $serialize,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Redington\Company\Helper\Data $companyHelper,
            array $data = array()) {
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionCollectionFactory, $countryCollectionFactory, $customerSession, $addressRepository, $addressDataFactory, $currentCustomer, $dataObjectHelper, $addressApprovalFactory, $serialize, $scopeConfig, $companyHelper, $data);
    }

    public function getAddressApproval($addressId) {
        $addressApproval = $this->forwarderApprovalFactory->create();
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

    public function getForwarderData() {
        return ($this->getAddressData());
    }
    public function getTitle()
    {
        if ($title = $this->getData('title')) {
            return $title;
        }
        if ($this->getAddress()->getId()) {
            $title = __('Edit Forwarder');
        } else {
            $title = __('Add New Forwarder');
        }
        return $title;
    }
    public function getRequestId(){
        $addressId = $this->getAddress()->getId();
        $addressApprovalData = $this->getAddressApproval($addressId);
        $requestId = $addressApprovalData->getEntityId();
        return $requestId;
    }
    public function getBackUrl()
    {
        return $this->getUrl('customer/forwarder/');
    }
}
