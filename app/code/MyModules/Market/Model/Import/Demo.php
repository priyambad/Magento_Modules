<?php

namespace Redington\Market\Model\Import;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;

class Demo extends \Sm\Market\Model\Import\Demo
{
    public function importDemo($demo_version,$store=NULL,$website = NULL)
    {
		$importPath = BP . '/app/code/Redington/Market/etc/import/';
        // Default response
        $gatewayResponse = new DataObject([
            'is_valid' => false,
            'import_path' => '',
            'request_success' => false,
            'request_message' => __('Error during Import '.$demo_version.'.'),
        ]);

        try {
            $xmlPath = $importPath . $demo_version . '.xml';
            $overwrite = true;
            
            if (!is_readable($xmlPath))
            {
                throw new \Exception(
                    __("Can't get the data file for import ".$demo_version.": ".$xmlPath)
                );
            }
            $data = $this->_parser->load($xmlPath)->xmlToArray();
            $scope = "default";
            $scope_id = 0;
            if ($store && $store > 0) // store level
            {
                $scope = "stores";
                $scope_id = $store;
            }
            elseif ($website && $website > 0) // website level
            {
                $scope = "websites";
                $scope_id = $website;
            }
            foreach($data['root']['config'] as $b_name => $b){
                foreach($b as $c_name => $c){
                    foreach($c as $d_name => $d){
                        $this->_configFactory->saveConfig($b_name.'/'.$c_name.'/'.$d_name,$d,$scope,$scope_id);
                    }
                }
            }

            //$gatewayResponse->setData("import_path",$config);

            $gatewayResponse->setIsValid(true);
            $gatewayResponse->setRequestSuccess(true);

            if ($gatewayResponse->getIsValid()) {
                $gatewayResponse->setRequestMessage(__('Success to Import '.$demo_version.'.'));
            } else {
                $gatewayResponse->setRequestMessage(__('Error during Import '.$demo_version.'.'));
            }
        } catch (\Exception $exception) {
            $gatewayResponse->setIsValid(false);
            $gatewayResponse->setRequestMessage($exception->getMessage());
        }

        return $gatewayResponse;
    }
}
