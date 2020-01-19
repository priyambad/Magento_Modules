<?php

namespace Redington\AddressApproval\Block\Customer\Forwarder;

class View extends \Magento\Framework\View\Element\Template
{

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
        $this->serialize = $serialize;
        parent::__construct($context, $data);
    }
    public function getComments() {
        $approvalId = $this->coreRegistry->registry('address_approval_id');
        $approvalAddress = $this->forwarderApprovalFactory->create()->load($approvalId);
        $commenstData = $approvalAddress->getComments();
        if($commenstData) {
            $comments = $this->serialize->unserialize($commenstData);
            return $comments;
        }else {
            return false;
        }
    }
    public function getAddressId() {
        return $this->coreRegistry->registry('address_id');
    }
    /**
     * canShowAction function
     * this function returns if we can show edit button on status page.
     * @return boolean
     */
    public function canShowAction(){
        $approvalId = $this->coreRegistry->registry('address_approval_id');
        $approvalAddress = $this->forwarderApprovalFactory->create()->load($approvalId);
        $approvalStatus = $approvalAddress->getStatus();
        if($approvalStatus == 1 || $approvalStatus == 100){
            return true;
        }else{
            return false;
        }
    }
}
