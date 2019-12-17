<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Controller\Quote;

use Magento\Framework\View\Result\PageFactory;

/**
 * Quotation As Quotation Login action
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory$resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory
    )
    {
           $this->resultPageFactory = $resultPageFactory;
           parent::__construct($context);
    }
    /**
     * Login as customer action
     *
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {   
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Quotation'));
        return $resultPage;
    }
}
