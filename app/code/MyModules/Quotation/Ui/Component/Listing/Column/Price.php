<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\Quotation\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Price column component.
 */
class Price extends \Magento\NegotiableQuote\Ui\Component\Listing\Column\Price
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceFormatter;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceCurrencyInterface $priceFormatter
     * @param StoreManagerInterface $storeManager
     * @param UserContextInterface $userContext
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param array $components [optional]
     * @param array $data [optional]
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceCurrencyInterface $priceFormatter,
        StoreManagerInterface $storeManager,
        UserContextInterface $userContext,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory,$priceFormatter,$storeManager,$userContext,$serializer, $components, $data);
        $this->priceFormatter = $priceFormatter;
        $this->storeManager = $storeManager;
        $this->userContext = $userContext;
        $this->serializer = $serializer;
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $price = $item[$this->getData('name')];
                if ($this->isAuthor($item) && $this->isStatusNotBlocked($item) && $this->isCurrencyChange($item)) {
                    $price = $this->priceFormatter->convert($price);
                    $currencyCode = $this->priceFormatter->getCurrency()->getCode();
                } elseif (!$this->isStatusNotBlocked($item) && isset($item['snapshot'])) {
                    $snapshot = $this->serializer->unserialize($item['snapshot']);
                    $price = $snapshot['quote']['subtotal'];
                    $currencyCode = $snapshot['quote']['quote_currency_code'];
                } else {
                    $price = $item['subtotal'];
                    $currencyCode = $item['quote_currency_code'];
                }
                $item[$this->getData('name')] = $this->priceFormatter->format(
                    $price,
                    false,
                    2,
                    null,
                    $currencyCode
                );
            }
        }

        return $dataSource;
    }

    /**
     * Check is status of quote isn't close or ordered.
     *
     * @param array $item
     * @return bool
     */
    private function isStatusNotBlocked(array $item)
    {
        $blockedStatuses = [NegotiableQuoteInterface::STATUS_CLOSED, NegotiableQuoteInterface::STATUS_ORDERED];
        $status = isset($item['status_original']) ? $item['status_original'] : $item['status'];
        return !in_array($status, $blockedStatuses);
    }

    /**
     * Check is current user is author of quote.
     *
     * @param array $item
     * @return bool
     */
    private function isAuthor(array $item)
    {
        $userId = isset($item['customer_id_original']) ? $item['customer_id_original'] : $item['customer_id'];
        return $userId == $this->userContext->getUserId();
    }

    /**
     * Check currency in quote and store.
     *
     * @param array $item
     * @return bool
     */
    private function isCurrencyChange(array $item)
    {
        $store = $this->storeManager->getStore($item['store_id']);
        return !isset($item['quote_currency_code'])
            || !isset($item['base_currency_code'])
            || $item['quote_currency_code'] != $store->getCurrentCurrency()->getCode()
            || $item['base_currency_code'] != $store->getBaseCurrency()->getCode()
            || $item['base_to_quote_rate'] != $store->getBaseCurrency()->getRate($item['quote_currency_code']);
    }
}
