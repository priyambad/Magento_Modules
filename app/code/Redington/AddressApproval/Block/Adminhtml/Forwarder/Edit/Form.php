<?php
namespace Redington\AddressApproval\Block\Adminhtml\Forwarder\Edit;
 
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
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Redington\AddressApproval\Helper\Data $redingtonHelper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
        $this->serialize = $serialize;
        $this->companyFactory = $companyFactory;
        $this->redingtonHelper = $redingtonHelper;
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
        $this->setId('forwarder_approval_form');
        $this->setTitle(__('Forwarder Information'));
    }
    
    public function getApprovalData($address_id) {
        $addressApproval = $this->forwarderApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$address_id);
        if($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
            return $addressApproval;
        }
        return false;
    }
 
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Maxime\Jobs\Model\Department $model */
        $model = $this->_coreRegistry->registry('address_approval');
        
        $addressId = $model->getId();
        
        $approvalData = $this->getApprovalData($addressId);
        $companyName = $approvalData->getCompanyName();
        $countryName = $approvalData->getCountry();
        $comments=unserialize(empty($approvalData->getComments()) ? '' : $approvalData->getComments());
        $comment=$comments[0]["content"];
        $status = $approvalData->getStatus() == 0 ? null : $approvalData->getStatus();
        $disable=($status!=0)?1:0; 
        $companyId = $approvalData->getCompanyId();   
        try{
            $parentCompanyName = $this->companyFactory->create()->load($companyId)->getCompanyName();
        }catch(\Exception $e){
            $this->redingtonHelper->logMessage('could not get comapany name '.$e->getMessage());
            $parentCompanyName = '';
        }
        $addressData = [
            'entity_id' => $model->getId(),
            'parent_name' => $parentCompanyName,
            'company_name' => $companyName,
            'country_name' => $countryName,
            'city' => $model->getCity(),
            'postalcode' => $model->getPostCode(),
            'phone' => $model->getTelePhone(),
            'street1' => $model->getStreet()[0],
            'street2' => sizeof($model->getStreet()) > 1 ? $model->getStreet()[1] : null,
            'street3' => sizeof($model->getStreet()) > 2 ? $model->getStreet()[2] : null,
            'status' => $status,
            'comment' => $comment
        ]; 
 
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
 
        $form->setHtmlIdPrefix('address_');
 
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Shipping Address'), 'class' => 'fieldset-wide']
        );
 
        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }

        $fieldset->addField(
            'parent_name',
            'text',
            ['name' => 'partner', 'label' => __('Company Name'), 'title' => __('Company Name'), 'readonly' => true]
        );
 
        $fieldset->addField(
            'company_name',
            'text',
            ['name' => 'company', 'label' => __('Forwarder Name'), 'title' => __('Forwarder Name'), 'required' => true, 'readonly' => true]
        );

        $fieldset->addField(
            'street1',
            'text',
            ['name' => 'street1', 'label' => __('Street 1'), 'title' => __('Street 1'), 'required' => true, 'readonly' => true]
        );
        
        if(sizeof($model->getStreet()) > 1){
            $fieldset->addField(
                'street2',
                'text',
                ['name' => 'street2', 'label' => __('Street 2'), 'title' => __('Street 2'), 'required' => false, 'readonly' => true]
            );
        }
        
        if(sizeof($model->getStreet()) > 2){
            $fieldset->addField(
                'street3',
                'text',
                ['name' => 'street3', 'label' => __('Street 3'), 'title' => __('Street 3'), 'required' => false, 'readonly' => true]
            );
        }
        
        $fieldset->addField(
            'country_name',
            'text',
            ['name' => 'country_name', 'label' => __('Country'), 'title' => __('Country'), 'required' => true, 'readonly' => true]
        );
        
        $fieldset->addField(
            'city',
            'text',
            ['name' => 'city', 'label' => __('City'), 'title' => __('City'), 'required' => true, 'readonly' => true]
        );
 
        $fieldset->addField(
            'postalcode',
            'text',
            ['name' => 'postalcode', 'label' => __('Postal Code'), 'title' => __('Postal Code'), 'readonly' => true]
        );
        
        $fieldset->addField(
            'phone',
            'text',
            ['name' => 'phone', 'label' => __('Mobile'), 'title' => __('Mobile'), 'required' => true, 'readonly' => true]
        );
        
        
        //Custom FieldSet
        
        $customFieldset = $form->addFieldset(
            'custom_base_fieldset',
            ['legend' => __('Shipping Address Documents'), 'class' => 'fieldset-wide']
        );
        $customFieldset->addType(
            'mycustomfield',
            '\Redington\AddressApproval\Block\Adminhtml\Forwarder\Edit\Renderer'
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
 
        $form->setValues($addressData);
        $form->setUseContainer(true);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
}