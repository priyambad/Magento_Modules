<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */

namespace Redington\Catalog\Block\Product\View;

class Stock extends \Magento\Framework\View\Element\Template
{
    /**
     * 
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */         
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Redington\Catalog\Helper\Data $helperData,
        array $data = []
    ) {     
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }
    
    /**
     * Get customer Data
     * 
     * @return array | boolean
     */
    public function getCustomerEmail() {
        $custEmail = $this->helperData->getCustomerData('email'); 
        return $custEmail;
    }
}