<?php

namespace Redington\AddressApproval\Block\Customer\Address;

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
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Redington\AddressApproval\Helper\Data $redingtonHelper,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->serialize = $serialize;
        $this->redingtonHelper = $redingtonHelper;
        parent::__construct($context, $data);
    }
    /**
     * getComments function
     * this function returns comments for address
     * @return comments
     */
    public function getComments() {
        $approvalId = $this->coreRegistry->registry('address_approval_id');
        try{
            $approvalAddress = $this->addressApprovalFactory->create()->load($approvalId);
            $commenstData = $approvalAddress->getComments();
            if($commenstData) {
                $comments = $this->serialize->unserialize($commenstData);
                return $comments;
            }else {
                return false;
            }
        }catch(\Exception $e){
            $this->redingtonHelper->logMessage('could not get comments');
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
        $approvalAddress = $this->addressApprovalFactory->create()->load($approvalId);
        $approvalStatus = $approvalAddress->getStatus();
        if($approvalStatus == 1 || $approvalStatus == 100){
            return true;
        }else{
            return false;
        }
    }
}
