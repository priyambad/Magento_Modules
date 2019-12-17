<?php
namespace Redington\Otp\Controller\Index;

class Verify extends \Magento\Framework\App\Action\Action
{
	/**
     * Flag for OTP expire ot not
     */
	protected $isOtpExpireFlag;
	
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
     * @var \Magento\Customer\Model\Session
     */
	protected $customerSession;
	
	/**
     * @var \Magento\Checkout\Model\Session 
     */
	protected $checkoutSession;
	
	
	/**
     * @var \Redington\Otp\Helper\Data
     */
	protected $redingtonOtpHelper;
	
	/**
     * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $pageFactory
	 * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Redington\Otp\Helper\Data $redingtonOtpHelper
     */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Redington\Otp\Helper\Data $redingtonOtpHelper
	)
	{

		$this->pageFactory = $pageFactory;
		$this->resultJsonFactory = $jsonFactory;
		$this->customerSession = $customerSession;
		$this->checkoutSession = $checkoutSession;
		$this->redingtonOtpHelper = $redingtonOtpHelper;
		parent::__construct($context);
	}
	/**
     * Varify OTP
     *
     * @return \Magento\Framework\Controller\Result\JsonFactory
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
		try {
			if($this->customerSession->isLoggedIn()) {
				$params = $this->getRequest()->getParams();
				$otpInput = $params['otp_input'];
				$sessionOtpVal = ($this->checkoutSession->getRandOtp()!="")? $this->checkoutSession->getRandOtp() : '';
				if($this->redingtonOtpHelper->isOtpExpire() == 'Y'){
					$response = [
						'error' => true,
						'message' => $this->redingtonOtpHelper->getConfigThoughVal('otp_expiration','m'),
						'is_display_message' => true
					];
					return $resultJson->setData($response);
				}
				if($sessionOtpVal != "" &&  $otpInput == $sessionOtpVal){
					$response = [
						'success' => true,
						'message' => 'Success'
					];
				}else{
					$response = [
						'error' => true,
						'message' => $this->redingtonOtpHelper->getConfigThoughVal('otp_not_matched','m'),
						'is_display_message' => true
					];
				}
			}else{
				$response = [
					'error' => true,
					'message' => 'Please login..',
					'is_display_message' => true
				];
			}
			$this->redingtonOtpHelper->debugLog('SUCCESS:(Verify) OTP Verify Done.', false);			
			return $resultJson->setData($response);
			
        } catch (\Exception $e) {
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
				'is_display_message' => true
            ];
			$this->redingtonOtpHelper->debugLog('ERROR:(Verify) OTP Verify '.$e->getMessage(), false);			
            return $resultJson->setData($response);
        }
	}
}