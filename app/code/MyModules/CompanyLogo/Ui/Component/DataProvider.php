<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\CompanyLogo\Ui\Component;

use Redington\CompanyLogo\Model\ResourceModel\CompanyLogo\CollectionFactory;

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
        array $data = []
    ) {
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
        return parent::getData();
    }

}
