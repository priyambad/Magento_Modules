<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */

namespace Redington\Company\Controller\customer;

/**
 * Class OrderViewAuthorization
 */
class Checkuserlimit extends \Magento\Framework\App\Action\Action {

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var \Redington\Company\Helper\Data
     */
    public $redingtonHelperData;

    /**
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Redington\Company\Helper\Data $redingtonHelperData
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $pageFactory, \Magento\Framework\Controller\Result\JsonFactory $jsonFactory, \Redington\Company\Helper\Data $redingtonHelperData
    ) {
        $this->pageFactory = $pageFactory;
        $this->resultJsonFactory = $jsonFactory;
        $this->redingtonHelperData = $redingtonHelperData;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            $response = [
                'status' => 'error',
                'message' => 'Not Post',
                'payload' => ''
            ];
            return $resultJson->setData($response);
        }

        try {
            $totalUsers = $this->redingtonHelperData->getCompanyUserCount();
            $totalUsers = $totalUsers - 1;
            $maxlimit = $this->redingtonHelperData->getMaxUsers();
            if($totalUsers < $maxlimit) {
                $response = [
                    'status' => 'ok',
                    'message' => 'You can add user upto '.$maxlimit. ', now total users are '.$totalUsers,
                    'data' => ''
                ];  
            } else {
                $response = [
                    'status' => 'notok',
                    'message' => 'You can not add more than '.$maxlimit. ' users , now total users are '.$totalUsers,
                    'payload' => ''
                ]; 
            }            
             
            return $resultJson->setData($response);
            
        } catch (Exception $ex) {
            $response = [
                'error' => true,
                'message' => $ex->getMessage(),
                'is_display_message' => false
            ];
        }
    }

}
