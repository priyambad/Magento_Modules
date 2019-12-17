<?php

namespace Redington\Company\Model\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;

class SaveCustomer {
    
    public function __construct(
            \Magento\Company\Model\CustomerFactory $companyCustomerFactory,
            CustomerRepositoryInterface $customerRepository,
            \Magento\Company\Model\CustomerFactory $companyUserFactory,
            \Redington\Company\Helper\Data $companyHelper,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
            ) {
        $this->companyCustomerFactory = $companyCustomerFactory;
        $this->customerRepository = $customerRepository;
        $this->companyUserFactory = $companyUserFactory;
        $this->companyHelper = $companyHelper;
        $this->scopeConfig = $scopeConfig;
    }
    
    public function aroundCreate(\Magento\Company\Model\Action\SaveCustomer $subject, \Closure $proceed, $request) {
        $this->logMessage('initiating the process');
        //Create user in ADB2C
        $customerData = $request->getParams();
        try{
            $randomPassword = $this->generateRandomPassword();
            $createCustomerData = [
                'firstName' => $customerData['firstname'],
                'lastName' => $customerData['lastname'],
                'mobileNumber' => $customerData['extension_attributes']['company_attributes']['telephone'],
                'emailID' => $customerData['email'],
                'password' => $randomPassword,
                'companyName' => $this->companyHelper->retrieveCompanyName(),
                'role' => 'partner',
                'storePermissions' =>  '',
                'status' => 1,
                'companyId' => $this->companyHelper->retrieveCompanyId()
            ];
            
            $customerResponce = $this->createUserInAD($createCustomerData);
            $this->logMessage('Ad response : ');
            $this->logMessage($customerResponce);
            $this->logMessage($customerResponce['IsSuccess']);
            if($customerResponce['IsSuccess'] == 1) {

                $name = $customerData['firstname'].' '.$customerData['lastname'];

                $email = $customerData['email'];

                $password = $randomPassword;

                $companyName = $this->companyHelper->retrieveCompanyName();

                $this->logMessage('calling funtion to send email with param '.$name.', '.$email.', '.$password);
                $this->companyHelper->sendCustomerAddedEmail($name,$email,$password, $companyName);
            }else{
                throw new \Magento\Framework\Exception\State\InputMismatchException(
                    __('A customer with the same email already assigned to company.')
                );
            }
        }catch(\Exception $e){
            $this->logMessage(' in catch error '.$e->getMessage());
            throw new \Magento\Framework\Exception\State\InputMismatchException(
                __('A customer with the same email already assigned to company.')
            );
        }
        
        // Create user in Magento
        $result = $proceed($request);
        return $result;
    }
    public function generateRandomPassword() {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $symbols = "#$-_%^@&";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        $pass[8] = $symbols[rand(0, strlen($symbols)-1)];
        return implode($pass);
    }

    public function createUserInAD($customerData) {
        $this->logMessage('customer Data : ');
        $adUrl = $this->scopeConfig->getValue('redington_company/email/adb2c_endpoint',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $createUserInAdUrl = $adUrl;
        $customerData = json_encode($customerData);
        $this->logMessage($customerData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $createUserInAdUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $customerData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $customerResponse = curl_exec($ch);
        $this->logMessage($customerResponse);
        $customerResponse = json_decode($customerResponse, true);
        curl_close($ch);
        return $customerResponse;
    }

    public function logMessage($message) {
        $filePath = '/var/log/Redington_Partners.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}