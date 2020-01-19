<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Model\NegotiableQuote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for updating quote items attributes and options for complex products.
 */
class QuoteItemsUpdater extends \Magento\NegotiableQuote\Model\QuoteItemsUpdater
{
    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    private $negotiableQuoteHelper;

    /**
     * @var \Magento\NegotiableQuote\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface
     */
    private $quote;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $resolver;

    /**
     * @var \Magento\Checkout\Model\CartFactory
     */
    private $cartFactory;

    /**
     * @var bool
     */
    private $hasChanges = false;

    /**
     * @var bool
     */
    private $hasUnconfirmedChanges = false;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\TotalsFactory
     */
    private $quoteTotalsFactory;

    /**
     * Json Serializer instance
     *
     * @var Json
     */
    private $serializer;

    /**
     * @param \Magento\NegotiableQuote\Helper\Quote $negotiableQuoteHelper
     * @param \Magento\NegotiableQuote\Model\Cart $cart
     * @param \Magento\Framework\Locale\ResolverInterface $resolver
     * @param \Magento\Checkout\Model\CartFactory $cartFactory
     * @param TotalsFactory $quoteTotalsFactory
     * @param Json|null $serialize
     */
    public function __construct(
        \Magento\NegotiableQuote\Helper\Quote $negotiableQuoteHelper,
        \Magento\NegotiableQuote\Model\Cart $cart,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Checkout\Model\CartFactory $cartFactory,
        TotalsFactory $quoteTotalsFactory,
		\Magento\Quote\Model\Quote\ItemFactory $itemFactory,
		\Magento\NegotiableQuote\Model\Quote\History $quoteHistory,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Redington\Quotation\Helper\Reservation $reservationHelper,
        Json $serialize = null
    ) {
         parent::__construct(
                $negotiableQuoteHelper,
                $cart,
                $resolver,
                $cartFactory,
                $quoteTotalsFactory,
                $serialize
                );
		$this->itemFactory = $itemFactory;
		$this->quoteHistory= $quoteHistory;
		$this->messageManager = $messageManager;
		$this->reservationHelper = $reservationHelper;
    }

    /**
     * Update quote items by $itemsData.
     *
     * @param CartInterface $quote
     * @param array $itemsData
     * @return bool
     */
    public function updateItemsForQuote(\Magento\Quote\Api\Data\CartInterface $quote, array $itemsData)
    {
				
        $this->quote = $quote;
        $items = [];
        $itemsForAdd = $this->getDataFromArray($itemsData, 'addItems');
        $itemsForUpdate = $this->getDataFromArray($itemsData, 'items');
        $configuredItemsAdd = $this->getDataFromArray($itemsData, 'configuredItems');

        $this->quote->setIsSuperMode(true);
        foreach ($itemsForUpdate as $data) {
            $item = $this->updateQuoteItem($data);
			
            if ($item !== null) {
                $items[(int)$data['id']] = $item;
				
            } else {
                $item = ['sku' => $data['sku'], 'productSku' => $data['productSku'], 'qty' => $data['qty']];
                if ($data['config']) {
                    $config = [];
                    parse_str(urldecode($data['config']), $config);
                    $item['config'] = $config;
                    $configuredItemsAdd[] = $item;
                } else {
                    $itemsForAdd[] = $item;
                }
            }
        }
        $this->removeQuoteItemsNotInArray($items);
        //$this->addConfiguredItems($configuredItemsAdd);
        //$this->addItems($itemsForAdd);
        $this->quote->setIsSuperMode(false);
       // $this->addRemovedSkus($quote, $itemsForUpdate);

        return $this->hasChanges;
    }

    /**
     * Getter for hasUnconfirmedChanges property.
     *
     * @return bool
     */
    public function hasUnconfirmedChanges()
    {
        return $this->hasUnconfirmedChanges;
    }

    /**
     * Get item data from array.
     *
     * @param array $itemsData
     * @param string $name
     * @return array
     */
    private function getDataFromArray(array $itemsData, $name)
    {
        return empty($itemsData[$name]) ? [] : $itemsData[$name];
    }

    /**
     * Update quote item by data.
     *
     * @param array $data
     * @return null|CartItemInterface
     */
    private function updateQuoteItem(array $data)
    {
        if (!isset($data['id']) || !isset($data['qty'])) {
            return null;
        }
        /** @var \Magento\Quote\Api\Data\CartItemInterface $item */

        $item = $this->quote->getItemById((int)$data['id']);
		$proposedPrice = 0;
		if(isset($data['proposed']) && $data['proposed']!=''){
			$proposedPrice = $data['proposed'];
		}
		$item->setProposedPrice($proposedPrice);
		$item->setCustomPrice($proposedPrice);
		$item->setOriginalCustomPrice($proposedPrice);
		$item->setRowTotal($proposedPrice * $data['qty']);
		$amount = $proposedPrice * $data['qty'];
		$taxamount = $amount*$item->getTaxPercent()/100;
		$item->setTaxAmount($taxamount);
		$item->save();
		$this->quote->getItemsCollection();
		$this->quote->collectTotals();
        $this->quote->setTotalsCollectedFlag(false);
        $this->quote->save();
        if (!$item || $this->isNeedReconfigurationItem($item, $data)) {
            return null;
        }
        $item->clearMessage();
        $requestCountItems = (int)trim($data['qty']);
        if (($requestCountItems > 0) && ($item->getQty() != $requestCountItems)) {
            $item->setQty($requestCountItems);
            $this->hasChanges = true;
        }
        return $item;
    }

    /**
     * Check if new and initial configuration of item are different.
     *
     * @param CartItemInterface $item
     * @param array $data
     * @return bool
     */
    private function isNeedReconfigurationItem(CartItemInterface $item, array $data)
    {
        if (!$item->getProduct()->canConfigure()) {
            return false;
        }

        $oldConfig = $this->negotiableQuoteHelper->retrieveCustomOptions($item, false);
        $newConfig = [];
        parse_str(urldecode($data['config']), $newConfig);

        return is_array($oldConfig) && is_array($newConfig) && $oldConfig != $newConfig;
    }

    /**
     * Remove quote items from quote.
     *
     * @param array $items
     * @return void
     */
    private function removeQuoteItemsNotInArray(array $items)
    {
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_QuoteItemData.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
				
        foreach ($this->quote->getAllVisibleItems() as $item) {
            /** @var \Magento\Quote\Api\Data\CartItemInterface $item */
            $itemId = $item->getId();
			$quoteId = $this->quote->getId();
			$oldData = $this->quoteHistory->collectOldDataFromQuote($this->quote);
            if (empty($items[$itemId])) {
				$this->quote->removeItem($itemId);
				$itemModel=$this->itemFactory->create()->load($itemId);
				$this->reservationHelper->removeItemById($quoteId, $itemModel, false, false);
				$itemModel->delete();
				$this->quote->collectTotals();
                $this->quote->setTotalsCollectedFlag(false);
                $this->quote->save();
				$this->messageManager->addSuccess(__('You\'ve removed the item from quote.'));
                $this->hasChanges = true;
				
				
            }
			
			
			$this->quoteHistory->checkPricesAndDiscounts($this->quote, $oldData);
        }
    }

    /**
     * Add configured complex products to quote.
     *
     * @param array $configuredItems
     * @return void
     */
    private function addConfiguredItems(array $configuredItems)
    {
        $result = $this->cart->addConfiguredItems($this->quote, $configuredItems);
        if ($result === true) {
            $this->hasChanges = true;
        }
    }

    /**
     * Add items to quote.
     *
     * @param array $addItems
     * @return void
     */
    private function addItems(array $addItems)
    {
        $result = $this->cart->addItems($this->quote, $addItems);
        if ($result === true) {
            $this->hasChanges = true;
        }
    }

    /**
     * Update quote by cart data.
     *
     * @param CartInterface $quote
     * @param array $cartData [optional]
     * @return CartInterface
     */
    public function updateQuoteItemsByCartData(\Magento\Quote\Api\Data\CartInterface $quote, array $cartData = [])
    {
        $totals = $this->quoteTotalsFactory->create(['quote' => $quote]);
        $quoteCatalogTotalPrice = $totals->getCatalogTotalPrice();
        $cartQty = (float)$quote->getItemsQty();

        $cartData = $this->processCartDataQty($cartData);

        // We instantiate a new cart model in order not to mess with the global one
        // We use it here as a helper to correctly set qty of quote items
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->cartFactory->create();
        $cart->setQuote($quote);
        $cartData = $cart->suggestItemsQty($cartData);
        $cart->updateItems($cartData);
        $quote = $cart->getQuote();

        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $value = $negotiableQuote->getNegotiatedPriceValue();
        if ($value !== null) {
            $negotiableQuote->setHasUnconfirmedChanges(true);
        }
        $quoteCatalogTotalPriceAfterRecalculation = $totals->getCatalogTotalPrice();
        $cartUpdatedQty = (float)$quote->getItemsQty();
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($negotiableQuote->getNegotiatedPriceValue() !== null
            && ($quoteCatalogTotalPrice != $quoteCatalogTotalPriceAfterRecalculation
                || $cartQty != $cartUpdatedQty)
        ) {
            $negotiableQuote->setIsCustomerPriceChanged(true);
        }
        return $quote;
    }

    /**
     * Process cart data qty.
     *
     * @param array $cartData
     * @return array
     */
    private function processCartDataQty(array $cartData)
    {
        $filter = new \Zend_Filter_LocalizedToNormalized(
            [
                'locale' => $this->resolver->getLocale()
            ]
        );

        $qtyCache = [];
        foreach ($cartData as $index => $data) {
            if (isset($data['qty']) && trim($data['qty']) !== '') {
                if (!isset($qtyCache[$data['qty']])) {
                    $qtyCache[$data['qty']] = $filter->filter(trim($data['qty']));
                }
                $cartData[$index]['qty'] = $qtyCache[$data['qty']];
            }
        }

        return $cartData;
    }

    /**
     * Add removed products sku to quote.
     *
     * @param CartInterface $quote
     * @param array $items
     * @return void
     */
    private function addRemovedSkus(\Magento\Quote\Api\Data\CartInterface $quote, array $items)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $failedItems = $this->cart->getDeletedItemsSku();
        $productSkus = [];
        foreach ($items as $item) {
            if (in_array($item['sku'], $failedItems)) {
                $productSkus[] = $item['sku'];
            }
        }
        if ($productSkus) {
            $skus = $this->getSkuString($negotiableQuote->getDeletedSku(), $productSkus);
            $negotiableQuote->setDeletedSku($skus);
        }
    }

    /**
     * Get new skus serialize string.
     *
     * @param string $old
     * @param array $addSku
     * @return string
     */
    private function getSkuString($old, array $addSku)
    {
        if (empty($old)) {
            $arraySkus = [
                \Magento\Framework\App\Area::AREA_ADMINHTML => [],
                \Magento\Framework\App\Area::AREA_FRONTEND => []
            ];
        } else {
            $arraySkus = $this->serializer->unserialize($old);
        }
        $arraySkus[\Magento\Framework\App\Area::AREA_ADMINHTML] = array_unique(
            array_merge($arraySkus[\Magento\Framework\App\Area::AREA_ADMINHTML], $addSku)
        );
        return $this->serializer->serialize($arraySkus);
    }
}
