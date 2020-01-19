<?php

namespace Redington\Warehouse\Model\Config\Source;

class DistributionChannel implements \Magento\Framework\Data\OptionSourceInterface
{


    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {

        $distributionChannelData = [
            ['value' => '', 'label' => __('Select distribution')],
            ['value' => '11', 'label' => __('IT Volume ME')],
            ['value' => '13', 'label' => __('IT Telco ME')],
            ['value' => '31', 'label' => __('IT Volume Africa')],
            ['value' => '33', 'label' => __('IT Telco Africa')],
        ];

        return $distributionChannelData;
    }
}