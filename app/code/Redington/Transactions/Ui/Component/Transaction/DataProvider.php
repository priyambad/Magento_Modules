<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Transactions\Ui\Component\Transaction;

use Redington\Transactions\Model\ResourceModel\Transaction\CollectionFactory;
use Redington\Transactions\Model\TransactionFactory;

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
     * @param \Redington\Company\Helper\Data $companyHelper
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
        $this->getCollection()->addFieldToFilter('main_table.company_id', ['eq' => $companyId])->setOrder('entity_id','DESC');
        return parent::getData();
    }

}
