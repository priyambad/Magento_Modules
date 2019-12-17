<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Company\Model\ResourceModel\Order;

/**
 * Class CollectionFactory.
 */
class CollectionFactory extends \Magento\Company\Model\ResourceModel\Order\CollectionFactory
{
    /**
     * Object Manager instance.
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager = null;

    /**
     * Instance name to create.
     *
     * @var string
     */
    private $instanceName = \Magento\Sales\Model\ResourceModel\Order\Collection::class;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structure;

    /**
     * @var \Magento\Company\Api\StatusServiceInterface
     */
    private $moduleConfig;

    /**
     * @var \Redington\Company\Helper\Data
     */
    public $redingtonHelperData;

    /**
     * CollectionFactory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Company\Model\Company\Structure $structure
     * @param \Magento\Company\Api\StatusServiceInterface $moduleConfig
     * @param \Redington\Company\Helper\Data $redingtonHelperData
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Company\Model\Company\Structure $structure,
        \Magento\Company\Api\StatusServiceInterface $moduleConfig,
        \Redington\Company\Helper\Data $redingtonHelperData
    ) {
        $this->objectManager = $objectManager;
        $this->structure = $structure;
        $this->moduleConfig = $moduleConfig;
        $this->redingtonHelperData = $redingtonHelperData;
        parent::__construct(
            $objectManager,
            $structure,
            $moduleConfig
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create($customerId = null)
    {
        $collection = $this->objectManager->create($this->instanceName);

        if ($customerId) {
            $customers = [];
            $customers = $this->redingtonHelperData->retrieveCompanyUsersArray();
            $collection->addFieldToFilter('customer_id', ['in' => $customers]);
        }

        return $collection;
    }
}
