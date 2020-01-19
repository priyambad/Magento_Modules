<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */

namespace Redington\Company\Helper;

use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\CompanyManagementFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     *
     * @var \Magento\Customer\Model\Customer
     */
    protected $customerModel;

    /**
     * PaymentGroup Model
     *
     * @var \Redington\PaymentGroup\Model\Group
     */
    protected $paymentGroup;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\SharedCatalog\Model\SharedCatalogFactory $sharedCatalogFactory
     * @param \Magento\SharedCatalog\Model\Repository $sharedCatalogRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param CompanyManagementFactory $companyManagementFactory
     * @param \Magento\Company\Model\Company $companyModel
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Redington\PaymentGroup\Model\Group $paymentGroup
     * @param CompanyPaymentMethod $companyPaymentMethod
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SharedCatalog\Model\SharedCatalogFactory $sharedCatalogFactory,
        \Magento\SharedCatalog\Model\Repository $sharedCatalogRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        CompanyManagementFactory $companyManagementFactory,
        \Magento\Company\Model\Company $companyModel,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Redington\Company\Model\DocumentsFactory $documentFactory,
        \Redington\Company\Model\BrandPreferenceFactory $BrandFactory,
        \Redington\Company\Model\ParentCompanyFactory $ParentCompanyFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Company\Model\Customer $companyCustomer,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\SharedCatalog\Model\CategoryManagementFactory $categoryManagementFactory,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\SharedCatalog\Model\ProductManagementFactory $productManagement,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Company\Model\RoleFactory $roleFactory,
        \Magento\Company\Model\PermissionFactory $permissionFactory,
        \Redington\Company\Helper\CompanyResources $companyResources,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Redington\PaymentGroup\Model\GroupFactory $paymentGroup,
        \Magento\CompanyPayment\Model\CompanyPaymentMethod $companyPaymentMethod,
        \Magento\Company\Model\CustomerFactory $companyCustomerFactory,
        \Redington\Category\Model\CategoryFactory $redingtonCategoryFactory,
        \Magento\NegotiableQuote\Model\CompanyQuoteConfigFactory $companyQuoteConfigFactory,
        \Magento\Company\Model\CompanyFactory $companyFactory
    ) {
        $this->roleFactory = $roleFactory;
        $this->permissionFactory = $permissionFactory;
        $this->companyResources = $companyResources;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->sharedCatalogFactory = $sharedCatalogFactory;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->taxClassRepository = $taxClassRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->groupManagement = $groupManagement;
        $this->companyManagementFactory = $companyManagementFactory;
        $this->companyModel = $companyModel;
        $this->documentFactory = $documentFactory;
        $this->BrandFactory = $BrandFactory;
        $this->ParentCompanyFactory = $ParentCompanyFactory;
        $this->_objectManager = $objectmanager;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->companyCustomer = $companyCustomer;
        $this->serialize = $serialize;
        $this->categoryFactory = $categoryFactory;
        $this->categoryManagementFactory = $categoryManagementFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->productFactory = $productFactory;
        $this->productManagement = $productManagement;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->paymentGroup = $paymentGroup;
        $this->companyPaymentMethod = $companyPaymentMethod;
        $this->companyCustomerFactory = $companyCustomerFactory;
        $this->redingtonCategoryFactory = $redingtonCategoryFactory;
        $this->companyQuoteConfigFactory = $companyQuoteConfigFactory;
        $this->companyFactory = $companyFactory;
    }

    public function logMessage($message)
    {
        $filePath = '/var/log/Redington_Partners.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }

    public function resetPostalCode($companyId){
        $this->logMessage('reseting postal code for comapny : '.$companyId);
        try{
            $this->companyFactory->create()->load($companyId)->setPostcode(NULL)->save();
        }catch(\Exception $e){
            $this->logMessage('error : '.$e->getMessage());
        }
    }

    public function setUserContact($userId,$telephone,$countryCode){
        $companyUser = $this->companyCustomerFactory->create()->load($userId);
        $companyUser->setTelephone($telephone);
        $companyUser->setCountryCode($countryCode);
        $companyUser->save();
    }

    /***
     *
     *
     */
    public function removeDefaultUserRole($companyId)
    {
        $companyRole = $this->roleFactory->create()->getCollection()
            ->addFieldToFilter('role_name', array('eq' => 'Default User'))
            ->addFieldToFilter('company_id', array('eq' => $companyId));

        $roleData = $companyRole->getData();
        foreach ($roleData as $data) {
            $roleId = $data['role_id'];
            $companyRole = $this->roleFactory->create()->load($roleId);
            $companyRole->delete();
            $companyRole->save();
        }

    }
    public function assignUserRoles($companyId)
    {
        $userRole = 'Sales';
        $companyRole = $this->roleFactory->create();
        $companyRole->setSortOrder(0);
        $companyRole->setRoleName($userRole);
        $companyRole->setCompanyId($companyId);
        $role = $companyRole->save();
        $roleId = $role->getId();
        $resources = $this->companyResources->getSalesResources();

        foreach ($resources as $name => $permission) {
            $companyPermission = $this->permissionFactory->create();
            $companyPermission->setRoleId($roleId);
            $companyPermission->setResourceId($name);
            $companyPermission->setPermission($permission);
            $companyPermission->save();
        }

        $userRole = 'Finance';
        $companyRole = $this->roleFactory->create();
        $companyRole->setSortOrder(0);
        $companyRole->setRoleName($userRole);
        $companyRole->setCompanyId($companyId);
        $role = $companyRole->save();
        $roleId = $role->getId();
        $resources = $this->companyResources->getFinanceResource();

        foreach ($resources as $name => $permission) {
            $companyPermission = $this->permissionFactory->create();
            $companyPermission->setRoleId($roleId);
            $companyPermission->setResourceId($name);
            $companyPermission->setPermission($permission);
            $companyPermission->save();
        }
    }

    public function sendCustomerAddedEmail($name, $email, $password, $companyName)
    {
        $this->logMessage('called send mail');
        $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->logMessage('fetchde to and from ');
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
            'companyName' => $companyName,
            'customerName' => $name,
            'accountUrl' => $this->storeManager->getStore('default')->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true),
            'userName' => $email,
            'password' => $password,
        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = array($email);
        $this->logMessage('set to and from');
        $this->logMessage($from);
        $this->logMessage($to);
        $this->logMessage('template identifier');
        $this->logMessage($this->scopeConfig->getValue('redington_company/email/new_user', \Magento\Store\Model\ScopeInterface::SCOPE_STORES));
        $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_company/email/new_user', \Magento\Store\Model\ScopeInterface::SCOPE_STORES))
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to)
            ->getTransport();
        $this->logMessage('trying to send mail');
        try {
            $sent = $transport->sendMessage();
            $this->logMessage('result');
            $this->logMessage($sent);
        } catch (\Exception $e) {
            $this->logMessage($e->getMessage());
        }
        $this->logMessage('mail sent');
        $this->inlineTranslation->resume();
    }
    public function sendPartnerAddedEmail($name, $email, $storeCode)
    {
        $this->logMessage('adding new user');
        $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $this->logMessage('template variables');
        $templateVars = array(
            'customerName' => $name,
            'accountUrl' => $this->storeManager->getStore('default')->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true),
            'userName' => $email,
        );
        $this->logMessage($templateVars);
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = array($email);
        $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_company/email/new_partner', \Magento\Store\Model\ScopeInterface::SCOPE_STORES))
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to)
            ->getTransport();
        $sent = $transport->sendMessage();
        $this->logMessage('sent result');
        $this->logMessage($sent);
        $this->inlineTranslation->resume();
    }

    public function setBillingAddress($customerId, $billingAddress,$companyName)
    {
        $address = $this->customerAddressFactory->create();
        $billingPostCode=(isset($billingAddress['postcode']) && !empty($billingAddress['postcode']))?$billingAddress['postcode']:NULL;
        $street = [$billingAddress['street1'], $billingAddress['street2']];
        $address->setCustomerId($customerId)
            ->setFirstname($billingAddress['firstname'])
            ->setLastname($billingAddress['lastname'])
            ->setCompany($billingAddress['company_name'])
            ->setCountryId($billingAddress['country_id'])
            ->setRegionId($billingAddress['region_id'])
            ->setPostcode($billingPostCode)
            ->setCity($billingAddress['city'])
            ->setTelephone($billingAddress['telephone'])
            ->setStreet($street)
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('0')
            ->setSaveInAddressBook('1')
            ->setCustomAttribute('approved', 1)
            ->setCustomAttribute('is_valid', 1)
            ->setCustomAttribute('sap_address_id', $billingAddress['SAPcustomernumber'])
            ->setCustomAttribute('delivery_plant', $billingAddress['deliveryplant']);
        $address->save();
    }
    public function setShippingAddress($customerId, $shippingAddress,$companyName)
    {
        $address = $this->customerAddressFactory->create();
        $shipingPostCode=(isset($shippingAddress['postcode']) && !empty($shippingAddress['postcode']))?$shippingAddress['postcode']:NULL;
        $street = [$shippingAddress['street1'], $shippingAddress['street2']];
        $address->setCustomerId($customerId)
            ->setFirstname($shippingAddress['firstname'])
            ->setLastname($shippingAddress['lastname'])
            ->setCompany($shippingAddress['company_name'])
            ->setCountryId($shippingAddress['country_id'])
            ->setRegionId($shippingAddress['region_id'])
            ->setPostcode($shipingPostCode)
            ->setCity($shippingAddress['city'])
            ->setTelephone($shippingAddress['telephone'])
            ->setStreet($street)
            ->setIsDefaultBilling('0')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1')
            ->setCustomAttribute('approved', 1)
            ->setCustomAttribute('is_valid', 1)
            ->setCustomAttribute('sap_address_id', $shippingAddress['SAPcustomernumber'])
            ->setCustomAttribute('delivery_plant', $shippingAddress['deliveryplant']);
        $address->save();
    }

    public function setCategoryData($companyId,$websiteId,$categoryData)
    {
        $categoryIds = [];
        $brandIds = [];

        $BussinessList = $categoryData[0]['lst_business'];
        foreach ($BussinessList as $key => $bussiness) {
            $bussinessName = strtok($bussiness['business_name'], " ");
            $parentCategoryId = $this->categoryFactory->create()->getCollection()->addFieldToFilter('name', $bussinessName)->getFirstItem()->getId();
            $categoryList = $bussiness['lst_category'];
            foreach ($categoryList as $key => $mainCategory) {
                $mainCategoryName = $mainCategory['category_name'];
                $subCategories = $mainCategory['lst_sub_category'];
                $mainCategoryId = $this->getSpecificChildCategoryId($parentCategoryId, $mainCategoryName);
                if ($mainCategoryId) {
                    foreach ($subCategories as $key => $subCategory) {
                        $subCategoryName = $subCategory['sub_category_name'];
                        $brandsList = $subCategory['lst_brands'];
                        $subCategoryId = $this->getSpecificChildCategoryId($mainCategoryId, $subCategoryName);
                        if ($subCategoryId) {
                            array_push($categoryIds, $subCategoryId);
                            foreach ($brandsList as $key => $brandName) {
                                $brandCategoryId = $this->getSpecificChildCategoryId($subCategoryId, $brandName);
                                if ($brandCategoryId) {
                                    array_push($brandIds, $brandCategoryId);
                                }
                            }
                        }
                    }
                }
            }
        }
        $redingtonCategory = $this->redingtonCategoryFactory->create();
        $redingtonCategory->setWebsiteId($websiteId);
        $redingtonCategory->setPartnerId($companyId);
        $redingtonCategory->setCategories($this->serialize->serialize($categoryIds));
        $redingtonCategory->setBrands($this->serialize->serialize($brandIds));
        $redingtonCategory->save();
    }

    public function getStoreByStoreCode($storeCode)
    {
        $store = $this->storeManager->getStore($storeCode);
        return $store;
    }

    /**
     * Get Website Id by Code
     *
     * @param string
     * return int
     */
    public function getWebsiteIdByStoreCode($storeCode)
    {
        $store = $this->getStoreByStoreCode($storeCode);
        $websiteId = $store->getWebsiteId();
        return $websiteId;
    }

    /**
     * Get Store Id by Code
     *
     * @param string
     * return int
     */
    public function getStoreIdByStoreCode($storeCode)
    {
        $store = $this->getStoreByStoreCode($storeCode);
        $storeId = $store->getStoreId();
        return $storeId;
    }

    /**
     *
     * @param int $companyId
     * @param string $sharedCatalogName
     */
    public function assignSharedCatalog($companyId, $sharedCatalogName, $storeId)
    {
        $companyManagement = $this->companyManagementFactory->create();

        $sharedCatalog = $this->sharedCatalogFactory->create();
        $loadedSharedCatalog = $sharedCatalog->getCollection()->addFieldToFilter('name', $sharedCatalogName)->load();
        if (!(empty($loadedSharedCatalog->getData()))) {
            $sharedCatalogId = $loadedSharedCatalog->getData()[0]['entity_id'];
        } else {
            $customerGroupId = $this->groupManagement->getDefaultGroup()->getId();
            $sharedCatalog->setName($sharedCatalogName)
                ->setType(SharedCatalogInterface::TYPE_CUSTOM)
                ->setStoreId($storeId)
                ->setTaxClassId($this->getRetailCustomerTaxClassId());
            $sharedCatalogId = $this->sharedCatalogRepository->save($sharedCatalog);
            $createdSharedCatalog = $this->sharedCatalogFactory->create()->load($sharedCatalogId);
            $createdSharedCatalog->setStoreId(null);
            $createdSharedCatalog->save();
        }
        $company = $this->companyModel->load($companyId);
        $companyManagement->assignCompanies($sharedCatalogId, [$company]);
    }

    /**
     *
     * @return int | null
     */
    private function getRetailCustomerTaxClassId()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();
        $customerTaxClasses = $this->taxClassRepository->getList($searchCriteria)->getItems();
        $customerTaxClass = array_shift($customerTaxClasses);
        return ($customerTaxClass && $customerTaxClass->getClassId()) ? $customerTaxClass->getClassId() : null;
    }

    /**
     *
     * @param type $attributesArray
     * @param type $customerId
     */
    public function assignCustomAttributes($attributesArray, $customerId)
    {
        foreach ($attributesArray as $key => $value) {
            $customerRepository = $this->_objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
            $customer = $customerRepository->getById($customerId);
            $customer->setCustomAttribute($key, $value);
            $customerRepository->save($customer);
        }
    }
    public function saveDocuments($companyId, $docData)
    {
        $serializedDocData = $this->serialize->serialize($docData);
        $document = $this->documentFactory->create();
        $document->setCompanyId($companyId);
        $document->setDocumentDetails($serializedDocData);
        $document->save();

    }

    public function saveBrandPreference($companyId, $brandsData)
    {
        $approvedBrands = $this->serialize->serialize($brandsData['lst_approved_brands']);
        $requeatedBrands = $this->serialize->serialize($brandsData['lst_requested_brands']);
        $brand = $this->BrandFactory->create();
        $brand->setCompanyId($companyId);
        $brand->setRequested($requeatedBrands);
        $brand->setApproved($approvedBrands);
        $brand->save();
    }

    public function setParentCompany($companyId, $adminEmail, $websiteId)
    {
        $parentCompany = $this->ParentCompanyFactory->create();
        $parentCompany->setCompanyId($companyId);
        $parentCompany->setAdminEmail($adminEmail);
        $parentCompany->setWebsiteId($websiteId);
        $parentCompany->save();
    }

    public function setCategoryAndProducts($companyId, $approvedCategories)
    {
        $sharedCatalogName = 'SYS_PERMISSION_' . $companyId;
        $sharedCatalog = $this->sharedCatalogFactory->create();
        $loadedSharedCatalog = $sharedCatalog->getCollection()->addFieldToFilter('name', $sharedCatalogName)->load();
        if (!(empty($loadedSharedCatalog->getData()))) {
            $sharedCatalogId = $loadedSharedCatalog->getData()[0]['entity_id'];
            $categoryIds = $this->getCategoryArray($approvedCategories);
            $categoryArray = [];
            foreach ($categoryIds as $key => $catId) {
                $cat = $this->categoryFactory->create()->load($catId);
                array_push($categoryArray, $cat);
            }
            $categoryManager = $this->categoryManagementFactory->create();
            $categoryManager->assignCategories($sharedCatalogId, $categoryArray);

            $productIds = $this->getProductIds($categoryIds);
            $productArray = [];
            foreach ($productIds as $key => $productId) {
                $product = $this->productFactory->create()->load($productId);
                array_push($productArray, $product);
            }
            $productManagement = $this->productManagement->create();
            $productManagement->assignProducts($sharedCatalogId, $productArray);
        }
    }
    public function getProductIds($categoryIds)
    {
        $productIds = [];
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoriesFilter(['in' => $categoryIds]);
        foreach ($collection as $key => $product) {
            array_push($productIds, $product->getId());
        }

        return array_unique($productIds);
    }

    public function getCategoryArray($categoryData)
    {
        $categoryIds = [];

        $BussinessList = $categoryData[0]['lst_business'];
        foreach ($BussinessList as $key => $bussiness) {
            $bussinessName = strtok($bussiness['business_name'], " ");
            $parentCategoryId = $this->categoryFactory->create()->getCollection()->addFieldToFilter('name', $bussinessName)->getFirstItem()->getId();
            $categoryList = $bussiness['lst_category'];
            foreach ($categoryList as $key => $mainCategory) {
                $mainCategoryName = $mainCategory['category_name'];
                $subCategories = $mainCategory['lst_sub_category'];
                $mainCategoryId = $this->getSpecificChildCategoryId($parentCategoryId, $mainCategoryName);
                if ($mainCategoryId) {
                    foreach ($subCategories as $key => $subCategory) {
                        $subCategoryName = $subCategory['sub_category_name'];
                        $brandsList = $subCategory['lst_brands'];
                        $subCategoryId = $this->getSpecificChildCategoryId($mainCategoryId, $subCategoryName);
                        if ($subCategoryId) {
                            array_push($categoryIds, $subCategoryId);
                            foreach ($brandsList as $key => $brandName) {
                                $brandCategoryId = $this->getSpecificChildCategoryId($subCategoryId, $brandName);
                                if ($brandCategoryId) {
                                    array_push($categoryIds, $brandCategoryId);
                                }
                            }
                        }
                    }
                }
            }
        }
        return array_unique($categoryIds);
    }

    public function getSpecificChildCategoryId($catId, $childCatName)
    {
        $childCategories = $this->categoryFactory->create()->load($catId)->getChildrenCategories();
        foreach ($childCategories as $key => $cat) {
            if ($cat->getName() == $childCatName) {
                return $cat->getId();
            }

        }
        return false;
    }

    public function getCompanyAdminId()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customerId);

        $companyId = $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
        ? $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        : null;

        $parentId = '';
        if ($companyId) {
            $companyData = $this->companyModel->load($companyId);

            if ($companyData) {
                $parentId = $companyData->getSuperUserId();
            } else {
                $parentId = $customerId;
            }
        }

        return $parentId;
    }

    /**
     * Get Company ID
     *
     * @return parentId
     */
    public function retrieveCompanyId()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customerId);

        $companyId = $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
        ? $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        : null;

        if ($companyId) {
            return $companyId;
        }

        return 0;
    }

    /**
     * Get All Company Users Id
     *
     * @return companyUsersIds
     */
    public function retrieveCompanyUsers()
    {
        $companyId = $this->retrieveCompanyId();

        $companyUsersIds = '';
        if ($companyId) {
            $companyCustomerData = $this->companyCustomer->getCollection();
            $companyCustomerData->addFieldToFilter('company_id', $companyId);

            if ($companyCustomerData) {
                $i = 0;
                foreach ($companyCustomerData as $companyCustomer) {
                    $i++;
                    $companyUsersIds .= $companyCustomer->getCustomerId();
                    if ($i < count($companyCustomerData)) {
                        $companyUsersIds .= ', ';
                    }
                }
            }
        }

        return $companyUsersIds;
    }

    /**
     * Get All Company Users Id Array
     *
     * @return companyUsersIds
     */
    public function retrieveCompanyUsersArray()
    {
        $companyId = $this->retrieveCompanyId();

        $companyUsersIds = [];
        if ($companyId) {
            $companyCustomerData = $this->companyCustomer->getCollection();
            $companyCustomerData->addFieldToFilter('company_id', $companyId);

            if ($companyCustomerData) {
                $i = 0;
                foreach ($companyCustomerData as $companyCustomer) {
                    $i++;
                    $companyUsersIds[] = $companyCustomer->getCustomerId();
                }
            }
        }

        return $companyUsersIds;
    }

    /**
     * Get Company admin account ID
     *
     * @return parentId
     */
    public function getCompanyUserCount()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customerId);

        $companyId = $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
        ? $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        : null;

        $companyUsers = 0;
        if ($companyId) {
            $companyCustomerData = $this->companyCustomer->getCollection();
            $companyCustomerData->addFieldToFilter('company_id', $companyId);

            if ($companyCustomerData) {
                $companyUsers = count($companyCustomerData->getData());
            }
        }

        return $companyUsers;
    }

    public function getCompanyEmail()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customerId);

        $companyId = $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
        ? $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        : null;

        $companyEmail = '';
        if ($companyId) {
            $companyData = $this->companyModel->load($companyId);

            if ($companyData) {
                $companyEmail = $companyData->getCompanyEmail();
            }
        }

        return $companyEmail;
    }

    public function retrieveCompanyName()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customerId);

        $companyId = $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
        ? $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        : null;

        $companyEmail = '';
        if ($companyId) {
            $companyData = $this->companyModel->load($companyId);

            if ($companyData) {
                $companyEmail = $companyData->getCompanyName();
            }
        }

        return $companyEmail;
    }

    /**
     *  Max user count
     *
     *  @return $companyCustomerData
     */
    public function getMaxUsers()
    {
        $adminId = $this->getCompanyAdminId();
        $adminUser = $this->customerRepository->getById($adminId);
        $maxNumberOfUser = $adminUser->getCustomAttribute('z_max_number_users')->getValue();
        return $maxNumberOfUser;
    }
    /**
     * Set payment terms
     *
     * @param int $companyId
     * @param string $paymentTerm
     * @return void
     */
    public function setPaymentTerms($companyId, $paymentTerm)
    {
        $model = $this->paymentGroup->create();

        if ($paymentTerm == 'R002' || $paymentTerm == 'R003' || $paymentTerm == 'R006' || $paymentTerm == 'R129'):
            $paymentMethods = 'cdc,cashpayment,cashondelivery,companycredit';
            $paymentRefcode = 'R032, R033, R034,' . $paymentTerm . '';
            $paymentGroup = 'cash,credit';

        elseif ($paymentTerm == 'R014' || $paymentTerm == 'R128'):
            $paymentMethods = 'cdc,cashpayment,cashondelivery,pdc';
            $paymentRefcode = 'R032, R033, R034,' . $paymentTerm . '';
            $paymentGroup = 'cash,credit';
        else:
            $paymentMethods = 'cdc,cashpayment,cashondelivery';
            $paymentRefcode = 'R032, R033, R034';
            $paymentGroup = 'cash';
        endif;
        $this->logMessage('called payment terms');
        $this->logMessage($paymentTerm);
        $this->companyPaymentMethod->setCompanyId($companyId);
        $this->companyPaymentMethod->setApplicablePaymentMethod(2);
        $this->companyPaymentMethod->setAvailablePaymentMethods($paymentMethods);
        $this->companyPaymentMethod->save();
        $model->setPartnerId($companyId);
        $model->setPaymentMethodGroup($paymentGroup);
        $model->setSapRefCode($paymentRefcode);
        try {
            $model->save();
        } catch (\Exception $e) {
            $e->getMessage();
            $this->logMessage('called exception in paymentterms');
            $this->logMessage($e->getMessage());
        }
    }
    public function setRequestForQuote($companyId,$quoteRequest)
    {
        $companyData = $this->companyQuoteConfigFactory->create()->load($companyId);
        $companyData->setIsQuoteEnabled($quoteRequest);
        $companyData->save();
    }

}
