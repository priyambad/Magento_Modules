<?php

namespace Redington\Transactions\Ui\Component\Transaction\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class TransactionAmount extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $components = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->pricingHelper = $pricingHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['transaction_amount'] is column name
                
                $items['transaction_amount'] = $this->getFormattedPrice($items['transaction_amount']);
            }
        }
        return $dataSource;
    }
    public function getFormattedPrice($price, $format = true, $includeContainer = false)
    {
         return $this->pricingHelper->currency($price, $format, $includeContainer);
    }
}