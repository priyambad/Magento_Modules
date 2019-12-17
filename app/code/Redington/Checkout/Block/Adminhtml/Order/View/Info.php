<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */
namespace Redington\Checkout\Block\Adminhtml\Order\View;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{
    /**
     * @var Redington\SapIntegration\Model\ResourceModel\OrderReference\Collection
     */
    protected $orderRefrenceCollection;

    /**
     * Dependent Objects
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadata
     * @param \Magento\Customer\Model\Metadata\ElementFactory $elementFactory
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Redington\SapIntegration\Model\ResourceModel\OrderReference\CollectionFactory $orderRefrenceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\SapIntegration\Model\ResourceModel\OrderReference\CollectionFactory $orderRefrenceCollection,
        array $data = array()) {
        $this->addressRepository = $addressRepository;
        $this->orderRefrenceCollection = $orderRefrenceCollection;
        parent::__construct($context, $registry, $adminHelper, $groupRepository, $metadata, $elementFactory, $addressRenderer, $data);
    }
    /**
     * Get forwarder address id
     *
     * @return void
     */
    public function getForwarderAddressId()
    {
        try {
            $order = $this->getOrder();
            $forwarderAddressId = $order->getForwarderAddressId();
            return $forwarderAddressId;
        } catch (\Exception $e) {
            $e->getMessage();
            return false;
        }
    }
    /**
     * Get Forwarder Address
     *
     * @return void
     */
    public function getForwarderAddress()
    {
        try { 
            $forwarderAddressId = $this->getForwarderAddressId();
            $addressObject = $this->addressRepository->getById($forwarderAddressId);
            $formattedForwarderAddress = $this->getFormattedAddress($addressObject);
            return $formattedForwarderAddress;
        } catch (\Exception $e) {
            $e->getMessage();
            return false;
        }
    }
    /**
     * Get Order Status
     *
     * @return void
     */
    public function getOrderStatus()
    {
        try {
            $order = $this->getOrder();
            $status = $order->getStatus();

            if ($status == 'failed') {
                return true;
            }
        } catch (\Exception $e) {
            $e->getMessage();
            return false;
        }

    }
    /**
     * Get Reason
     *
     * @return void
     */
    public function getStatusReason()
    {
        try {
            $orderId = $this->getOrder()->getId();
            $orderRefcollection = $this->orderRefrenceCollection->create();
            $orderRefcollection->addFieldToFilter('order_id', $orderId);
            $statusData = unserialize($orderRefcollection->getFirstItem()->getData('response_data'));

            $status = isset($statusData['RETURN']['BAPIRET2']['MESSAGE']) ? $statusData['RETURN']['BAPIRET2']['MESSAGE'] : $statusData['RETURN']['BAPIRET2'][0]['MESSAGE'];            
            return $status ? $status : 'Request timed out';
        } catch (\Exception $e) {
            $e->getMessage();
            return false;
        }
    }
}
