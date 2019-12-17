<?php

namespace Redington\Transactions\Observer;

class BeforeOrder implements \Magento\Framework\Event\ObserverInterface {
    public function __construct(
        \Redington\Company\Helper\Data $companyHelper,
        \Redington\Transactions\Helper\Data $transactionHelper,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\CompanyCredit\Model\ResourceModel\CreditLimit\CollectionFactory $creditLimitCollectionFactory
    ) {
        $this->companyHelper = $companyHelper;
        $this->transactionHelper = $transactionHelper;
        $this->creditDataProvider = $creditDataProvider;
        $this->creditLimitCollectionFactory = $creditLimitCollectionFactory;
    }
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $this->logMessage('admin id is '.$adminId);
        $sapResponse = $this->transactionHelper->getSapCreditLimit($adminId);
        if($sapResponse) {
            $this->logMessage('credit in Sap is  '.$sapResponse);
            $companyId = $this->companyHelper->retrieveCompanyId();
            $availableCreditLimit = $this->creditDataProvider->get($companyId)->getAvailableLimit();
            if($sapResponse != $availableCreditLimit) {
                $balance = $this->creditDataProvider->get($companyId)->getBalance();
                $creditLimit = $sapResponse - $balance;
                $creditCollection = $this->creditLimitCollectionFactory->create()->addFieldToFilter('company_id',$companyId)->getFirstItem();
                $creditCollection->setCreditLimit($creditLimit)->save();
                $this->logMessage('updated credit limit');
            }
        }
    }
    private function logMessage($message) {
        $filePath = '/var/log/Redington_CreditUpdateBeforeOrder.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}