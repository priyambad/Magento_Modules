<?php
namespace Redington\Configuration\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class SkuType extends AbstractSource
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
                ['label' => __('Physical'), 'value' => 1],
                ['label' => __('Non Physical'), 'value' => 0]
            ];
        }
        return $this->_options;
    }
}