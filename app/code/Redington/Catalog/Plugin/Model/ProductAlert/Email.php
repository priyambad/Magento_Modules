<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */

namespace Redington\Catalog\Plugin\Model\ProductAlert;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\ScopeInterface;

class Email 
{
    /**
     * default 
     */
    const SCOPE_DEFAULT = 'default';
    
    /**
     *
     * @var \Redington\Catalog\Helper\Data
     */
    private $helperData;
    
    public function __construct(
        TransportBuilder $transportBuilder,
        \Redington\Catalog\Helper\Data $helperData
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->helperData = $helperData;
    }
    public function aroundSend(
        \Magento\ProductAlert\Model\Email $subject,
        \Closure $proceed
    ) { 
        $moduleEnabled = $this->helperData->isModuleEnabled(self::SCOPE_DEFAULT, '');
        if($moduleEnabled) {
            $companyUsersEmailArray = $this->helperData->getCompanyUsersEmail('1'); 
            $this->_transportBuilder->addTo($companyUsersEmailArray, "Team");
        }
        return $proceed();
    }
}