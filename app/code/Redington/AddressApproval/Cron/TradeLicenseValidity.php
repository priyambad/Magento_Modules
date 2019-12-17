<?php

namespace Redington\AddressApproval\Cron;

class TradeLicenseValidity {
    public function __construct(
        \Redington\AddressApproval\Model\ResourceModel\AddressApproval\CollectionFactory $addressCollectionFactory,
        \Redington\AddressApproval\Model\ResourceModel\ForwarderApproval\CollectionFactory $forwarderCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ){
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->forwarderCollectionFactory = $forwarderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->serialize = $serialize;
        $this->_date =  $date;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->_addressRepository = $addressRepository;
        $this->storeManager = $storeManagerInterface;
    }
    public function checkTradeLicenseValidity(){
        $this->logMessage('Running cron for address and forwarder trade license validity');
        $this->date = $this->_date->date()->format('Y-m-d H:i:s');
        $this->logMessage('current date is '.$this->date);
        $addressDocumentCollection = $this->addressCollectionFactory->create();
        $this->logMessage('running cron for addresses');
        $this->execute($addressDocumentCollection);
        $forwarderDocumentCollection = $this->forwarderCollectionFactory->create();
        $this->logMessage('running cron for forwarders');
        $this->execute($forwarderDocumentCollection);
    }
    public function execute($Collection){
        echo 'Started execution';
        foreach($Collection as $addressDocument){
            $addressId = $addressDocument->getAddressId();
            $adminId = $addressDocument->getParentId();
            $this->logMessage('address id = '.$addressId);
            try{
                $documents = $this->serialize->unserialize($addressDocument->getDocuments());
                if(array_key_exists('t_l', $documents)){
                    $TradeDocument = $documents['t_l']; 
                    $mailSent = false;
                    $remainingDays = $this->getDaysToExpiry($TradeDocument);
                    if($remainingDays == -1){
                        $this->logMessage('trade license has expired');
                        //$mailSent = $this->sendExpiredMail($adminId);
                        $this->setTradeLicenseInvalid($addressId);
                    }else{
                        $this->logMessage('trade license will expire in '.$remainingDays.' days');
                        switch($remainingDays) {
                            case 1 : 
                                //$mailSent =$mailSent = $this->sendExpiresTomorrowMail($adminId);
                                break;
                            case 30 :
                            case 15 :
                            case 7 :
                                //$mailSent =$this->sendToExpireMail($adminId, $remainingDays);
                                break;
                        }
                    }
                    if($mailSent)
                        $this->logMessage('email sent successfully');
                    else
                        $this->logMessage("couldn't send email");
                }
            }catch(\Exception $e){
                $this->logMessage($e->getMessage());
                continue;
            }
        }
        echo 'Execution completed, please check log Redington_TradeLicenseValidity';
    }
    public function setTradeLicenseInvalid($addressId){

        try{
            $address = $this->_addressRepository->getById($addressId);
            $address->setCustomAttribute('is_valid',0);
            $this->_addressRepository->save($address);
            $this->logMessage('Invalidated trade license');
            return;
        }catch(\Exception $e){
            $this->logMessage('could not invalidate trade license attribute');
            $this->logMessage($e->getMessage());
            return;
        }
    }
    public function getDaysToExpiry($documentData){
        $expiryDate = $documentData['documentExpiry'];
        $this->logMessage('expiryDate is '.$expiryDate);
        $this->logMessage('current date is '.$this->date);
        $expiryTime = strtotime($expiryDate);
        $currentTime = strtotime($this->date);
        $days = round( ($expiryTime - $currentTime) / (60 * 60 * 24));
        return $days;
    }
    public function sendToExpireMail($adminId, $days){
        try{
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
    public function sendExpiredMail($adminId){
        try{
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
    public function sendExpiresTomorrowMail($adminId){
        try{
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