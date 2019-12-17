<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Otp
 */

namespace Redington\Otp\Api;

/**
 * Interface for retrieving various entity data objects by a given parameters and creating company and admin user to company.
 *
 * @api
 * @since 100.0.0
 */
interface OtpInterface
{    
    /**
     * Send Otp .
     * 
     * @param mixed $otp
     * @return \Redington\Otp\Api\OtpInterface
     */
    public function sendOtp($otp);
}
