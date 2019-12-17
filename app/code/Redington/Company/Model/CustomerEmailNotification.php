<?php

namespace Redington\Company\Model;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerEmailNotification extends \Magento\Customer\Model\EmailNotification {
    public function __construct(
            \Magento\Customer\Model\CustomerRegistry $customerRegistry,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
            \Magento\Customer\Helper\View $customerViewHelper,
            \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver = null
        ) {
        parent::__construct($customerRegistry, $storeManager, $transportBuilder, $customerViewHelper, $dataProcessor, $scopeConfig, $senderResolver);
    }
    public function newAccount(
        CustomerInterface $customer,
        $type = self::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        return;
    }
}