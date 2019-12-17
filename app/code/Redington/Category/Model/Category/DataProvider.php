<?php

namespace Redington\Category\Model\Category;
 
use Redington\Category\Model\ResourceModel\Category\CollectionFactory;
 
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    protected $_loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        array $meta = [],
        array $data = []
    ) {
        $this->_resource = $resource;
        $this->collection = $categoryCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
          $companyTable = $this->_resource->getTableName('company');
         $this->collection ->join(
            ['co'=>$companyTable],
            "main_table.partner_id = co.entity_id",
            [
                'company_name' => 'co.company_name'
            ]
        );
       
         $items = $this->collection->getItems();
        foreach ($items as $record) {  
           
            $this->_loadedData[$record->getId()] = $record->getData();
             $this->_loadedData[$record->getId()]['categories']=array_merge(unserialize($record->getCategories()),unserialize($record->getBrands()));
        }

       
       
        return $this->_loadedData;

    }
}