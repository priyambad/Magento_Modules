<?php

namespace Redington\Transactions\Ui\Component\Credit\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class CreditLimit extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
        \Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency,
        \Redington\Company\Helper\Data $companyHelper,
        array $components = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->companyHelper = $companyHelper;
        $this->priceFormatter = $priceFormatter;
        $this->websiteCurrency = $websiteCurrency;
        $this->creditDataProvider = $creditDataProvider;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['available_credit_limit'] is column name
                
//              $items['available_credit_limit'] = $this->_storeManager->getStore()->getBaseCurrencyCode().' '.$items['available_credit_limit'];
                $items['available_credit_limit'] = $this->getFormatedCredit($items['available_credit_limit']);
            }
        }
        return $dataSource;
    }
    public function getFormatedCredit($credit){
        return $this->priceFormatter->format(
            $credit,
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