<?php

namespace Redington\Quotation\Block\Adminhtml\Quote\View;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Class Shipping.
 *
 * @api
 */
class Totals extends \Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals
{
    /**
     * @var bool
     */
    protected $inQuoteCurrency = false;

    /**
     * Initialize quote totals array.
     *
     * @return $this
     */
    protected function initTotals()
    {
        $this->quoteTotals = $this->quoteTotalsFactory->create(['quote' => $this->getCollectedQuote()]);
        $this->initSubtotal()
            ->initShipping()
            ->initTax()
            ->initGrandTotal();

        $currency = $this->getQuote()->getCurrency();
        if ($currency->getQuoteCurrencyCode() != $currency->getBaseCurrencyCode()) {
            $totalsForDuplicatePrice = ['catalog_price', 'grand_total', 'proposed_quote_price'];
            foreach ($totalsForDuplicatePrice as $total) {
                if (isset($this->totals[$total])) {
                    $this->totals[$total]->setData('currency', $currency->getQuoteCurrencyCode());
                }
            }
            $this->totals['catalog_price']->setData(
                'base_currency',
                $this->getQuote()->getCurrency()->getBaseCurrencyCode()
            );
            $this->totals['catalog_price']->setData('quote_value', $this->quoteTotals->getSubtotal(true));
            //$this->totals['proposed_quote_price']->setData('quote_value', $this->quoteTotals->getSubtotal(true));
            $this->totals['grand_total']->setData('quote_value', $this->quoteTotals->getGrandTotal(true));
        }

        return $this;
    }

    /**
     * Init cost value.
     *
     * @return $this
     */
    protected function initCost()
    {
        $this->totals['cost'] = new \Magento\Framework\DataObject(
            ['code' => 'cost', 'value' => $this->quoteTotals->getTotalCost(), 'label' => __('Total Cost')]
        );

        return $this;
    }

    /**
     * Init negotiation.
     *
     * @return $this
     */
    protected function initNegotiation()
    {
        $negotiatedPriceType = $this->getNegotiableQuote() !== null
        ? $this->getNegotiableQuote()->getNegotiatedPriceType() : null;

        if (empty($negotiatedPriceType)) {
            $negotiatedPriceType = NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT;
        }

        $negotiatedPriceValue = $this->getNegotiableQuote() !== null
        ? $this->getNegotiableQuote()->getNegotiatedPriceValue() : null;

        $this->totals['negotiation'] = new \Magento\Framework\DataObject(
            [
                'code' => NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE,
                'code_value' => NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE,
                'field' => 'negotiation',
                'strong' => false,
                'value' => $negotiatedPriceValue,
                'type' => (int) $negotiatedPriceType,
                'block_name' => 'negotiable.quote.totals.negotiation',
                'label' => __('Negotiated Price'),
                'tax_value_add' => $this->quoteTotals->getTaxValueForAddInTotal(),
            ]
        );

        return $this;
    }

    /**
     * Init shipping.
     *
     * @return $this
     */
    protected function initShipping()
    {
        $this->totals['shipping'] = new \Magento\Framework\DataObject(
            [
                'code' => NegotiableQuoteInterface::SHIPPING_PRICE,
                'value' => $this->quoteTotals->getQuoteShippingPrice(),
                'label' => __('Shipping & Handling'),
                'role' => 'shipping-price-wrap',
            ]
        );

        return $this;
    }

    /**
     * Init quote total price.
     *
     * @return $this
     */
    protected function initQuoteTotalPrice()
    {
        $this->totals['quote_total_price'] = new \Magento\Framework\DataObject(
            [
                'code' => 'quote_total_price',
                'field' => 'quote_total_price',
                'value' => $this->quoteTotals->getQuoteTotalPrice(),
                'label' => __('Quote Subtotal (Incl. Tax)'),
            ]
        );

        return $this;
    }

    /**
     * Display prices.
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    public function displayPrices($price, $currency = null)
    {
        $quoteCurrency = $this->getQuote()->getCurrency();
        $currencyDisplay = isset($currency) ? $currency : $quoteCurrency->getBaseCurrencyCode();
        return $this->priceCurrency->format(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currencyDisplay
        );
    }
}
