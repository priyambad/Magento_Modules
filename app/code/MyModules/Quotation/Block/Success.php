<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */
namespace Redington\Quotation\Block;

/**
 * Quote block
 * 
 */
class Success extends \Magento\Framework\View\Element\Template
{
    /**
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    
    /**
     * 
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Checkout\Model\Cart $cart,
		\Magento\Quote\Model\QuoteFactory $quoteFactory,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->cart = $cart;
		$this->quoteFactory = $quoteFactory;
    }
    /**
     * 
     * @return string
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    
    }
    /**
     * Retrieve current quote
     *
     * @return string
     */
    public function getQuoteId()
    {   
        return $this->getRequest()->getParam('quote_id');
    }   
	public function getQuotationId($quoteId)
    {   
        $quote = $this->quoteFactory->create()->load($quoteId);
		return $quote->getQuotationId();
    }
}
