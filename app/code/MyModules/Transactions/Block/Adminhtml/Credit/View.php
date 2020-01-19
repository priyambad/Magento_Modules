<?php
namespace Redington\Transactions\Block\Adminhtml\Credit;
 
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
        \Redington\Transactions\Model\CreditFactory $creditFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->creditFactory = $creditFactory;
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
        $this->_blockGroup = 'Redington_Transactions';
        $this->_controller = 'adminhtml_credit';
    
        parent::_construct();
       
 
        if ($this->_isAllowedAction('Redington_Transactions::Credit_save')) {
            $this->buttonList->update('save', 'label', __('Submit'));
//            
        } else {
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
        
        return 'Credit Approval';
    }
 
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        
        $creditId = $this->_coreRegistry->registry('credit_request_id');
        $approvalData = $this->getApprovalData($creditId);
        $status = $approvalData->getStatus();
        if($status==0)
        {
            return $this->_authorization->isAllowed($resourceId);
        }
        
    }

    public function getApprovalData($creditId) {
        $credit = $this->creditFactory->create()->load($creditId);
        return $credit;
    }
 
    
}