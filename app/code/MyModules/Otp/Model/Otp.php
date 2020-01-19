<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Otp
 */

namespace Redington\Otp\Model;

class Otp implements \Redington\Otp\Api\OtpInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var \Redington\Otp\Helper\Data
     */
    private $helperData;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Redington\Otp\Helper\Data $helperData,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
        $this->helperData = $helperData;
    }

    /**
     * Send Otp
     *
     * @param mixed $otp
     * @return string[]
     */
    public function sendOtp($otp)
    {

        $isModuleEnabled = $this->helperData->isModuleEnabled('default', '');
        $accessToken = $this->scopeConfig->getValue('redington_company/general/company_integration_access_token');

        $otpResponseArray = [];
        if ($isModuleEnabled && $accessToken) {

            $authorization = "Authorization: Bearer " . $accessToken;
            /** Start otp code send API */
            try {
                $url = $this->helperData->getConfigThoughVal('otp_sms_api_url', 'g');
                $this->helperData->debugLog('URL in Model: ' . $url, false);
                $data['payload'] = $this->jsonHelper->jsonEncode($otp);
                $this->helperData->debugLog('OTP: ' . print_r($otp, true), false);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 400);
                $otpResponse = curl_exec($ch);

                //$info = curl_getinfo($ch);
                $otpResponseArray['otp'] = $this->jsonHelper->jsonDecode($otpResponse);
                $this->helperData->debugLog('Otp Response: ' . print_r($otpResponseArray['otp'], true), false);

                curl_close($ch);
                unset($otp);
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->messageManager->addError($message);
                $otpResponseArray['otp'] = ['Error :' . $message . ''];
                $this->helperData->debugLog('Otp Exception: ' . $message, false);

            }
        } else {
            $otpResponseArray['customer'] = ['Error : Module is disabled or Invalid Access Token'];
            return "error_Module is disabled or Invalid Access Token";
        }
        $requestResponseJson = $this->jsonHelper->jsonEncode($otpResponseArray);
        //$successResponse = "success_".$otpResponseArray;
        return $otpResponseArray;
    }
}
