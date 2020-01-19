<?php
/**
 * Copyright © Redington. All rights reserved.
 *
 */
namespace Redington\CompanyLogo\Controller\Adminhtml\Address;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action 
{

    protected $dataPersistor;
    
   /**
     * Undocumented function
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Redington\CompanyLogo\Model\CompanyLogoFactory $companylogodata
     * @param  \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param \Magento\Backend\Model\Auth\Session $adminSession
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Request\Http $request,
        \Redington\CompanyLogo\Model\CompanyLogoFactory $companylogodata,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Magento\Backend\Model\Auth\Session $adminSession
    ) {
		
        parent::__construct($context);
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
		$this->companylogodata = $companylogodata;
        $this->dataPersistor = $dataPersistor;
        $this->_adminSession = $adminSession;
    }

    
    public function execute()
    {
        $resultPage = $this->companylogodata->create();
        $params = $this->request->getParams('');
       try{

            foreach ($params['company']['icon'] as $key => $value) {
                $company_logo = $value['name'];
             }

           $company_code = $params['company']['company_code'];
           $accountname = $params['company']['accountname'];
           $company_address = $params['company']['company_address'];
           $onlinetransfer = $params['company']['onlinetransfer'];
           $company_logo_url = "logos/".$company_logo;
          
           //company address edit
           if($this->_adminSession->getRequest() == "view"){
             $entity_Id = $params['company']['entity_id'];
             $companyLogoData = $this->companylogodata->create()->load($entity_Id);
             $companyLogoData->setCompanyCode($company_code);
             $companyLogoData->setAccountname($accountname);
             $companyLogoData->setCompanyAddress($company_address);
             $companyLogoData->setOnlinetransfer($onlinetransfer);
             $companyLogoData->setCompanyLogo($company_logo_url);
             $companyLogoData->save();
           }else{
             $companyLogoData = $this->companylogodata->create();
             $companyLogoData->setCompanyCode($company_code);
             $companyLogoData->setAccountname($accountname);
             $companyLogoData->setCompanyAddress($company_address);
             $companyLogoData->setOnlinetransfer($onlinetransfer);
             $companyLogoData->setCompanyLogo($company_logo_url);
             $companyLogoData->save();
         }
     }catch (\Exception $e) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/company_address.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('error--'.$e->getMessage());
     }
     $this->_redirect('companylogo/address/index');
    }
   
}
