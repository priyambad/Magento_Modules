<?php

namespace Redington\Transactions\Block\Credit;

class Newcredit extends \Magento\Framework\View\Element\Template
{

    /**
     * Constructor function
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
        array $data = []
    ) {
        $this->companyHelper = $companyHelper;
        $this->creditDataProvider = $creditDataProvider;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->addressRepository = $addressRepository;
        $this->websiteCurrency = $websiteCurrency;
        $this->priceFormatter = $priceFormatter;
        parent::__construct($context, $data);
    }
    /**
     * getAvailableCredit function
     *
     * @return creditLimit
     */
    public function getAvailableCredit()
    {
        $companyId = $this->companyHelper->retrieveCompanyId();
        return $this->creditDataProvider->get($companyId)->getAvailableLimit();
    }
    /**
     * getSapAccountNumber function
     *
     * @return $sapAccountNumber
     */
    public function getSapAccountNumber()
    {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        return $adminUser->getCustomAttribute('z_sap_account_number')->getValue();
    }
    /**
     * getDocumentsApi function
     *
     * @return $apiEndpoint
     */
    public function getDocumentsApi()
    {
        return $this->scopeConfig->getValue('redington_documents/general/document_fetch_api');
    }
    /**
     * getCountryId function
     *
     * @return $countryId
     */
    public function getCountryId()
    {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $defaultShippingAddressId = $adminUser->getDefaultShipping();
        $countryId = $this->addressRepository->getById($defaultShippingAddressId)->getCountryId();
        return $countryId;
    }
    /**
     * getComapanyName function
     *
     * @return $companyName
     */
    public function getComapanyName()
    {
        return $this->companyHelper->retrieveCompanyName();
    }

    /**
     * getCreditCurrency function
     *
     * @return $creditCurrencyCode
     */
    public function getCreditCurrency()
    {
        $creditCurrencyCode = null;
        if ($this->getCredit()) {
            $creditCurrencyCode = $this->getCredit()->getCurrencyCode();
        }
        return $creditCurrencyCode;
    }
    /**
     * getCredit function
     *
     * @return $credit
     */
    private function getCredit()
    {
        $companyId = $this->companyHelper->retrieveCompanyId();
        $this->credit = $this->creditDataProvider->get($companyId);
        return $this->credit;
    }
}
