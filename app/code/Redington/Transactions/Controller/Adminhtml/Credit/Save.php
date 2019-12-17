<?php

namespace Redington\Transactions\Controller\Adminhtml\Credit;

class Save extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Redington\Transactions\Model\CreditFactory $creditFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\CompanyCredit\Model\ResourceModel\CreditLimit\CollectionFactory $creditLimitCollectionFactory,
        \Redington\Transactions\Helper\Data $transactionHelper,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->request = $request;
        $this->creditFactory = $creditFactory;
        $this->date = $date;
        $this->serialize = $serialize;
        $this->authSession = $authSession;
        $this->creditLimitCollectionFactory = $creditLimitCollectionFactory;
        $this->creditDataProvider = $creditDataProvider;
        $this->transactionHelper = $transactionHelper;
        $this->companyFactory = $companyFactory;
        $this->_storeManager = $storeManager;
    }

    public function execute()
    {
        
        $this->shouldDeduct = false;
        $creditId = $this->request->getParam('entity_id');
        $status = $this->request->getParam('status');
        $comment = $this->request->getParam('comment');

        $credit = $this->creditFactory->create()->load($creditId);
            //set status
        $requestedCreditLimit = $credit->getRequestedCreditLimit();
        $oldStatus = $credit->getStatus();
        if($oldStatus == 2 ){
            if($status == 2){
                $this->messageManager->addError(__('Credit has already been approved.'));
                $this->_redirect('approval/credit/index');
                return;
            }else{
                $this->shouldDeduct = true;
            }
        }
        $companyId = $credit->getCompanyId();
        $adminId = $this->companyFactory->create()->load($companyId)->getSuperUserId();
        $outstandingBalance = $this->creditDataProvider->get($companyId)->getBalance();
        $credit->setStatus($status);
        $credit->setActionTakenBy($this->authSession->getUser()->getId());
        $credit->setActionDate($this->date->gmtDate());
        if($comment != '') {
            $commentData = [
                "time" => $this->date->gmtDate(),
                "author" => $this->authSession->getUser()->getFirstName().' '.$this->authSession->getUser()->getLastName(),
                "content" => $comment
            ];
            //get old comments
            $previousComments = $credit->getComments();
            if(!$previousComments){
                $previousComments = [];
            }
            else {
                $previousComments = $this->serialize->unserialize($previousComments);
            }
            array_unshift($previousComments, $commentData);
            $credit->setComments($this->serialize->serialize($previousComments));
        }
        
        $availableCreditLimit = $this->transactionHelper->getSapCreditLimit($adminId);
        $approvedCreditLimit = $availableCreditLimit;
        if($status ==2) {
            $message = 'Approved';
            $approvalStatus = 'approved';
            $approvedCreditLimit = $availableCreditLimit + $requestedCreditLimit - $outstandingBalance;
            $availableCreditLimit = $approvedCreditLimit + $outstandingBalance;
        }else {
            $approvedCreditLimit = $availableCreditLimit - $outstandingBalance;
            $message = 'Rejected';
            $approvalStatus = 'rejected';
        }
        $savedInSap = $this->transactionHelper->setCreditLimit($adminId,$availableCreditLimit);

        if($savedInSap){
            $credit->save();
            $creditCollection = $this->creditLimitCollectionFactory->create()->addFieldToFilter('company_id',$companyId)->getFirstItem();
            $creditCollection->setCreditLimit($approvedCreditLimit)->save();
            $requestedCreditLimit = $this->_storeManager->getStore()->getBaseCurrencyCode().' '.$requestedCreditLimit;
            $availableCreditLimit = $this->_storeManager->getStore()->getBaseCurrencyCode().' '.$availableCreditLimit;
            $this->transactionHelper->sendApprovalActionMail($requestedCreditLimit,$adminId,$credit->getRequester(),$approvalStatus,$availableCreditLimit,$comment);
            $this->messageManager->addSuccessMessage(__('Credit '.$message.'.'));
        }else{
            $this->messageManager->addError(__('Unable to process the request.'));
        }
        $this->_redirect('approval/credit/index');
    }
}