<?php

namespace Redington\Transactions\Block\Company;
use Redington\Transactions\Model\ResourceModel\Credit\CollectionFactory;

class CreditBalance extends \Magento\CompanyCredit\Block\Company\CreditBalance {
    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
            \Magento\CompanyCredit\Model\CreditDetails\CustomerProvider $customerProvider,
            \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
            \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
            \Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency,
            \Redington\Company\Helper\Data $companyHelper,
            CollectionFactory $collectionFactory,
            array $data = array()) {
        $this->companyHelper = $companyHelper;
        $this->priceFormatter = $priceFormatter;
        $this->websiteCurrency = $websiteCurrency;
        $this->creditDataProvider = $creditDataProvider;
        $this->collection = $collectionFactory->create();
        parent::__construct($context, $customerProvider, $creditDataProvider, $priceFormatter, $websiteCurrency, $data);
    }

    public function getLastAddedCredit(){
        $companyId = $this->companyHelper->retrieveCompanyId();
        $collection = $this->collection->addFieldToFilter('main_table.company_id', ['eq' => $companyId])->addFieldToFilter('main_table.status', ['eq' => 2])->setOrder('entity_id','DESC');
        $lastCredit = $collection->getFirstItem();
        $lastRequest = $lastCredit->getRequestedCreditLimit();
        return $this->priceFormatter->format(
            $lastRequest,
            false,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $this->getCreditCurrency()
        );
    }
    private function getCreditCurrency()
    {
        $creditCurrencyCode = null;
        if ($this->getCredit()) {
            $creditCurrencyCode = $this->getCredit()->getCurrencyCode();
        }
        return $this->websiteCurrency->getCurrencyByCode($creditCurrencyCode);
    }
    public function getCredit()
    {
        $companyId = $this->companyHelper->retrieveCompanyId();
        $this->credit = $this->creditDataProvider->get($companyId);
        return $this->credit;
    }
}
