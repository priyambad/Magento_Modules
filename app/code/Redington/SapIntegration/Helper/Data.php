<?php

namespace Redington\SapIntegration\Helper;

class Data {


        public function __construct(
           \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
           
        ) {
            $this->scopeConfig = $scopeConfig;
        }

        public function getOverdueAmount($sapData)
        {
           $overdueUrl = $this->scopeConfig->getValue('redington_sap/general/overdue_api');
            

            $data = $sapData;
            //$this->logMessage('Request ' . $data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $overdueUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 400);
            $sapResponce = curl_exec($ch);
            curl_close($ch);
            try {
                $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($responseXml);
                $json = str_replace("{}", "null", $json);
                $responseArray = json_decode($json, true);

                //$this->logMessage($responseArray);
                if ($responseArray['OVERDUE']) {
                return $responseArray['OVERDUE']['ZOVERDUE'];
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }
       
        private function logMessage($message)
        {
        $filePath = '/var/log/a_test.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }
}
