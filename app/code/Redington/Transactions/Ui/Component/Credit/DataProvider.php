<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Transactions\Ui\Component\Credit;

use Redington\Transactions\Model\ResourceModel\Credit\CollectionFactory;
use Redington\Transactions\Model\CreditFactory;

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
        \Redington\Company\Helper\Data $companyHelper,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->companyHelper = $companyHelper;
        $this->collection = $collectionFactory->create();
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        $companyId = $this->companyHelper->retrieveCompanyId();
        $this->getCollection()->addFieldToFilter('main_table.company_id', ['eq' => $companyId])->addFieldToFilter('main_table.status', ['gteq' => 0])->setOrder('entity_id','DESC');
        return parent::getData();
    }

}
