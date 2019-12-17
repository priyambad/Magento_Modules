<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\Category\Ui\Component;

use Redington\Category\Model\ResourceModel\Category\CollectionFactory;

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
        \Magento\Framework\App\ResourceConnection $resource,
        array $meta = [],
        array $data = []
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
            "main_table.partner_id = co.entity_id",
            [
                'company_name' => 'co.company_name',
                'company_email' => 'co.company_email'
            ]
        );
        return parent::getData();
    }

}
