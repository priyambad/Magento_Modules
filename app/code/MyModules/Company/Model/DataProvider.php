<?php
/**
 * Redington.
 *
 * @category  Redington
 *
 * @author    Spadeworx
 * @copyright Copyright (c) Redington
 */

namespace Redington\Company\Model;

use Magento\Company\Model\ResourceModel\Role\CollectionFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $roleCollecttionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $roleCollecttionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $items = $this->collection->getItems();
        print_r($items);
        die;
        foreach ($items as $employee) {
            $this->_loadedData[$employee->getId()] = $employee->getData();
        }

        return $this->_loadedData;
    }
}
