<?php
namespace Redington\Transactions\Block\Adminhtml\Credit\Edit;
 
use \Magento\Backend\Block\Widget\Form\Generic;
 
class Form extends Generic
{
 
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
 
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Redington\Transactions\Model\CreditFactory $creditFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->creditFactory = $creditFactory;
        $this->serialize = $serialize;
        $this->companyFactory = $companyFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }
 
    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('credit_approval_form');
        $this->setTitle(__('Credit Request Information'));
        
    }
    
    public function getApprovalData($creditId) {
        $credit = $this->creditFactory->create()->load($creditId);
        return $credit;
    }
 
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        
        $creditId = $this->_coreRegistry->registry('credit_request_id');
        $approvalData = $this->getApprovalData($creditId);
        $companyId = $approvalData->getCompanyId();
        $company = $this->companyFactory->create()->load($companyId);
        
        
        $companyName = $company->getCompanyName();
        $requestDate = $approvalData->getRequestDate();
       
        $availableCreditLimit = $approvalData->getAvailableCreditLimit();
        $requestedCreditLimit = $approvalData->getRequestedCreditLimit();
        $sapAccountNo = $approvalData->getSapAccNo();
        $accountName = $approvalData->getAccountName();
        $status = $approvalData->getStatus() == 0 ? null : $approvalData->getStatus();
        $comments=unserialize(empty($approvalData->getComments()) ? '' : $approvalData->getComments());
        $disable=($status!=0)?1:0;
       
        $comment=$comments[0]["content"];
        
        $requestData = [
            'entity_id' => $creditId,
            'company_name' => $companyName,
            'request_date'=> $requestDate,
            'available_credit_amount' => $availableCreditLimit,
            'requested_credit_amount' => $requestedCreditLimit,
            'sap_account_number' => $sapAccountNo,
            'account_name' => $accountName,
            'status' => $status,
            'comment' =>$comment
            
        ]; 
 
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
 
        $form->setHtmlIdPrefix('address_');
 
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Credit request details'), 'class' => 'fieldset-wide']
        );
 
        if ($creditId) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }
 
        $fieldset->addField(
            'company_name',
            'text',
            ['name' => 'company', 'label' => __('Company Name'), 'title' => __('Company Name'), 'required' => true, 'readonly' => true]
        );
                
        $fieldset->addField(
            'request_date',
            'text',
            ['name' => 'request_date', 'label' => __('Request Date'), 'title' => __('Request Date'), 'required' => true, 'readonly' => true]
        );
 
        $fieldset->addField(
            'available_credit_amount',
            'text',
            ['name' => 'available_credit_amount', 'label' => __('Available Credit Amount'), 'title' => __('Available Credit Amount'), 'required' => true, 'readonly' => true]
        );
        
        $fieldset->addField(
            'requested_credit_amount',
            'text',
            ['name' => 'requested_credit_amount', 'label' => __('Requested Credit Amount'), 'title' => __('Requested Credit Amount'), 'required' => true, 'readonly' => true]
        );
        
        $fieldset->addField(
            'sap_account_number',
            'text',
            ['name' => 'sap_account_number', 'label' => __('Sap Account Number'), 'title' => __('Sap Account Number'), 'required' => true, 'readonly' => true]
        );

         $fieldset->addField(
            'account_name',
            'text',
            ['name' => 'account_name', 'label' => __('Account Name'), 'title' => __('Account Name'), 'required' => true, 'readonly' => true]
        );
        
        //if(sizeof($model->getStreet()) > 1){
        //   $fieldset->addField(
        //        'street2',
        //        'text',
        //        ['name' => 'street2', 'label' => __('Street 2'), 'title' => __('Street 2'), 'required' => false, 'readonly' => true]
        //    );
        //}
        
        //Custom FieldSet
        
        $customFieldset = $form->addFieldset(
            'custom_base_fieldset',
            ['legend' => __('Credit Request Documents'), 'class' => 'fieldset-wide']
        );
        $customFieldset->addType(
            'mycustomfield',
            '\Redington\Transactions\Block\Adminhtml\Credit\Edit\Renderer'
        );
        
        $customFieldset->addField(
            'custom',
            'mycustomfield',
            [
                'name'  => 'customdiv'

            ]
        );
        
        
        //ActionFieldSet
        $actionFieldset = $form->addFieldset(
            'action_base_fieldset',
            ['legend' => __('Action'), 'class' => 'fieldset-wide']
        );
        
        
        $actionFieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Select Status'),
                'title' => __('Status'),
                'options' => ['1' => __('Reject'), '2' => __('Approve')],
                'class' => 'select',
                'required' => true,
                'disabled'=> $disable
            ]
        );
        
        $actionFieldset->addField(
            'comment',
            'textarea',
            ['name' => 'comment', 'label' => __('Comment'), 'required' => true, 'title' => __('Comment')]
        );
 
        $form->setValues($requestData);
        $form->setUseContainer(true);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
}