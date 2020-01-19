<?php
namespace Redington\Configuration\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Availability extends AbstractSource
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
                ['label' => __('No'), 'value' => 0],
                ['label' => __('Yes'), 'value' => 1],
                ['label' => __('B2B'), 'value' => 2]
            ];
        }
        return $this->_options;
    }
}