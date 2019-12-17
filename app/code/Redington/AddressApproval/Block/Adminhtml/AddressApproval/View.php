<?php
namespace Redington\AddressApproval\Block\Adminhtml\AddressApproval;
 
use Magento\Backend\Block\Widget\Form\Container;
 
class View extends Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
 
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->addressApprovalFactory = $addressApprovalFactory;
        parent::__construct($context, $data);
    }
 
    /**
     * Department edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Redington_AddressApproval';
        $this->_controller = 'adminhtml_addressApproval';
 
        parent::_construct();

        
            if ($this->_isAllowedAction('Redington_Address::address_save')) {
            $this->buttonList->update('save', 'label', __('Submit'));
           
            }
        else {
            $this->buttonList->remove('save');
        }
 
    }
 
    /**
     * Get header with Department name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        
        return 'Address Approval';
    }
 
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        $model = $this->_coreRegistry->registry('address_approval');
        
        $addressId = $model->getId();
        $approvalData = $this->getApprovalData($addressId);
        $status = $approvalData->getStatus();
        if($status==0)
        {
            return $this->_authorization->isAllowed($resourceId);
        }
    }
 
    public function getApprovalData($address_id) {
        $addressApproval = $this->addressApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$address_id);
        if($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
            return $addressApproval;
        }
        return false;
    }
    
}