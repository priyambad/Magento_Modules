<?php

namespace Redington\Transactions\Observer;

class SetCredit implements \Magento\Framework\Event\ObserverInterface {
    public function __construct(
        \Redington\Company\Helper\Data $companyHelper,
        \Redington\Transactions\Helper\Data $transactionHelper,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\CompanyCredit\Model\ResourceModel\CreditLimit\CollectionFactory $creditLimitCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository

    ) {
        $this->companyHelper = $companyHelper;
        $this->transactionHelper = $transactionHelper;
        $this->creditDataProvider = $creditDataProvider;
        $this->creditLimitCollectionFactory = $creditLimitCollectionFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $sapResponse = $this->transactionHelper->getSapCreditLimit($adminId);
        if($sapResponse) {
            $companyId = $this->companyHelper->retrieveCompanyId();
            $availableCreditLimit = $this->creditDataProvider->get($companyId)->getAvailableLimit();
            if($sapResponse != $availableCreditLimit) {
                $balance = $this->creditDataProvider->get($companyId)->getBalance();
                $creditLimit = $sapResponse - $balance;
                $creditCollection = $this->creditLimitCollectionFactory->create()->addFieldToFilter('company_id',$companyId)->getFirstItem();
                $creditCollection->setCreditLimit($creditLimit)->save();
            }
        }
       
        $sapOverdueResponse = $this->transactionHelper->getOverdueAmount($adminId);

        if($sapOverdueResponse)
        {
            $this->customerSession->setOverdueAmount($sapOverdueResponse); 
        }
    }
}