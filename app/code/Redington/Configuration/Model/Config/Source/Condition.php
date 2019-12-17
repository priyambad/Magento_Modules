<?php
namespace Redington\Configuration\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Condition extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->_options=[
                ['label' => __('New'), 'value' => 0],
                ['label' => __('Used'), 'value' => 1],
                ['label' => __('Refurbished'), 'value' => 2]
            ];
        }
        return $this->_options;
    }
}