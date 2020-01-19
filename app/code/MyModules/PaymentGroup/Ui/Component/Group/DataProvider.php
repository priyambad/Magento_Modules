<?php
/**
 * Copyright © Redington. All rights reserved.
 *
 */
namespace Redington\PaymentGroup\Ui\Component\Group;

use Redington\PaymentGroup\Model\ResourceModel\Group\CollectionFactory;

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
