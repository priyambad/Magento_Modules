<?php

namespace Redington\Warehouse\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $warehouseData = [
        [
            'sap_account_code' => '1120',
            'source_code' => 1147,
            'plant_code' => 1147,
            'distribution_channel' =>11,
            'name' => 'DXB IT Vol Online Sales WH',
            'contact_name' => 'DXB IT Vol Online Sales WH',
            'country_id' => 'AE',
            'city' => 'DUBAI',
            'street' => '407 & 408 Warehouse #1',
            'postcode' => '17266',
            'phone' => 48809487
        ],
        [
            'sap_account_code' => 1120,
            'source_code' => 1148,
            'plant_code' => 1148,
            'distribution_channel' =>13,
            'name' => 'DXB Telco Online Sales WH',
            'contact_name' => 'DXB Telco Online Sales WH',
            'country_id' => 'AE',
            'city' => 'DUBAI',
            'street' => '407 & 408 Warehouse #1',
            'postcode' => '17266',
            'phone' => 48809487
        ],
        [
            'sap_account_code' => 1140,
            'source_code' => 1248,
            'plant_code' => 1248,
            'distribution_channel' =>11,
            'name' => 'DXB CAE IT Vol Online Sales WH',
            'contact_name' => 'DXB CAE IT Vol Online Sales WH',
            'country_id' => 'AE',
            'city' => 'DUBAI',
            'street' => '507 Atrium Center,Khalid Bin A',
            'postcode' => '12816',
            'phone' => 43734854
        ],
        [
            'sap_account_code' => 1180,
            'source_code' => 2039,
            'plant_code' => 2039,
            'distribution_channel' =>31,
            'name' => 'JA IT AFR Vol Online Sales WH',
            'contact_name' => 'JA IT AFR Vol Online Sales WH',
            'country_id' => 'AE',
            'city' => 'DUBAI',
            'street' => 'Plot No. S 30902, South Zone 5',
            'postcode' => '17266',
            'phone' => 43734854
        ],
        [
            'sap_account_code' => 1180,
            'source_code' => 2121,
            'plant_code' => 2121,
            'distribution_channel' =>33,
            'name' => 'JA Telco AFR Online Sales WH',
            'contact_name' => 'JA Telco AFR Online Sales WH',
            'country_id' => 'AE',
            'city' => 'DUBAI',
            'street' => 'Plot No. S 30902, South Zone 5',
            'postcode' => '17266',
            'phone' => 123456789
        ],
        [
            'sap_account_code' => 1310,
            'source_code' => 2149,
            'plant_code' => 2149,
            'distribution_channel' =>31,
            'name' => 'Agility IT Vol Online Sales W',
            'contact_name' => 'Agility IT Vol Online Sales W',
            'country_id' => 'KE',
            'city' => 'Nairobi',
            'street' => 'Outer ring road,Baba dogo',
            'postcode' => '383-00606',
            'phone' => 97148809487
        ],
        [
            'sap_account_code' => 1310,
            'source_code' => 2150,
            'plant_code' => 2150,
            'distribution_channel' =>31,
            'name' => 'Westland IT Vol Online Sales W',
            'contact_name' => 'Westland IT Vol Online Sales W',
            'country_id' => 'KE',
            'city' => 'Nairobi',
            'street' => 'Outer ring road,Baba dogo',
            'postcode' => '383-00606',
            'phone' => 97148809487
        ],
        [
            'sap_account_code' => 1310,
            'source_code' => 2243,
            'plant_code' => 2243,
            'distribution_channel' =>33,
            'name' => 'MBA Telco  Online Sales WH',
            'contact_name' => 'MBA Telco  Online Sales WH',
            'country_id' => 'KE',
            'city' => 'Nairobi',
            'street' => 'Outer ring road,Baba dogo',
            'postcode' => '383-00606',
            'phone' => 254204237000
        ],
        [
            'sap_account_code' => 1140,
            'source_code' => 3128,
            'plant_code' => 3128,
            'distribution_channel' =>13,
            'name' => 'DXB CAE Telco Online Sales WH',
            'contact_name' => 'DXB CAE Telco Online Sales WH',
            'country_id' => 'AE',
            'city' => 'Dubai',
            'street' => '507 Atrium Center,Khalid Bin A',
            'postcode' => '12816',
            'phone' => '123456789'
        ],
        [
            'sap_account_code' => 1110,
            'source_code' => 6117,
            'plant_code' => 6117,
            'distribution_channel' =>11,
            'name' => 'JA IT Vol Online Sales WH',
            'contact_name' => 'JA IT Vol Online Sales WH',
            'country_id' => 'AE',
            'city' => 'Dubai',
            'street' => 'Plot No. S 30902, South Zone 5',
            'postcode' => '17266',
            'phone' => 123456789
        ],
        [
            'sap_account_code' => 1110,
            'source_code' => 6118,
            'plant_code' => 6118,
            'distribution_channel' =>13,
            'name' => 'JA Telco ME Online Sales WH',
            'contact_name' => 'JA Telco ME Online Sales WH',
            'country_id' => 'AE',
            'city' => 'Dubai',
            'street' => 'Plot No. S 30902, South Zone 5',
            'postcode' => '17266',
            'phone' => 123456789
        ]
    ];
    public function getWarehouseList() {
        return $this->warehouseData;
    }
}
