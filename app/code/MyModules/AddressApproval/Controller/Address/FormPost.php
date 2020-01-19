<?php

namespace Redington\AddressApproval\Controller\Address;

use Magento\Framework\App\ObjectManager;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;

class FormPost extends \Magento\Customer\Controller\Address\FormPost {
    private $customerAddressMapper;
    protected $_storeManager;
    /**
     * Undocumented function
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Helper\Data $helperData
     * @param \Redington\AddressApproval\Model\AddressApprovalFactory $addressApproval
     * @param \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApproval
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Company\Model\CompanyUser $companyUser
     * @param \Magento\Company\Model\CompanyFactory $companyFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Redington\AddressApproval\Helper\Data $approvalHeler
     * @param \Redington\AddressApproval\Helper\Region $regionHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Helper\Data $helperData,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApproval,
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApproval,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Redington\AddressApproval\Helper\Data $approvalHeler,
        \Redington\AddressApproval\Helper\Region $regionHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
        ) {
        $this->addressApprovalFactory = $addressApproval;
        $this->forwarderApprovalFactory = $forwarderApproval;
        $this->countryFactory =$countryFactory;
        $this->companyFactory = $companyFactory;
        $this->companyUser = $companyUser;
        $this->companyFactory = $companyFactory;
        $this->resultJsonFactory = $jsonFactory;
        $this->companyHelper = $companyHelper;
        $this->approvalHeler = $approvalHeler;
        $this->regionHelper = $regionHelper;
        $this->customerSession = $customerSession;
        $this->_storeManager = $storeManager; 
        parent::__construct($context, $customerSession, $formKeyValidator, $formFactory, $addressRepository, $addressDataFactory, $regionDataFactory, $dataProcessor, $dataObjectHelper, $resultForwardFactory, $resultPageFactory, $regionFactory, $helperData);
    }
    
    public function execute()
    {
        
       
        
        $resultJson = $this->resultJsonFactory->create();
        
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $postData = $this->getRequest()->getPostValue();
        $postCountryId = $postData['country_id'];
        $postTelephone = $postData['telephone'];

        if (!$this->getRequest()->isPost()) {
            $this->_getSession()->setAddressFormData($this->getRequest()->getPostValue());
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->error($this->_buildUrl('*/*/edit'))
            );
        }

        try {
            $address = $this->_extractAddress();
            $address->setCustomerId($this->companyHelper->getCompanyAdminId());
            $formatedTelephone = $this->getIsdCode($postCountryId).''.$postTelephone;
            $address->setTelephone($formatedTelephone);
            $savedAddress = $this->_addressRepository->save($address);
            $savedAddress->setCustomAttribute('approved',0);
            $isForwarder = $this->getRequest()->getPost('is_forwarder');
            if($isForwarder == 'true') {
                $savedAddress->setCustomAttribute('is_forwarder',1);
                $this->_addressRepository->save($savedAddress);
                $companyName = $this->setForwarderApprovalData($savedAddress);
                  if (strpos($_SERVER['HTTP_REFERER'],'edit') !== false) {
                      $this->customerSession->setAddressRequest('forwarder_edit_documents');
                  }else{
                    $this->customerSession->setAddressRequest('new_forwarder');
                  }
            }else {
                $this->_addressRepository->save($savedAddress);
                $companyName = $this->setAddressApprovalData($savedAddress);
                if (strpos($_SERVER['HTTP_REFERER'],'edit') !== false) {
                 $this->customerSession->setAddressRequest('edit_documents');
             }else{
                $this->customerSession->setAddressRequest('new_documents');
             }
            }
            $this->customerSession->setAddressRequestId($savedAddress->getId());
            $this->messageManager->addSuccessMessage(__('You saved the address.'));

            $response = [
                'success' => true,
                'address_id' => $savedAddress->getId(),
                'company_name' => $companyName
            ];
            return $resultJson->setData($response);
            
            
        } catch (InputException $e) {

            $response = [
                'error' => true,
                'message' => $e->getMessage()
            ];
            return $resultJson->setData($response);
        } catch (\Exception $e) {

            $response = [
                'error' => true,
                'message' => $e->getMessage()
            ];
            return $resultJson->setData($response);
        }

        
    }
    /**
     * Undocumented function
     *
     * @param [type] $postCountryId
     * @return $countryCode
     */
    private function getIsdCode($postCountryId){
        switch ($postCountryId) {
            case "AE":
                return '+971';
            case "KE":
                return '+254';
        }
    }
    /**
     * Undocumented function
     *
     * @return $address
     */
    protected function getExistingAddressData()
    {
        $existingAddressData = [];
        if ($addressId = $this->getRequest()->getParam('id')) {
            $existingAddress = $this->_addressRepository->getById($addressId);
            if ($existingAddress->getCustomerId() !== $this->companyHelper->getCompanyAdminId()) {
                throw new \Exception();
            }
            $existingAddressData = $this->getCustomerAddressMapper()->toFlatArray($existingAddress);
        }
        return $existingAddressData;
    }

    /**
     * Undocumented function
     *
     * @return address
     */
    protected function _extractAddress()
    {
        $existingAddressData = $this->getExistingAddressData();

        /** @var \Magento\Customer\Model\Metadata\Form $addressForm */
        $addressForm = $this->_formFactory->create(
            'customer_address',
            'customer_address_edit',
            $existingAddressData
        );
        $addressData = $addressForm->extractData($this->getRequest());
        $attributeValues = $addressForm->compactData($addressData);

        $this->updateRegionData($attributeValues);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($existingAddressData, $attributeValues),
            \Magento\Customer\Api\Data\AddressInterface::class
        );
        $addressDataObject->setCustomerId($this->companyHelper->getCompanyAdminId())
            ->setIsDefaultBilling(
                $this->getRequest()->getParam(
                    'default_billing',
                    isset($existingAddressData['default_billing']) ? $existingAddressData['default_billing'] : false
                )
            )
            ->setIsDefaultShipping(
                $this->getRequest()->getParam(
                    'default_shipping',
                    isset($existingAddressData['default_shipping']) ? $existingAddressData['default_shipping'] : false
                )
            );
        return $addressDataObject;
    }
    /**
     * Undocumented function
     *
     * @param [type] $attributeValues
     * @return void
     */
    protected function updateRegionData(&$attributeValues)
    {
        if (!empty($attributeValues['region_id'])) {
            $newRegion = $this->regionFactory->create()->load($attributeValues['region_id']);
            $attributeValues['region_code'] = $newRegion->getCode();
            $attributeValues['region'] = $this->getRegionName($attributeValues['country_id'],$attributeValues['region_id']);
        }

        $regionData = [
            RegionInterface::REGION_ID => !empty($attributeValues['region_id']) ? $attributeValues['region_id'] : null,
            RegionInterface::REGION => $this->getRegionName($attributeValues['country_id'],$attributeValues['region_id']),
            RegionInterface::REGION_CODE => !empty($attributeValues['region_code'])
                ? $attributeValues['region_code']
                : null,
        ];

        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            \Magento\Customer\Api\Data\RegionInterface::class
        );
        $attributeValues['region'] = $region;
    }
    /**
     * Undocumented function
     *
     * @param [type] $countryId
     * @param [type] $regionId
     * @return $region
     */
    protected function getRegionName($countryId,$regionId){
        $regionData = $this->regionHelper->getRegionData();
        return $regionData[$countryId][$regionId];
    }
    /**
     * Undocumented function
     *
     * @return $customerAddressMapper
     */
    private function getCustomerAddressMapper()
    {
        if ($this->customerAddressMapper === null) {
            $this->customerAddressMapper = ObjectManager::getInstance()->get(
                \Magento\Customer\Model\Address\Mapper::class
            );
        }
        return $this->customerAddressMapper;
    }

    /**
     * Undocumented function
     *
     * @param [type] $address
     * @return $companyName
     */
    public function setAddressApprovalData($address) {
        $addressApproval = $this->addressApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$address->getId());
        if($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
        }
        $addressId = $address->getId();
        $street = $address->getStreet();
        $city = $address->getCity();
        $parentId = $this->companyHelper->getCompanyAdminId();
        $requestedBy = $this->_getSession()->getCustomerId();
        $phone = $address->getTelephone();
        $postCode = $address->getPostCode();
        $region = $address->getRegionId();
        $countryId = $address->getCountryId();
        $countryName = $this->countryFactory->create()->loadByCode($countryId)->getName();
        $companyId = $this->companyUser->getCurrentCompanyId();
        $companyName = $this->companyFactory->create()->load($companyId)->getData('company_name');
        $addressString = $city.',\\n'.$region.','.$postCode.'\\n'.$countryName;

//        Save Data in Address Approval Table
        $addressApproval->setAddressId($addressId);
        $addressApproval->setCompanyId($companyId);
        $addressApproval->setParentId($parentId);
        $addressApproval->setRequestedBy($requestedBy);
        $addressApproval->setCompanyName($this->getRequest()->getPost('company'));
        $addressApproval->setPhone($phone);
        $addressApproval->setCity($city);
        $addressApproval->setPostcode($postCode);
        $addressApproval->setCountry($countryName);
        $addressApproval->setAddress($addressString);
        $addressApproval->setStatus(100);
        $addressApproval->setCustomerStoreId($this->_storeManager->getStore()->getId());
        $addressApproval->save();
        $this->customerSession->setApprovalRequestId($addressApproval->getEntityId());
        $this->customerSession->setIsForwarderRequest(false);
        // $this->approvalHeler->sendApprovalRequestMail('Shipping Address',$address,$parentId,$requestedBy);

        return $companyName;
    }

    /**
     * Undocumented function
     *
     * @param [type] $address
     * @return $companyName
     */
    public function setForwarderApprovalData($address) {
        $forwarderApproval = $this->forwarderApprovalFactory->create();
        $existInQueue = $forwarderApproval->getCollection()->addFieldToFilter('address_id',$address->getId());
        if($existInQueue->count() > 0) {
            $forwarderApproval = $existInQueue->getFirstItem();
        }
        $addressId = $address->getId();
        $street = $address->getStreet();
        $city = $address->getCity();
        $parentId = $this->companyHelper->getCompanyAdminId();
        $requestedBy = $this->_getSession()->getCustomerId();
        $phone = $address->getTelephone();
        $postCode = $address->getPostCode();
        $region = $address->getRegionId();
        $countryId = $address->getCountryId();
        $countryName = $this->countryFactory->create()->loadByCode($countryId)->getName();
        $companyId = $this->companyUser->getCurrentCompanyId();
        $companyName = $this->companyFactory->create()->load($companyId)->getData('company_name');
        $addressString = $city.',\\n'.$region.','.$postCode.'\\n'.$countryName;

//        Save Data in Address Approval Table
        $forwarderApproval->setAddressId($addressId);
        $forwarderApproval->setCompanyId($companyId);
        $forwarderApproval->setParentId($parentId);
        $forwarderApproval->setRequestedBy($requestedBy);
        $forwarderApproval->setCompanyName($this->getRequest()->getPost('company'));
        $forwarderApproval->setPhone($phone);
        $forwarderApproval->setCity($city);
        $forwarderApproval->setPostcode($postCode);
        $forwarderApproval->setCountry($countryName);
        $forwarderApproval->setAddress($addressString);
        $forwarderApproval->setStatus(100);
        $forwarderApproval->setCustomerStoreId($this->_storeManager->getStore()->getId());
        $forwarderApproval->save();
        $this->customerSession->setApprovalRequestId($forwarderApproval->getEntityId());
        $this->customerSession->setIsForwarderRequest(true);
        // $this->approvalHeler->sendApprovalRequestMail('Forwarder',$address,$parentId,$requestedBy);
        
        return $companyName;
    }
}