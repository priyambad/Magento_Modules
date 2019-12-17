<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Model;

use Amasty\Checkout\Api\Data\AdditionalFieldsInterface;

/**
 * @method \Amasty\Checkout\Model\ResourceModel\AdditionalFields getResource()
 * @method \Amasty\Checkout\Model\ResourceModel\AdditionalFields\Collection getCollection()
 */
class AdditionalFields extends \Amasty\Checkout\Model\AdditionalFields
{
    /**
     * @var ResourceModel\AdditionalFields\CollectionFactory
     */
    protected $additionalFieldsCollectionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,

        \Amasty\Checkout\Model\ResourceModel\AdditionalFields\CollectionFactory $additionalFieldsCollectionFactory,

        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,

        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->additionalFieldsCollectionFactory = $additionalFieldsCollectionFactory;
    }
    /**
     * @findByField by custom field
     */
    public function findByField($value, $field)
    {
        /** @var \Amasty\Checkout\Model\ResourceModel\AdditionalFields\Collection $deliveryCollection */
        $additionalFieldsCollection = $this->additionalFieldsCollectionFactory->create();

        /** @var \Amasty\Checkout\Model\AdditionalFields $delivery */
        $additionalFields = $additionalFieldsCollection
            ->addFieldToFilter($field, $value)
            ->getFirstItem()
        ;
        return $additionalFields;
    }
}
