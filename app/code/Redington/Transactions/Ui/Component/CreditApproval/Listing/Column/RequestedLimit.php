<?php

namespace Redington\Transactions\Ui\Component\CreditApproval\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class RequestedLimit extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['requested_credit_limit'] is column name
                
                $items['requested_credit_limit'] = $this->_storeManager->getStore()->getBaseCurrencyCode().' '.$items['requested_credit_limit'];
            }
        }
        return $dataSource;
    }
}