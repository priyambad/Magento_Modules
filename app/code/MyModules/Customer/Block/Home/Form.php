<?php
namespace Redington\Customer\Block\Home;

class Form extends \Magento\Framework\View\Element\Template
{
	protected $_customerSession;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        array $data = [])
        {
        	$this->_customerSession = $customerSession;
        	parent::__construct($context, $data);
        } 

        public function customerLoggedIn()
        {
        	return $this->_customerSession->isLoggedIn();
        }
}
