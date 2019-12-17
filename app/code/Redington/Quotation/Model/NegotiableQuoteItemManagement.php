<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class for managing quote items.
 */
class NegotiableQuoteItemManagement extends \Magento\NegotiableQuote\Model\NegotiableQuoteItemManagement
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Tax\Model\Config
     */
    private $taxConfig;

    /**
     * @var \Magento\NegotiableQuote\Model\NegotiableQuoteItemFactory
     */
    private $negotiableQuoteItemFactory;

    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\TotalsFactory
     */
    private $quoteTotalsFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\Action\Item\Price\Update
     */
    private $priceUpdater;

    /**
     * @var \Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem
     */
    private $negotiableQuoteItemResource;

    /**
     * @var int
     */
    private $customPricePrecision = 4;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param TaxConfig $taxConfig
     * @param NegotiableQuoteItemFactory $negotiableQuoteItemFactory
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param TotalsFactory $quoteTotalsFactory
     * @param \Magento\NegotiableQuote\Model\Action\Item\Price\Update $priceUpdater
     * @param \Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem $negotiableQuoteItemResource
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TaxConfig $taxConfig,
        \Magento\NegotiableQuote\Model\NegotiableQuoteItemFactory $negotiableQuoteItemFactory,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        TotalsFactory $quoteTotalsFactory,
        \Magento\NegotiableQuote\Model\Action\Item\Price\Update $priceUpdater,
        \Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem $negotiableQuoteItemResource
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->taxConfig = $taxConfig;
        $this->negotiableQuoteItemFactory = $negotiableQuoteItemFactory;
        $this->extensionFactory = $extensionFactory;
        $this->quoteTotalsFactory = $quoteTotalsFactory;
        $this->priceUpdater = $priceUpdater;
        $this->negotiableQuoteItemResource = $negotiableQuoteItemResource;
    }

    /**
     * @inheritdoc
     */
    public function updateQuoteItemsCustomPrices($quoteId, $needSave = true)
    {
        $quote = $this->getQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        $priceMultiplier = $this->getPriceMultiplier($quote);
        $quoteCurrency = $quote->getCurrency();
        $priceLost = 0;
        $itemsToUpdate = [];
        $preserveCustomPrice = $this->preserveQuoteCustomPrice($quote, $negotiableQuote->getStatus());
        $needRecalculateRule = ($negotiableQuote->getNegotiatedPriceValue() === null) && !$preserveCustomPrice;
	    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_QuotenegotiableItem.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
        /** @var CartItemInterface $quoteItem */
        foreach ($quote->getAllItems() as $key => $quoteItem) {
            $price = $this->getOriginalPriceByItem($quoteItem);
            $itemToUpdate = [
                'qty' => $quoteItem->getQty()
            ];
            if ($needRecalculateRule) {
                $itemToUpdate['use_discount'] = true;
            } else {
                $newPrice = $price * $priceMultiplier;
                $itemToUpdate['custom_price'] = round(
                    $newPrice * $quoteCurrency->getBaseToQuoteRate(),
                    PriceCurrencyInterface::DEFAULT_PRECISION
                );
                $priceInBaseCurrency = round(
                    $itemToUpdate['custom_price'] / $quoteCurrency->getBaseToQuoteRate(),
                    PriceCurrencyInterface::DEFAULT_PRECISION
                );
                $priceLost += ($newPrice - $priceInBaseCurrency) * $quoteItem->getQty();
            }
            $itemsToUpdate[$key] = $itemToUpdate;
        }
        $customPricePrecision = $quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()
            ? $this->customPricePrecision
            : PriceCurrencyInterface::DEFAULT_PRECISION;
        foreach ($quote->getAllItems() as $key => $quoteItem) {
            if (round($priceLost, $customPricePrecision) != 0) {
                $priceAdd = round(
                    $priceLost / $itemsToUpdate[$key]['qty'] * $quoteCurrency->getBaseToQuoteRate(),
                    $customPricePrecision
                );
                $itemsToUpdate[$key]['custom_price'] += $priceAdd;
				$logger->info($itemsToUpdate[$key]['custom_price']);
                $priceLost -= $priceAdd * $itemsToUpdate[$key]['qty'];
            }
            $itemsToUpdate[$key]['custom_price'] = $quoteItem->getCustomPrice();
            $this->updateItemByArray($quoteItem, $itemsToUpdate[$key], $needRecalculateRule);
        }

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->setNegotiableQuotePrices($negotiableQuote, $quote, $preserveCustomPrice);
        if ($needSave) {
            $this->quoteRepository->save($quote);
        }

        return true;
    }

    /**
     * Update $quoteItem prices, qty, options and discount by $itemToUpdate.
     *
     * @param CartItemInterface $quoteItem
     * @param array $itemToUpdate
     * @param bool $needRecalculate [optional]
     * @return void
     */
    private function updateItemByArray(CartItemInterface $quoteItem, array $itemToUpdate, $needRecalculate = false)
    {
        
        $this->priceUpdater->update($quoteItem, $itemToUpdate);
        if ($needRecalculate) {
            //$quoteItem->setCustomPrice(null);
            //$quoteItem->setOriginalCustomPrice(null);
        }
        $quoteItem->setBaseTaxCalculationPrice(null);
        $quoteItem->setTaxCalculationPrice(null);
    }

    /**
     * Retrieve negotiable price multiplier for quote.
     *
     * @param CartInterface $quote
     * @return float
     */
    private function getPriceMultiplier(CartInterface $quote)
    {
        $priceMultiplier = 1;
        /** @var $negotiableQuote NegotiableQuoteInterface */
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        if ($negotiableQuote->getNegotiatedPriceValue() !== null) {
            switch ($negotiableQuote->getNegotiatedPriceType()) {
                case NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT:
                    $priceMultiplier -= $negotiableQuote->getNegotiatedPriceValue() / 100;
                    break;
                case NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_AMOUNT_DISCOUNT:
                    $subtotal = $this->getQuoteOriginalSubtotal($quote);
                    if ($subtotal > 0) {
                        $priceMultiplier -= $negotiableQuote->getNegotiatedPriceValue() / $subtotal;
                    }
                    break;
                case NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL:
                    $subtotal = $this->getQuoteOriginalSubtotal($quote);
                    if ($subtotal > 0) {
                        $priceMultiplier = $negotiableQuote->getNegotiatedPriceValue() / $subtotal;
                    }
                    break;
            }
        }
        return $priceMultiplier;
    }

    /**
     * Get original subtotal for negotiable quote.
     *
     * @param CartInterface $quote
     * @return float
     */
    private function getQuoteOriginalSubtotal(CartInterface $quote)
    {
        $totals = $this->quoteTotalsFactory->create(['quote' => $quote]);
        return $totals->getCatalogTotalPrice();
    }

    /**
     * Initialize quote model instance.
     *
     * @param int $quoteId
     * @return CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuote($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        if ($negotiableQuote === null
            || !$negotiableQuote->getIsRegularQuote()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        return $quote;
    }

    /**
     * @inheritdoc
     */
    public function getOriginalPriceByItem(CartItemInterface $quoteItem, $isTax = true, $isDiscount = true)
    {
        $originalPrice = 0;

        $this->setNegotiableQuoteItem($quoteItem);
        if ($quoteItem->getExtensionAttributes() !== null
            && $quoteItem->getExtensionAttributes()->getNegotiableQuoteItem() !== null) {
            $price = $quoteItem->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalPrice();
            $tax = ($this->taxConfig->priceIncludesTax($quoteItem->getStoreId()) && $isTax)
                ? $quoteItem->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalTaxAmount()
                : 0;

            $discount = $isDiscount
                ? $quoteItem->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalDiscountAmount()
                : 0;
            $originalPrice = $price + $tax - $discount;
        }

        return $originalPrice;
    }

    /**
     * @inheritdoc
     */
    public function recalculateOriginalPriceTax(
        $quoteId,
        $needRecalculatePrice = false,
        $needRecalculateRule = false,
        $needSaveQuote = true,
        $needSaveItems = true
    ) {
        $quote = $this->getQuote($quoteId);
        $this->resetOriginalPrice($quote, $needRecalculatePrice, $needRecalculateRule);
        $quote->collectTotals();

        $isNewShipping = $quote->getShippingAddress()->isObjectNew();
        $isNewBilling = $quote->getBillingAddress()->isObjectNew();
        $quote->getShippingAddress()->isObjectNew(true);
        $quote->getBillingAddress()->isObjectNew(true);

        $quote->setTotalsCollectedFlag(false);
        $this->resetNegotiableQuoteItemFromQuote($quote, $needSaveItems, $needRecalculateRule);
        $this->updateQuoteItemsCustomPrices($quoteId, $needSaveQuote);

        $quote->getShippingAddress()->isObjectNew($isNewShipping);
        $quote->getBillingAddress()->isObjectNew($isNewBilling);

        return true;
    }

    /**
     * Reset custom prices in quote items to catalog prices.
     *
     * @param CartInterface $quote
     * @param bool $needRecalculatePrice
     * @param bool $needRecalculateRule
     * @return $this
     */
    private function resetOriginalPrice(CartInterface $quote, $needRecalculatePrice, $needRecalculateRule)
    {
        $quoteCurrency = $quote->getCurrency();
        /** @var CartItemInterface $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $itemToUpdate = [];
            if ($needRecalculateRule) {
                $itemToUpdate['use_discount'] = true;
            }
            if (!$needRecalculatePrice) {
                $price = $this->getOriginalPriceByItem($quoteItem, true, !$needRecalculateRule);
                if ($quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()) {
                    $price = $price * $quoteCurrency->getBaseToQuoteRate();
                }
                $itemToUpdate['custom_price'] = $price;
            }
            $this->updateItemByArray($quoteItem, $itemToUpdate, $needRecalculatePrice);
        }
        $quote->setTotalsCollectedFlag(false);
        return $this;
    }

    /**
     * Set data for negotiable quote items from quote items.
     *
     * @param CartInterface $quote
     * @param bool $needSaveItems [optional]
     * @param bool $needRecalculateRule [optional]
     * @return $this
     */
    private function resetNegotiableQuoteItemFromQuote(
        CartInterface $quote,
        $needSaveItems = false,
        $needRecalculateRule = false
    ) {
        $negotiableQuoteItems = [];
        foreach ($quote->getAllItems() as $quoteItem) {
            $this->setNegotiableQuoteItem($quoteItem);
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $price = $quoteItem->getBasePrice();
            $taxAmountPerItem = $quoteItem->getBaseTaxAmount() / $quoteItem->getQty();
            $negotiableQuoteItem = $quoteItem->getExtensionAttributes()->getNegotiableQuoteItem();
            $negotiableQuoteItem->setItemId($quoteItem->getItemId())
                ->setOriginalPrice($price)
                ->setOriginalTaxAmount($taxAmountPerItem);
            if ($needRecalculateRule) {
                $discountAmountPerItem = $this->getBaseTotalDiscountAmount($quoteItem) / $quoteItem->getQty();
                $negotiableQuoteItem->setOriginalDiscountAmount($discountAmountPerItem);
            } else {
                $negotiableQuoteItem->setOriginalPrice($price + $negotiableQuoteItem->getOriginalDiscountAmount());
            }
            $negotiableQuoteItems[] = $negotiableQuoteItem;
        }
        if ($needSaveItems) {
            $this->negotiableQuoteItemResource->saveList($negotiableQuoteItems);
        }
        if ($needRecalculateRule) {
            $negotiableQuote = $this->retrieveNegotiableQuote($quote);
            $negotiableQuote->setAppliedRuleIds($quote->getAppliedRuleIds());
        }

        return $this;
    }

    /**
     * Calculate base total discount for quote item.
     *
     * @param CartItemInterface $quoteItem
     * @return int
     */
    private function getBaseTotalDiscountAmount(CartItemInterface $quoteItem)
    {
        $totalDiscountAmount = 0;
        $children = $quoteItem->getChildren();
        if (!empty($children) && $quoteItem->isChildrenCalculated()) {
            foreach ($children as $child) {
                $totalDiscountAmount += $child->getBaseDiscountAmount();
            }
        } else {
            $totalDiscountAmount = $quoteItem->getBaseDiscountAmount();
        }
        return $totalDiscountAmount;
    }

    /**
     * Retrieve negotiable quote from $quote.
     *
     * @param CartInterface $quote
     * @return NegotiableQuoteInterface|null
     */
    private function retrieveNegotiableQuote(CartInterface $quote)
    {
        return ($quote
            && $quote->getExtensionAttributes()
            && $quote->getExtensionAttributes()->getNegotiableQuote())
            ? $quote->getExtensionAttributes()->getNegotiableQuote()
            : null;
    }

    /**
     * Set negotiable quote item in quote item as extension attribute.
     *
     * @param CartItemInterface $quoteItem
     * @return void
     */
    private function setNegotiableQuoteItem(CartItemInterface $quoteItem)
    {
        if (!$quoteItem->getExtensionAttributes()
            || !$quoteItem->getExtensionAttributes()->getNegotiableQuoteItem()
            || !$quoteItem->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalPrice()
        ) {
            $negotiableItem = $this->negotiableQuoteItemFactory->create()->load($quoteItem->getItemId());
            $negotiableItem->setItemId($quoteItem->getItemId());
            $quoteItemExtension = $this->extensionFactory
                ->create(\Magento\Quote\Api\Data\CartItemInterface::class)
                ->setNegotiableQuoteItem($negotiableItem);
            $quoteItem->setExtensionAttributes($quoteItemExtension);
        }
    }

    /**
     * Set negotiable quote prices.
     *
     * @param NegotiableQuoteInterface $negotiableQuote
     * @param CartInterface $quote
     * @param bool $preserveCustomPrice
     * @return void
     */
    private function setNegotiableQuotePrices(
        NegotiableQuoteInterface $negotiableQuote,
        CartInterface $quote,
        bool $preserveCustomPrice
    ) {
        $totals = $this->quoteTotalsFactory->create(['quote' => $quote]);
        $negotiableQuote->setData(NegotiableQuoteInterface::ORIGINAL_TOTAL_PRICE, $totals->getCatalogTotalPrice(true));
        $negotiableQuote->setData(NegotiableQuoteInterface::BASE_ORIGINAL_TOTAL_PRICE, $totals->getCatalogTotalPrice());
        if ($negotiableQuote->getStatus() !== NegotiableQuoteInterface::STATUS_CREATED) {
            $negotiableQuote->setData(NegotiableQuoteInterface::BASE_NEGOTIATED_TOTAL_PRICE, $totals->getSubtotal());
            $negotiableQuote->setData(NegotiableQuoteInterface::NEGOTIATED_TOTAL_PRICE, $totals->getSubtotal(true));
        } else {
            $negotiableQuote->setData(
                NegotiableQuoteInterface::BASE_NEGOTIATED_TOTAL_PRICE,
                $totals->getCatalogTotalPrice()
            );
            $negotiableQuote->setData(
                NegotiableQuoteInterface::NEGOTIATED_TOTAL_PRICE,
                $totals->getCatalogTotalPrice(true)
            );

            // To preserve custom price for just created negotiable quote we set it as Negotiated Proposed Price
            if ($preserveCustomPrice) {
                $negotiableQuote->setNegotiatedPriceValue($negotiableQuote->getNegotiatedTotalPrice());
                $negotiableQuote->setNegotiatedPriceType(
                    NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL
                );
            }
        }
    }

    /**
     * Retrieve true if quote custom price should be preserved.
     *
     * Normally quote item custom price field is used to set negotiable price value.
     * But customizations can set the value to this field before negotiable flow is
     * started and we just lose this value. To manage this case we should define if
     * quote item custom price field already has value when the negotiable quote is just created.
     *
     * @param CartInterface $quote
     * @param string $negotiableQuoteStatus
     * @return bool
     */
    private function preserveQuoteCustomPrice(CartInterface $quote, string $negotiableQuoteStatus): bool
    {
        if ($negotiableQuoteStatus !== NegotiableQuoteInterface::STATUS_CREATED) {
            return false;
        }

        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getCustomPrice()) {
                return true;
            }
        }

        return false;
    }
}
