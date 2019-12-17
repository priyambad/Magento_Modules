<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{

    /**
     * default
     */
    const SCOPE_DEFAULT = 'default';

    /**
     * Catalog Module Enable configuration
     */
    const CUSTOMER_MODULE_ENABLED = 'redington_quotation/general/enabled';

    protected $_storeManager;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $_storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Quote\Model\Quote\ItemFactory $itemFactory,
        \Magento\NegotiableQuote\Model\Status\LabelProviderInterface $labelProvider,
        \Magento\NegotiableQuote\Model\NegotiableQuoteFactory $negotiableQuote,
        \Magento\NegotiableQuote\Model\Company\DetailsProviderFactory $companyDetailsProviderFactory,
        \Magento\NegotiableQuote\Block\Quote\Info $info,
        \Magento\Company\Model\CompanyUserFactory $companyUserFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Company\Model\CompanyRepository $companyRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
        $this->_storeManager = $_storeManager;
        $this->customerFactory = $customerFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->itemFactory = $itemFactory;
        $this->labelProvider = $labelProvider;
        $this->negotiableQuoteFactory = $negotiableQuote;
        $this->companyDetailsProviderFactory = $companyDetailsProviderFactory;
        $this->info = $info;
        $this->companyUserFactory = $companyUserFactory;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
    }

    /**
     * Get scope config value by path, scope type and code
     *
     * @param type $path
     * return string | int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check module is enabled or not
     *
     * @return string | null
     */
    public function isModuleEnabled()
    {
        return $this->getConfig(self::CUSTOMER_MODULE_ENABLED);
    }

    public function getCurrencyRate()
    {

        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $currencyRate = $this->_storeManager->getStore()->getBaseCurrency()->getRate($currencyCode);
        return $currencyRate;
    }
    public function getCurrencyCode()
    {

        $currencyCode = $this->_storeManager->getStore(5)->getCurrentCurrency()->getCode();
        return $currencyCode;
    }

    public function getCustomerStore($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId);
        return $quote->getStoreId();
    }

    public function getStoreById($storeId)
    {
        $currencyCode = $this->_storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
        return $currencyCode;
    }
    public function getCurrencyRateByCode($currencyCode)
    {

        $currencyRate = $this->_storeManager->getStore()->getBaseCurrency()->getRate($currencyCode);
        return $currencyRate;
    }

    public function getCustomerDetails($quoteId)
    {

        $quote = $this->quoteRepository->get($quoteId);

        $customerId = $quote->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $companyId = $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
        $company = $this->companyRepository->get($companyId);
        $superUserId = $company->getSuperUserId();
        $customerModel = $this->customerFactory->create()->load($superUserId);
        $sourceCollection = $this->sourceCollectionFactory->create()
            ->addFieldToFilter('enabled', 1)
            ->addFieldToFilter('distribution', $customerModel->getZDistributionChannel())
            ->addFieldToFilter('sap_account_code', $customerModel->getZSapCode());

        $data = $sourceCollection->getData();
        if (isset($data) && $data != '') {
            return $data[0]['plant_code'];
        } else {
            return '';
        }

    }

    public function getProductQtyInPlant($productSku, $plantCode)
    {
        $filePath = '/var/log/Redington_admin_catalog_Qty.log';
        try {

            $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku', $productSku)
                ->addFieldToFilter('source_code', $plantCode);
            if (sizeof($sourceCollection) > 0) {
                $sourceData = $sourceCollection->getFirstItem();
                $qtyInPlant = $sourceData->getQuantity();
                $this->logMessage('Available qty in plant ' . $plantCode . ' is ' . $qtyInPlant . ' for product ' . $productSku, $filePath);
                return $qtyInPlant;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->logMessage('Error in getting product qty' . $e->getMessage(), $filePath);
            return false;
        }
    }

    public function getProuductCustomPrice($itemId)
    {
        $itemModel = $this->itemFactory->create()->load($itemId);
        return $itemModel->getCustomPrice();

    }

    public function logMessage($message, $filePath = '/var/log/Redington_Product_Price_Alert.log')
    {
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }

    public function getStatus($quoteId)
    {

        return $this->negotiableQuoteFactory->create()->load($quoteId)->getStatus();

    }
	
	public function getCurrencyRateByCodeInAdmin($currencyCode)
    {

        $currencyRate = $this->_storeManager->getStore(5)->getBaseCurrency()->getRate($currencyCode);
        return $currencyRate;
    }
}
