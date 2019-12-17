<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */

namespace Redington\Company\Model;

class Company implements \Redington\Company\Api\CompanyInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     *
     * @var \Redington\Company\Helper\Data
     */
    private $helperData;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Redington\Company\Helper\Data $helperData,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\CompanyCredit\Model\ResourceModel\CreditLimit\CollectionFactory $creditLimitCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->helperData = $helperData;
        $this->creditLimitCollectionFactory = $creditLimitCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Create Company
     *
     * @param mixed $company
     * @return string[]
     */
    public function createCompany($company)
    {
        $this->helperData->logMessage('on boarding api called with following data');
        $this->helperData->logMessage($company);
        if($company["sap_code"] == 1110 || $company["sap_code"] == 1130 ){
            $company["store_id"] = 'telco_ae_fze';
        }
        $companyPostCode=(isset($company["postcode"]) && !empty($company["postcode"]))?$company["postcode"] : NULL;
        $quoteAllow = $company["request_for_quote_allowed"];
        $needToResetPostalCode = false;
        if(!$companyPostCode){
            $this->helperData->logMessage('postal code is blank need to reset');
            $companyPostCode = 1;
            $needToResetPostalCode = true;
        }
        $comapnyData = [
            "status" => $company["status"],
            "company_name" => $company["company_name"],
            "company_email" => $company["company_email"],
            "street" => [$company["street1"], $company["street2"]],
            "city" => $company["city"],
            "country_id" => $company["country_id"],
            "region" => $company["region"],
            "region_id" => $company["region_id"],
            "postcode" => $companyPostCode,
            "telephone" => $company["telephone"],
            "super_user_id" => $company["super_user_id"],
            "customer_group_id" => $company["customer_group_id"]
        ];
        $customerData = [
            "custom_attributes" => [
                "z_distribution_channel" => $company["distribution_channel"],
                "z_short_company_name" => $company["short_company_name"],
                "z_segment" => $company["segment"],
                "z_max_number_users" => $company["max_number_users"],
                "z_stock_allocation" => $company["stock_allocation"],
                "z_request_for_quote_allowed" => $company["request_for_quote_allowed"],
                "z_bulk_order_allowed" => $company["bulk_order_allowed"],
                "z_download_stock_availability" => $company["download_stock_availability"],
                "z_order_review" => $company["order_review"],
                "z_purchase_period" => $company["purchase_period"],
                "z_sap_account_number" => $company["sap_account_number"],
                "z_sap_code" => $company["sap_code"]
            ],
            "email" => $company["email"],
            "firstname" => $company["firstname"],
            "lastname" => $company["lastname"],
            "store_id" => $company["store_id"],
            "website_id" => $company["website_id"],
        ];
        $customData = [
            "billing" => $company["billing"],
            "shipping" => $company["shipping"],
            "documents" => $company["documents"],
            "brands" => $company["brands"],
            "companyCredit" => $company["company_credit"],
            "payment_term" => $company["payment_term"],
            "mobile" => $company["mobile"],
            "country_code" => $company["mobile_code"]
        ];
        $company = [
            "company" => $comapnyData,
            "customer" => $customerData,
            "custom" => $customData,
        ];
        $isModuleEnabled = $this->scopeConfig->getValue('redington_company/general/enabled');
        $accessToken = $this->scopeConfig->getValue('redington_company/general/company_integration_access_token');
        $adminUrl = $this->scopeConfig->getValue('admin/url/custom');
        // $this->helperData->getCategoryArray($company['custom']['brands']['lst_approved_brands']);

        $customerResponseArray = [];
        $companyResponseArray = [];
        if ($isModuleEnabled && $accessToken) {
            /** decode api request data */
            // $company = json_decode($company, true);
            $storeCode = $company['customer']['store_id'];
            $storeId = $this->helperData->getStoreIdByStoreCode($storeCode);
            $websiteId = $this->helperData->getWebsiteIdByStoreCode($storeCode);
            $company['customer']['store_id'] = $storeId;
            $company['customer']['website_id'] = $websiteId;
            //echo $storeId; echo $websiteId; exit;
            $customerData['customer'] = $company['customer'];
            $customerData = json_encode($customerData);
            // var_dump("<pre>", $customerData);exit;
            $authorization = "Authorization: Bearer " . $accessToken;
            /** Start company user creation API */
            if ($adminUrl == null) {
                $createCompanyUserURL = $this->urlBuilder->getUrl('rest/V1/customers/');
            } else {
                $createCompanyUserURL = $adminUrl . 'rest/V1/customers/';
            }

            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $createCompanyUserURL);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $customerData);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 400);
                $customerResponse = curl_exec($ch);
                $customerResponseArray['customer'] = json_decode($customerResponse, true);
                curl_close($ch);
                if (array_key_exists('message', $customerResponseArray['customer'])) {
                    $customerErrorMessage = $customerResponseArray['customer']['message'];
                    return "error_" . $customerErrorMessage;
                }
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info("Partner Admin Create Response - " . print_r($customerResponseArray['customer'], true));
                $logger->info("Partner Admin Create accessToken - " . print_r($accessToken, true));
                $logger->info("Partner Admin Create createURL - " . print_r($createCompanyUserURL, true));
                unset($customerData);
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->messageManager->addError($message);
                $companyResponseArray['customer'] = ['Error :' . $message . ''];
            }

            /** Start company Creation API */
            if (!empty($customerResponseArray['customer']) && isset($customerResponseArray['customer']['id'])) {
                $superUserId = $customerResponseArray['customer']['id'];
                $superUserGroupId = $customerResponseArray['customer']['group_id'];
                $company['company']['super_user_id'] = $superUserId;
                $company['company']['customer_group_id'] = $superUserGroupId;
                $companyData['company'] = $company['company'];
                $companyData = json_encode($companyData);
                if ($adminUrl == null) {
                    $createCompanyURL = $this->urlBuilder->getUrl('rest/V1/company/');
                } else {
                    $createCompanyURL = $adminUrl . 'rest/V1/company/';
                }
				 
                try {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $createCompanyURL);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $companyData);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
                    $companyResponse = curl_exec($ch);
                    $companyResponseArray['company'] = json_decode($companyResponse, true);
                    curl_close($ch);
                    if (array_key_exists('message', $companyResponseArray['company'])) {
                        // delete already created admin user
                        $this->helperData->logMessage('deleting customer with id '.$superUserId);
                        $this->customerRepositoryInterface->deleteById($superUserId);
                        $companyErrorMessage = $companyResponseArray['company']['message'];
                        return "error_" . $companyErrorMessage;
                    }
                    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/company.log');
                    $logger = new \Zend\Log\Logger();
                    $logger->addWriter($writer);
                    $logger->info("Partner Create Respanse - " . print_r($companyResponseArray, true));
                    $logger->info("Partner Create accessToken - " . print_r($accessToken, true));
                    $logger->info("Partner Create createURL - " . print_r($createCompanyURL, true));

                    /** assign shared catalog for this company **/
                    if (isset($companyResponseArray['company']['id'])) {

                        $this->helperData->setUserContact($customerResponseArray['customer']['id'], $company['custom']['mobile'], $company['custom']['country_code']);

                        // $sharedCatlogName = 'SYS_PERMISSION_' . $companyResponseArray['company']['id'];
                        // $this->helperData->assignSharedCatalog($companyResponseArray['company']['id'], $sharedCatlogName, $storeId);

                        // $this->helperData->setCategoryAndProducts($companyResponseArray['company']['id'], $company['custom']['brands']['lst_approved_brands']);

                        $this->helperData->setCategoryData($companyResponseArray['company']['id'], $websiteId, $company['custom']['brands']['lst_approved_brands']);

                        $documentData = $company['custom']['documents'];
                        $this->helperData->saveDocuments($companyResponseArray['company']['id'], $documentData);
                        $brandData = $company['custom']['brands'];
                        $this->helperData->saveBrandPreference($companyResponseArray['company']['id'], $brandData);
                        $this->helperData->setParentCompany($companyResponseArray['company']['id'], $customerResponseArray['customer']['email'], $websiteId);
						$this->helperData->setRequestForQuote($companyResponseArray['company']['id'],$quoteAllow);
                        $customerId = $customerResponseArray['customer']['id'];
                        if($needToResetPostalCode){
                            $this->helperData->resetPostalCode($companyResponseArray['company']['id']);
                        }
                        $billingAddress = $company['custom']['billing'];
                        $shippingAddress = $company['custom']['shipping'];
                        if (empty($shippingAddress)) {
                            $shippingAddress = $billingAddress;
                        }
                        //Send email to admin
                        $adminName = $company['customer']['firstname'] . ' ' . $company['customer']['lastname'];
                        $comapnyStore = $company['customer']['store_id'];
                        $adminEmail = $company['customer']['email'];
                        $this->helperData->sendPartnerAddedEmail($adminName, $adminEmail, $comapnyStore);

                        $this->helperData->setBillingAddress($customerId, $billingAddress, $company['company']['company_name']);
                        $this->helperData->setShippingAddress($customerId, $shippingAddress, $company['company']['company_name']);
                        $companyId = $companyResponseArray['company']['id'];
						$paymentTerm =$company['custom']['payment_term'];
                        $this->helperData->setPaymentTerms($companyId,$paymentTerm);
                        $this->helperData->removeDefaultUserRole($companyId);
                        $this->helperData->assignUserRoles($companyId);

                        $creditCollection = $this->creditLimitCollectionFactory->create()->addFieldToFilter('company_id', $companyId)->getFirstItem();
                        $creditCollection->setCreditLimit($company['custom']['companyCredit'])->save();
                    }

                    unset($company);
                    unset($companyData);
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $this->messageManager->addError($message);
                    $companyResponseArray['company'] = ['Error :' . $message . ''];
                }
            }
        } else {
            $customerResponseArray['customer'] = ['Error : Module is disabled or Invalid Access Token'];
            $companyResponseArray['company'] = ['Error : Module is disabled or Invalid Access Token'];
            return "error_Module is disabled or Invalid Access Token";
        }
        $requestResponseJson = array_merge($customerResponseArray, $companyResponseArray);
        $successResponse = "success_" . $companyResponseArray['company']['id'];
        return $successResponse;
    }

}
