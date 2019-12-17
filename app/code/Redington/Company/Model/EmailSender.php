<?php

namespace Redington\Company\Model;

class EmailSender extends \Magento\Company\Model\Email\Sender {
    public function __construct(
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Company\Model\Email\Transporter $transporter,
            \Magento\Customer\Api\CustomerNameGenerationInterface $customerViewHelper,
            \Magento\Company\Model\Email\CustomerData $customerData,
            \Magento\Company\Model\Config\EmailTemplate $emailTemplateConfig,
            \Magento\Company\Api\CompanyRepositoryInterface $companyRepository
        ) {
        parent::__construct($storeManager, $scopeConfig, $transporter, $customerViewHelper, $customerData, $emailTemplateConfig, $companyRepository);
    }
    public function sendCustomerCompanyAssignNotificationEmail(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $companyId
    ) {
        return $this;
    }
}