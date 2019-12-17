<?php

namespace Redington\Transactions\Controller\Credit;


class Save extends \Magento\Framework\App\Action\Action {

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Redington\Transactions\Model\CreditFactory $creditFactory
     * @param \Magento\Company\Model\CompanyFactory $companyFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Redington\Transactions\Model\CreditFactory $creditFactory,
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
        $this->companyFactory = $companyFactory;
        $this->_storeManager = $storeManager;
        $this->session = $session;
        parent::__construct($context);
    }
    
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $postData = $this->getRequest()->getPost();
        
        $accountName = $postData['account-name'];
        $companyId = $this->companyHelper->retrieveCompanyId();
        $requester = $this->customerSession->getCustomerId();
        $requestDate = $this->date->gmtDate();
        $requestedCredit = $postData['requested-credit'];
        $availableCredit = $this->creditDataProvider->get($companyId)->getAvailableLimit();
        $sapAcc = $this->customerRepositoryInterface->getById($this->companyHelper->getCompanyAdminId())
                    ->getCustomAttribute('z_sap_account_number')->getValue();

        try {
            $credit = $this->creditFactory->create();
            $credit->setCompanyId($companyId);
            $credit->setRequester($requester);
            $credit->setRequestDate($requestDate);
            $credit->setRequestedCreditLimit($requestedCredit);
            $credit->setAvailableCreditLimit($availableCredit);
            $credit->setSapAccNo($sapAcc);
            $credit->setAccountName($accountName);
            $credit->setCustomerStoreId($this->_storeManager->getStore()->getId());
            $credit->save();
            $this->session->setCreditRequestId($credit->getEntityId());
            $response = [
                'success' => true,
                'credit_id' => $credit->getId(),
                'company_name' => $this->companyHelper->retrieveCompanyName()
            ];
            return $resultJson->setData($response);
        }catch(\Exception $e) {
            $response = [
                'error' => true,
                'message' => $e->getMessage()
            ];
            return $resultJson->setData($response);
        }
    }
}