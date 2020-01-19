<?php

namespace Redington\AddressApproval\Controller\Adminhtml\Address;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Undocumented function
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Redington\AddressApproval\Helper\Data $approvalHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Company\Model\CompanyUser $companyUser
     * @param \Redington\Company\Model\DocumentsFactory $documentsApproval
     * @param \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Redington\AddressApproval\Helper\Data $approvalHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Redington\Company\Model\DocumentsFactory $documentsApproval,
        \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory,
        \Magento\Customer\Model\Session $customerSession

    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->_addressRepository = $addressRepository;
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->date = $date;
        $this->serialize = $serialize;
        $this->authSession = $authSession;
        $this->approvalHelper = $approvalHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyUser = $companyUser;
        $this->documentsApprovalFactory = $documentsApproval;
        $this->documentCollectionFactory = $documentCollectionFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function execute()
    {
        $addressId = $this->request->getParam('entity_id');
        $status = $this->request->getParam('status');
        $comment = $this->request->getParam('comment');

        $addressApproval = $this->addressApprovalFactory->create();

        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id', $addressId);
        if ($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
            $companyAdminId = $addressApproval->getParentId();
            $requestedById = $addressApproval->getRequestedBy();

            if ($comment != '') {
                $commentData = [
                    "time" => $this->date->gmtDate(),
                    "author" => $this->authSession->getUser()->getFirstName() . ' ' . $this->authSession->getUser()->getLastName(),
                    "content" => $comment
                ];
                //get old comments
                try {
                    $previousComments = $addressApproval->getComments();
                    if ($previousComments == null) {
                        $previousComments = [];
                    } else {
                        $previousComments = $this->serialize->unserialize($previousComments);
                    }
                } catch (\Exception $e) {
                    $previousComments = [];
                }
                array_unshift($previousComments, $commentData);
                $addressApproval->setComments($this->serialize->serialize($previousComments));
            }
        }

        $address = $this->_addressRepository->getById($addressId);

        if ($status ==2) {
            $approvedDocuments = $addressApproval->getPendingDocuments();
            $approvedDocumentsData = $approvedDocuments ? $this->serialize->unserialize($approvedDocuments) : [];
            $addressType = $this->customerSession->getAddressType();
            $companyEntityId = $this->documentCollectionFactory->create()->addFieldToFilter('company_id', $addressApproval->getCompanyId())->getFirstItem()->getId();
            $documentRequest = $this->documentsApprovalFactory->create()->load($companyEntityId);
            $documentData = $documentRequest->getDocumentDetails() ? $this->serialize->unserialize($documentRequest->getDocumentDetails()) : [];

            $documentName = '';
            $documentNumber = '';
            $docUrl = '';
            $documentExpiry = '';
            $documentIssue = '';

            foreach ($approvedDocumentsData as $key => $value) {
                if (strpos(strtolower($value['documentName'] ), 'trade license') !== false) {
                    $documentName = $value['documentName'];
                    $documentNumber = $value['documentNumber'];
                    $docUrl = $value['docUrl'];
                    $documentExpiry = $value['documentExpiry'];
                    $documentIssue = $value['documentIssue'];
                }
            }

            if ($addressApproval->getRequestType() == "DefaultBilling") {
               
                if (!empty($documentData['billing'])) {
                    foreach ($documentData['billing'] as $key => $value) {
                        if ($value['document_name'] == 'Trade license along with partner page') {
                            $documentData['billing'][$key]['document_number'] = $documentNumber;
                            $documentData['billing'][$key]['document_url'] = $docUrl;
                            $documentData['billing'][$key]['expiry_date'] = $documentExpiry;
                            $documentData['billing'][$key]['issue_date'] = $documentIssue;
                        }
                    }
                    $adminId = $addressApproval->getParentId();
                    $customer = $this->customerRepositoryInterface->getById($adminId);
                    $customer->setCustomAttribute('z_trade_license_valid',1);
                    $this->customerRepositoryInterface->save($customer);
                    $redirectUrl = 'customer/address/index';
                    $type = 'Billing Address';
                }
                $documentData = $this->serialize->serialize($documentData);
                $documentRequest->setDocumentDetails($documentData)->save();
            } elseif ($addressApproval->getRequestType() == "DefaultShipping") {
                if (!empty($documentData['shipping'])) {
                    foreach ($documentData['shipping'] as $key => $value) {
                        if (strpos(strtolower($value['document_name'] ), 'trade license') !== false) {
                            $documentData['shipping'][$key]['document_number'] = $documentNumber;
                            $documentData['shipping'][$key]['document_url'] = $docUrl;
                            $documentData['shipping'][$key]['expiry_date'] = $documentExpiry;
                            $documentData['shipping'][$key]['issue_date'] = $documentIssue;
                        }
                    }
                    $address = $this->_addressRepository->getById($addressId);
                    $address->setCustomAttribute('is_valid', 1);
                    $this->_addressRepository->save($address);
                    $redirectUrl = 'customer/address/index';
                    $type = 'Shipping Address';
                }
                $documentData = $this->serialize->serialize($documentData);
                $documentRequest->setDocumentDetails($documentData)->save();
            } else {
                $approvedDocuments = $addressApproval->getPendingDocuments();
                $addressApproval->setDocuments($approvedDocuments);
            }

            $address = $this->_addressRepository->getById($addressId);
            $sapAddressId = $address->getCustomAttribute('sap_address_id') ? $address->getCustomAttribute('sap_address_id')->getValue() : null;

            if (!$sapAddressId) {
                $sapAddressNumber = $this->approvalHelper->sendAddressDataToSap($address, 'address', $companyAdminId);

                if ($sapAddressNumber) {
                    $address = $this->_addressRepository->getById($addressId);
                    $address->setCustomAttribute('approved', $status-1);
                    $address->setCustomAttribute('is_valid', 1);
                    $address->setCustomAttribute('sap_address_id', $sapAddressNumber);
                    $this->_addressRepository->save($address);
                    //set status
                    $addressApproval->setStatus($status);
                    $addressApproval->save();
                    $this->approvalHelper->sendApprovalActionMail('address', $address, $companyAdminId, $requestedById, 'approved', $comment);
                    $this->messageManager->addSuccessMessage(__('Address Approved.'));
                } else {
                    $this->messageManager->addError(__('Could not process the request.'));
                }
            } else {
                $address = $this->_addressRepository->getById($addressId);
                $address->setCustomAttribute('approved', $status-1);
                $address->setCustomAttribute('is_valid', 1);

                $this->_addressRepository->save($address);
                //set status
                $addressApproval->setStatus($status);
                $addressApproval->save();
                $this->approvalHelper->sendApprovalActionMail('address', $address, $companyAdminId, $requestedById, 'approved', $comment);
                $this->messageManager->addSuccessMessage(__('Address Approved.'));
            }
        } else {
            if ($addressApproval->getRequestType() == "DefaultBilling") {
                $adminId = $addressApproval->getParentId();
                $customer = $this->customerRepositoryInterface->getById($adminId);
                // $customer->setCustomAttribute('z_trade_license_valid',0);
                $this->customerRepositoryInterface->save($customer);
                $address = $this->_addressRepository->getById($addressId);
                $address->setCustomAttribute('approved', $status-1);
                $this->_addressRepository->save($address);
                //set status
                $addressApproval->setStatus($status);
                $addressApproval->save();
                $this->approvalHelper->sendApprovalActionMail('address', $address, $companyAdminId, $requestedById, 'rejected', $comment);
                $this->messageManager->addSuccessMessage(__('Address Rejected.'));
            } elseif ($addressApproval->getRequestType() == "DefaultShipping") {
                $address = $this->_addressRepository->getById($addressId);
                $address->setCustomAttribute('approved', $status-1);
                // $address->setCustomAttribute('is_valid',0);
                $this->_addressRepository->save($address);
                //set status
                $addressApproval->setStatus($status);
                $addressApproval->save();
                $this->approvalHelper->sendApprovalActionMail('address', $address, $companyAdminId, $requestedById, 'rejected', $comment);
                $this->messageManager->addSuccessMessage(__('Address Rejected.'));
            } else {
                $address = $this->_addressRepository->getById($addressId);
                $address->setCustomAttribute('approved', $status-1);
                $this->_addressRepository->save($address);
                //set status
                $addressApproval->setStatus($status);
                $addressApproval->save();
                $this->approvalHelper->sendApprovalActionMail('address', $address, $companyAdminId, $requestedById, 'rejected', $comment);
                $this->messageManager->addSuccessMessage(__('Address Rejected.'));
            }
        }

        $this->_redirect('approval/address/index');
    }
}
