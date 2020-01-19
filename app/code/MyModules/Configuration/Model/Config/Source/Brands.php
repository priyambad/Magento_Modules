<?php
namespace Redington\Configuration\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Brands extends AbstractSource
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
                ['label' => __('Apple'), 'value' => 0],
                ['label' => __('Samsung'), 'value' => 1],
                ['label' => __('HP'), 'value' => 2],
                ['label' => __('Dell'), 'value' => 3],
                ['label' => __('Acer'), 'value' => 4],
                ['label' => __('Microsoft'), 'value' => 5]
            ];
        }
        return $this->_options;
    }
}