<?php

namespace Redington\Customer\Cron;

class TradeLicenseValidity {
    public function __construct(
        \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ){
        $this->companyDocumentCollectionFactory = $documentCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->serialize = $serialize;
        $this->_date =  $date;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyFactory = $companyFactory;
        $this->storeManager = $storeManagerInterface;
    }
    public function checkTradeLicenseValidity(){
        echo 'Started execution';
        $this->logMessage('Running cron for trade license validity');
        $this->date = $this->_date->date()->format('Y-m-d H:i:s');
        $this->logMessage('current date is '.$this->date);
        $companyDocumentCollection = $this->companyDocumentCollectionFactory->create();
        foreach($companyDocumentCollection as $companyDocument){
            $companyId = $companyDocument->getCompanyId();
            $this->logMessage('company id = '.$companyId);
            try{
                $documents = $this->serialize->unserialize($companyDocument->getDocumentDetails());
                $billingDocuments = $documents['billing']; 
                foreach($billingDocuments as $document){
                    if($document['document_name'] == 'Trade license along with partner page'){
                        $mailSent = false;
                        $remainingDays = $this->getDaysToExpiry($document);
                        $this->logMessage('$remainingDays '.$remainingDays);
                        if($remainingDays == -1){
                            $this->logMessage('trade license has expired');
                            $this->setTradeLicenseInvalid($companyId);
                        }else{
                            $this->logMessage('trade license will expire in '.$remainingDays.' days');
                            switch($remainingDays) {
                                case 0 : 
                                    $mailSent =$mailSent = $this->sendExpiresTomorrowMail($companyId);
                                    break;
                                case 30 :
                                case 15 :
                                case 7 :
                                    $mailSent =$this->sendToExpireMail($companyId, $remainingDays);
                                    break;
                            }
                        }
                        if($mailSent)
                            $this->logMessage('email sent successfully');
                        else
                            $this->logMessage("couldn't send email");
                    }    
                }
            }catch(\Exception $e){
                $this->logMessage($e->getMessage());
                continue;
            }
        }
        echo 'Execution completed, please check log Redington_TradeLicenseValidity';
    }
    public function setTradeLicenseInvalid($companyId){
        try{
            $company = $this->companyFactory->create()->load($companyId);
            $adminId = $company->getSuperUserId();

            $customer = $this->customerRepositoryInterface->getById($adminId);
            $customer->setCustomAttribute('z_trade_license_valid',0);
            $this->customerRepositoryInterface->save($customer);
            $this->logMessage('invalidated trade license attribute');
            return;
        }catch(\Exception $e){
            $this->logMessage('could not invalidate trade license attribute');
            $this->logMessage($e->getMessage());
            return;
        }
    }
    public function getDaysToExpiry($documentData){
        $expiryDate = $documentData['expiry_date'];
        $this->logMessage('expiryDate is '.$expiryDate);
        $this->logMessage('current date is '.$this->date);
        $expiryTime = strtotime($expiryDate);
        $currentTime = strtotime($this->date);
        $days = round( ($expiryTime - $currentTime) / (60 * 60 * 24));
        return $days;
    }
    public function sendToExpireMail($companyId, $days){
        try{
            $company = $this->companyFactory->create()->load($companyId);
            $adminId = $company->getSuperUserId();
            $admin = $this->customerRepositoryInterface->getById($adminId);
            $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
            $templateVars = array(
                                'companyAdmin' => $admin->getFirstName().' '.$admin->getLastName(),
                                'remainingDays' => $days
                            );
            $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
            $this->inlineTranslation->suspend();
            $to = array($admin->getEmail());
            $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_customer/tradelicense/to_expire',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                            ->setTemplateOptions($templateOptions)
                            ->setTemplateVars($templateVars)
                            ->setFrom($from)
                            ->addTo($to)
                            ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        }catch(\Exception $e){
            $this->logMessage($e->getMessage());
            return false;
        }
    }
    public function sendExpiredMail($companyId){
        try{
            $company = $this->companyFactory->create()->load($companyId);
            $adminId = $company->getSuperUserId();
            $admin = $this->customerRepositoryInterface->getById($adminId);
            $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
            $templateVars = array(
                                'companyAdmin' => $admin->getFirstName().' '.$admin->getLastName(),
                            );
            $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
            $this->inlineTranslation->suspend();
            $to = array($admin->getEmail());
            $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_customer/tradelicense/expired',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                            ->setTemplateOptions($templateOptions)
                            ->setTemplateVars($templateVars)
                            ->setFrom($from)
                            ->addTo($to)
                            ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        }catch(\Exception $e){
            $this->logMessage($e->getMessage());
            return false;
        }
    }
    public function sendExpiresTomorrowMail($companyId){
        try{
            $company = $this->companyFactory->create()->load($companyId);
            $adminId = $company->getSuperUserId();
            $admin = $this->customerRepositoryInterface->getById($adminId);
            $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
            $templateVars = array(
                                'companyAdmin' => $admin->getFirstName().' '.$admin->getLastName(),
                            );
            $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
            $this->inlineTranslation->suspend();
            $to = array($admin->getEmail());
            $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_customer/tradelicense/to_expire_tomorrow',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                            ->setTemplateOptions($templateOptions)
                            ->setTemplateVars($templateVars)
                            ->setFrom($from)
                            ->addTo($to)
                            ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        }catch(\Exception $e){
            $this->logMessage($e->getMessage());
            return false;
        }
    }
    private function logMessage($message) {
        $filePath = '/var/log/Redington_TradeLicenseValidity.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}