<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */
 
namespace Redington\Checkout\Controller\Documents;

class Uploaddoc extends \Magento\Framework\App\Action\Action {

	/**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;
	
	/**
     * @var \Redington\Configuration\Helper\Data
     */
    protected $helperData;
	
	/**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;
	
	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
	
	/**
     * @var \Magento\Company\Model\CompanyUser
     */
    protected $companyUser;
	
	/**
     * @var \Magento\Company\Model\CompanyFactory
     */
    protected $companyFactory;
	
	/**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
	
	/**
     * @var \Redington\Checkout\Helper\Data
     */
    protected $redingtonCheckoutHelper;
	/**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Redington\Configuration\Helper\Data $helperData
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Company\Model\CompanyUser $companyUser
	 * @param \Magento\Company\Model\CompanyFactory $companyFactory
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Redington\Configuration\Helper\Data $helperData,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Company\Model\CompanyUser $companyUser,
		\Magento\Company\Model\CompanyFactory $companyFactory,
		\Magento\Customer\Model\Session $customerSession,
		\Redington\Checkout\Helper\Data $redingtonCheckoutHelper
    ) {
		$this->helperData = $helperData;
        $this->resultJsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;
		$this->companyUser = $companyUser;
		$this->companyFactory = $companyFactory;
		$this->customerSession = $customerSession;
		$this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        if(!$this->getRequest()->isPost()){
		
			$response = [
						'error' => true,
						'message' => 'Not Post'
				];
			return $resultJson->setData($response);	
		}
		
		$postData = $this->getRequest()->getParams();
		$fileType = $postData['file_type'];
		try {
			if($this->customerSession->isLoggedIn()) {
				
				$companyName = $this->getLoggedInUserCompanyName();
				$loggedIdCustomerId = $this->customerSession->getCustomer()->getId();

				$container = $this->scopeConfig->getValue('redington_documents/general/container_path');
				
				$containerPath = $container.$companyName."/order"."/".$loggedIdCustomerId."/".$fileType;
				
				if($_FILES) {
					foreach ($_FILES as $code => $file) {
						$name = $file['name'];
						$content = file_get_contents($file['tmp_name']);
						$type = $file['type'];
						$savedFile = $this->helperData->saveBlobStorage($content,$name,$type,$containerPath);
					}
					$response = [
						'success' => true,
						'doc_url' => $savedFile,
						'fileType' => $fileType
					];	
				}else{
					$response = [
						'success' => false,
						'doc_url' => "",
						'fileType' => $fileType
					];
				}
			}else{
				 $response = [
					'success' => false,
					'message' => 'Please login..',
					'fileType' => $fileType
				];
			}
			return $resultJson->setData($response);
		}
		catch (\Exception $e) {
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
				'fileType' => $fileType
            ];
            $this->redingtonCheckoutHelper->debugLog("ERROR(Uploaddoc):".$e->getMessage(),false);
            return $resultJson->setData($response);
        }
    }
	
	/**
     * Retrieve loggedin customer company name
     *
     * @return string
     */
	public function getLoggedInUserCompanyName(){
		$companyName = "";
		$companyId = $this->companyUser->getCurrentCompanyId();
        $companyName = $this->companyFactory->create()->load($companyId)->getData('company_name');
		return $companyName;
	}
}