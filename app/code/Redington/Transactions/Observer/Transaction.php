<?php

namespace Redington\Transactions\Observer;

class Transaction implements \Magento\Framework\Event\ObserverInterface {
    public function __construct(
        \Redington\Transactions\Model\TransactionFactory $transactionFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Customer\Model\Session $customerSession
    ) {
        
        $this->transactionFactory = $transactionFactory;
        $this->companyHelper = $companyHelper;
        $this->date = $date;
        $this->creditDataProvider = $creditDataProvider;
        $this->customerSession = $customerSession;
    }
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $filePath = '/var/log/Redington_Order.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('starting execution of observer');
        $order = $observer->getEvent()->getOrder();
        $transaction = $this->transactionFactory->create();
        $logger->info('created transaction object');
		$transaction->setCompanyId($this->companyHelper->retrieveCompanyId());
		$transaction->setTransactionId($order->getIncrementId());
        $transaction->setTransactionDate($this->date->gmtDate());
        $logger->info('fetched date and company Id');
        $transaction->setTransactionAmount($order->getBaseGrandTotal());
        $logger->info('Transaction amount : '.$order->getBaseGrandTotal());
        $transaction->setRemainingCreditLimit($this->getCreditLimit());
        $logger->info('Credit limit : '.$this->getCreditLimit());
        $transaction->setActionTakenBy($this->getCustomerId());
        $logger->info('action taken by : '.$this->getCustomerId());
        $transaction->setTransactionType($this->getTransactionType($order));
        $logger->info('Transaction type : '.$this->getTransactionType($order));
        try{
            $transaction->save();
        }
        catch(\Exception $e){
            $logger->info('Error occured : '.$e->getMessage());
        }
    }
    private function getCreditLimit() {
        $filePath = '/var/log/Redington_Order.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('getting credit limit');
        $companyId = $this->companyHelper->retrieveCompanyId();
        $logger->info('company Id : '.$companyId);
        $logger->info('credit limit '.$this->creditDataProvider->get($companyId)->getAvailableLimit());
        return $this->creditDataProvider->get($companyId)->getAvailableLimit();
    }
    private function getCustomerId() {
        return $this->customerSession->getCustomer()->getId();
    }
    private function getTransactionType($order) {
        return $order->getPayment()->getMethodInstance()->getTitle();
    }
}