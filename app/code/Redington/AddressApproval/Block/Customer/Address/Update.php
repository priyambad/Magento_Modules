<?php

namespace Redington\AddressApproval\Block\Customer\Address;

class Update extends \Magento\Framework\View\Element\Template
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
   
  
    public function getAddressId() {
        return $this->coreRegistry->registry('address_id');
    }
}
