<?php
namespace Redington\Otp\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
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
			
				$otpExpireTimeVal = $this->redingtonOtpHelper->getConfigThoughVal('otp_expire_time','g');
				
				$params = $this->getRequest()->getParams();
				$selectedOtoType = $params['selectedOtoType'];
				
				$otpSendNote = $this->redingtonOtpHelper->getConfigThoughVal('otp_send_note','m');
				if($otpSendNote !="" ){
					$otpSendNote = str_replace('{{otp_type}}', $selectedOtoType, $otpSendNote);
				}
				
				if($selectedOtoType == 'email'){
					$customerEmail = $this->customerSession->getCustomer()->getEmail();
					//$customerEmail = 'piyalisarkar08@gmail.com';
					$this->redingtonOtpHelper->genarateOTP();
					$this->redingtonOtpHelper->sendOtpInMail($customerEmail);
					$obfuscateCustomerEmail = $this->redingtonOtpHelper->obfuscateEmail($customerEmail);
					
					$otpNote = $otpSendNote.' <span class="email-txt">'. $obfuscateCustomerEmail.'</span>';
					$response = [
								'success' => true,
								'otpNote' => $otpNote,
								'message' => 'Email Address OTP',
								'otpExpireTime' => $otpExpireTimeVal,
								'smsStatus' => ''

						];
					
				}else{
					$customerEmail = $this->customerSession->getCustomer()->getEmail();
					$customerMobileWithMask = $this->redingtonOtpHelper->getCustomerTelephoneWithMask();
					$otpNote = $otpSendNote.' <span class="email-txt">'. $customerMobileWithMask.'</span>';
					
					$this->redingtonOtpHelper->genarateOTP();
					
					$returnStatus = $this->redingtonOtpHelper->sendOtpInPhone($this->redingtonOtpHelper->getCustomerTelephone());
					
					if($returnStatus == 'FAILED'){
						$response=[
							'error' => true,
							'message'=>'There is issue with the sending OTP on mobile please send it on email Id',
							'is_display_message' => true,
							'status'=>'FAILED'
						];
						$this->redingtonOtpHelper->sendOtpInMail($customerEmail);
					}
					if($returnStatus == 'SUCCESS'){
						
						$response = [
								'success' => true,
								'otpNote' => $otpNote,
								'message' => 'Phone no OTP',
								'otpExpireTime' => $otpExpireTimeVal,
								'smsStatus' => $returnStatus
						];
					}
				}
			}else{
				 $response = [
					'error' => true,
					'message' => 'Please login..',
					'is_display_message' => true
				];
			}
			$this->redingtonOtpHelper->debugLog('SUCCESS:(Index) OTP Send Done.', false);
			return $resultJson->setData($response);
			
        } catch (\Exception $e) {
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
				'is_display_message' => false
            ];
			$this->redingtonOtpHelper->debugLog('ERROR:(Index) OTP Send '.$e->getMessage(), false);		
            return $resultJson->setData($response);
        }
	}
}