<?php
/**
 * Copyright © Redington. All rights reserved.
 *
 */
namespace Redington\Company\Ui\DataProvider\Roles;

use Magento\Company\Model\ResourceModel\Role\CollectionFactory;

/**
 * Class DataProvider.
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = [],
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_resource = $resource;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        $companyTable = $this->_resource->getTableName('company');
         $this->collection ->join(
            ['co'=>$companyTable],
            "main_table.company_id = co.entity_id",
            [
                'company_name' => 'co.company_name'
            ]
        );
        return parent::getData();
    }
}
