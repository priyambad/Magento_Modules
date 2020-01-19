<?php
namespace Redington\CompanyLogo\Model;

use Redington\CompanyLogo\Model\ResourceModel\CompanyLogo\CollectionFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

	/**
     * @var array
     */
    protected $_loadedData;
    
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $newRecordCollectionFactory
     * @param array $meta
     * @param array $data
     */

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $newRecordCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $newRecordCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $items = $this->collection->getItems();
        
        foreach ($items as $record) {
            $this->_loadedData[$record->getId()] = $record->getData();
        }
        return $this->_loadedData;
    }
}