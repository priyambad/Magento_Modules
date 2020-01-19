<?php

namespace Redington\Transactions\Ui\Component\CreditApproval\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => __('Pending'), 'value' => '0'];
        $options[] = ['label' => __('Rejected'), 'value' => '1'];
        $options[] = ['label' => __('Approved'), 'value' => '2'];
        return $options;
    }
}
