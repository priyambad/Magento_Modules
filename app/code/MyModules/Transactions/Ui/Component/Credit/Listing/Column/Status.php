<?php

namespace Redington\Transactions\Ui\Component\Credit\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Status extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['status'] is column name
                if ($items['status'] == 0) {
                    $items['status'] = html_entity_decode('<span class="credit-request-pending">Pending</span>');
                }
                else {
                    if ($items['status'] == 1) {
                        $items['status'] = html_entity_decode('<span class="credit-request-rejected">Rejected</span>');
                    } else 
                        $items['status'] = html_entity_decode('<span class="credit-request-approved">Approved</span>');
                }
            }
        }
        return $dataSource;
    }
}