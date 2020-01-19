<?php
namespace Redington\Contact\Controller\Index;

use Redington\Contact\Model\Contact;
use Redington\Contact\Model\ContactFactory;

class Post extends \Magento\Framework\App\Action\Action
{
		protected $_pageFactory;

		protected $contactfactory;

		const XML_PATH_EMAIL_RECIPIENT = 'redington_contact/general/recipients_email';

		public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Framework\View\Result\PageFactory $pageFactory,
			\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
			 \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
			ContactFactory $contactfactory
		)
		{
			
			$this->_pageFactory = $pageFactory;
			$this->contactfactory=$contactfactory;
			$this->_transportBuilder = $transportBuilder;
			$this->scopeConfig = $scopeConfig;
			parent::__construct($context);
		}

		public function execute()
		{
			
			$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
			 if (!$this->getRequest()->isPost()) {
            	return $this->resultRedirectFactory->create()->setPath('*/*/');
       		 }
       		 
       		 $postData = $this->getRequest()->getPostValue();
       		 $requiredData= array();
       		 $requiredData['mobile']=$postData['mobile'];
       		 $requiredData['email']=$postData['email'];
       		 $requiredData['company_name']=$postData['company_name'];
       		 $requiredData['full_name']=$postData['full_name'];
       		  $requiredData['country_code']=$postData['country_code'];
       		 $this->contactfactory->create()->setData($requiredData)->save();


			$sender = [
			'name' =>  $this->scopeConfig->getValue("trans_email/ident_sales/name", $storeScope),
            'email' => $this->scopeConfig->getValue("trans_email/ident_sales/email", $storeScope),
			];
			 
			$templateVars = array(
							'name' => $requiredData['full_name'],
                            'mobile' =>$requiredData['mobile'],
                            'email' => $requiredData['email'],
                            'company_name' => $requiredData['company_name'],
                            'emailSubject' => 'Setup your Account'
                        );
			
			$transport = $this->_transportBuilder
			->setTemplateIdentifier('redington_contact_template') // this code we have mentioned in the email_templates.xml
			->setTemplateOptions(
			[
			'area' => \Magento\Framework\App\Area::AREA_FRONTEND, // this is using frontend area to get the template file
			'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
			]
			)
			->setTemplateVars($templateVars)
			->setFrom($sender)
			->addTo(explode(',',$this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope)))
			->getTransport();
			 
			$transport->sendMessage();


       		$resonse='Thanks for contacting us. We\'ll respond to you very soon.';
            echo json_encode($resonse);
		}
}
?>