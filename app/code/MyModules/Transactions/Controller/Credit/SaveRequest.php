<?php

namespace Redington\Transactions\Controller\Credit;


class SaveRequest extends \Magento\Framework\App\Action\Action {

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Redington\Transactions\Model\CreditFactory $creditFactory,
        \Redington\Transactions\Helper\Data $transactionHelper,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Session\SessionManagerInterface $session
        ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->companyHelper = $companyHelper;
        $this->creditDataProvider = $creditDataProvider;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerSession = $customerSession;
        $this->date = $date;
        $this->creditFactory = $creditFactory;
        $this->transactionHelper = $transactionHelper;
        $this->companyFactory = $companyFactory;
        $this->_storeManager = $storeManager;
        $this->session = $session;
        parent::__construct($context);
    }
    
    public function execute()
    {

        try {
            $creditId = $this->session->getCreditRequestId();
            $credit = $this->creditFactory->create()->load($creditId);
            $requestedCredit = $credit->getRequestedCreditLimit();
            $credit->setStatus(0);
            $credit->save();
            
            $companyId = $this->companyHelper->retrieveCompanyId();
            $requester = $this->customerSession->getCustomerId();

            $companyAdminId = $this->companyFactory->create()->load($companyId)->getSuperUserId();
            $requestedCredit = $this->_storeManager->getStore()->getBaseCurrencyCode().' '.$requestedCredit;
            $this->transactionHelper->sendApprovalRequestMail($requestedCredit,$companyAdminId,$requester,$this->companyHelper->retrieveCompanyName());
            $this->messageManager->addSuccessMessage(__('Credit request has been sent for approval.'));
            $this->_redirect('customer/credit/index');

            return $resultJson->setData($response);
        }catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to request for credit.'));
            $this->_redirect('customer/credit/index');
        }
    }
}