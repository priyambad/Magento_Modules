<?php
 

namespace Redington\Quotation\Block\Quote;

use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\NegotiableQuote\Helper\Quote as NegotiableQuoteHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Block for Quote Totals.
 *
 * @api
 */
class Totals extends \Magento\NegotiableQuote\Block\Quote\Totals
{
    /**
     * Associated array of totals
     * array(
     *  $totalCode => $totalObject
     * )
     *
     * @var array
     */
    protected $totals = [];

    /**
     * Quote tax value
     *
     * @var float|null
     */
    protected $taxValue = null;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface
     */
    protected $collectedQuote;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\Totals
     */
    protected $quoteTotals;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\TotalsFactory
     */
    protected $quoteTotalsFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface
     */
    protected $restriction;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var bool
     */
    protected $inQuoteCurrency = true;

    /**
     * Constructor.
     *
     * @param TemplateContext $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param NegotiableQuoteHelper $negotiableQuoteHelper
     * @param RestrictionInterface $restriction
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\NegotiableQuote\Model\Quote\TotalsFactory $quoteTotalsFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        NegotiableQuoteHelper $negotiableQuoteHelper,
        RestrictionInterface $restriction,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\NegotiableQuote\Model\Quote\TotalsFactory $quoteTotalsFactory,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $postDataHelper, $negotiableQuoteHelper, $restriction, $taxConfig, $quoteTotalsFactory, $priceCurrency,$data);
    }

    /**
     * Initialize order totals array.
     *
     * @return $this
     */
    protected function initTotals()
    {
        $this->initSubtotal()
            ->initProposedQuotePrice();;
            //->initShipping()
            //->initTax()
            //->initGrandTotal();

        return $this;
    }

    /**
     * Get totals array for visualization.
     *
     * @return array
     */
    public function getTotals()
    {
        if (count($this->totals) == 0) {
            $this->initTotals();
        }
        return $this->totals;
    }

    /**
     * Get formatted price value including currency.
     *
     * @param float $price
     * @param string $code
     * @return string
     */
    public function formatPrice($price, $code)
    {
        return $this->negotiableQuoteHelper->formatPrice($price, $code);
    }

    /**
     * Init subtotal.
     *
     * @return $this
     */
    protected function initSubtotal()
    {
        $displayIncludeTax = $this->_scopeConfig->getValue(
            \Magento\Tax\Model\Config::XML_PATH_DISPLAY_SALES_SUBTOTAL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()
        );
        $subtotalClass = $displayIncludeTax == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX ? 'hidden' : '';
        $subtotalTaxClass = $displayIncludeTax == \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX ? 'hidden' : '';

        $subtotals = [
            'subtotal' => [
                'value' => $this->getQuoteTotals()->getCatalogTotalPriceWithoutTax($this->inQuoteCurrency),
                'label' => __('Subtotal (Excl. Tax)'),
                'class' => $subtotalClass
            ],
            'subtotalTax' => [
                'value' => $this->getQuoteTotals()->getCatalogTotalPriceWithTax($this->inQuoteCurrency),
                'label' => __('Subtotal (Incl. Tax)'),
                'class' => $subtotalTaxClass
            ],
            'discount' => [
                'value' => -$this->getQuoteTotals()->getCartTotalDiscount($this->inQuoteCurrency),
                'label' => __('Discount'),
                'class' => $this->getQuoteTotals()->getCartTotalDiscount() ? '' : 'hidden'
            ],
            'tax' => [
                'value' => $this->getQuoteTotals()->getOriginalTaxValue($this->inQuoteCurrency),
                'label' => __('Estimated Tax'),
                'class' => ''
            ],
        ];

        /*$this->totals['catalog_price'] = new \Magento\Framework\DataObject(
            [
                'code' => 'catalog_price',
                'subtotals' => $subtotals,
                'block_name' => 'negotiable.quote.totals.original',
                'value' => $this->getQuoteTotals()->getCatalogTotalPrice($this->inQuoteCurrency),
                'label' => $this->getCatalogTotalPriceLable(),
                'base_currency' => $this->getQuote()->getCurrency()->getQuoteCurrencyCode(),
                'rate' => $this->getQuote()->getCurrency()->getBaseToQuoteRate(),
            ]
        );*/

        return $this;
    }

    /**
     * Get catalog total price label.
     *
     * @return \Magento\Framework\Phrase
     */
    private function getCatalogTotalPriceLable()
    {
        return $this->isTaxDisplayedWithGrandTotal()
            ? __('Catalog Total Price (Incl. Tax)')
            : __('Catalog Total Price (Excl. Tax)');
    }

    /**
     * Init proposed quote price.
     *
     * @return $this
     */
    protected function initProposedQuotePrice()
    {
        $this->totals['proposed_quote_price'] = new \Magento\Framework\DataObject(
            [
                'code' => 'proposed_quote_price',
                'value' => $this->getQuoteTotals()->getSubtotal($this->inQuoteCurrency),
                'label' => $this->isTaxDisplayedWithGrandTotal()
                    ? __('Quote Subtotal (Incl. Tax)')
                    : __('Quote Subtotal (Excl. Tax)')
            ]
        );

        return $this;
    }

    /**
     * Init tax.
     *
     * @return $this
     */
    protected function initTax()
    {
        $this->totals['quote_tax'] = new \Magento\Framework\DataObject(
            [
                'code' => 'quote_tax',
                'value' => $this->getQuoteTotals()->getTaxValue($this->inQuoteCurrency),
                'label' => __('Estimated Tax')
            ]
        );

        return $this;
    }

    /**
     * Init grand total.
     *
     * @return $this
     */
    protected function initGrandTotal()
    {
        $this->totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'value' => $this->getQuoteTotals()->getGrandTotal($this->inQuoteCurrency),
                'label' => __('Quote Grand Total (Incl. Tax)'),
            ]
        );

        return $this;
    }

    /**
     * Init base grand total.
     *
     * @return $this
     */
    protected function initBaseGrandTotal()
    {
        $quoteCurrency = $this->getQuote()->getCurrency();
        if ($quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()) {
            $this->totals['base_grand_total'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'base_grand_total',
                    'field' => 'base_grand_total',
                    'style' => 'no-border',
                    'currency' => $this->getQuote()->getCurrency()->getBaseCurrencyCode(),
                    'strong' => true,
                    'value' => $this->getQuoteTotals()->getGrandTotal(),
                    'label' => __('Grand Total to Be Charged'),
                ]
            );
        }

        return false;
    }

    /**
     * Init shipping price.
     *
     * @return $this
     */
    protected function initShipping()
    {
        if ($this->getNegotiableQuote()->getShippingPrice() !== null
            || ($this->getQuote()->getShippingAddress() !== null
                && $this->getQuote()->getShippingAddress()->getShippingMethod())
        ) {
            $proposedPrice = $this->getQuoteTotals()->getQuoteShippingPrice($this->inQuoteCurrency);
            $this->totals['proposed_shipping'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'proposed_shipping',
                    'value' => $proposedPrice,
                    'label' => __('Shipping & Handling')
                ]
            );
        }

        return $this;
    }

    /**
     * Get quote with collected totals.
     *
     * @return \Magento\Quote\Api\Data\CartInterface|null
     */
    protected function getCollectedQuote()
    {
        if (!$this->collectedQuote && $this->getQuote(true)) {
            $this->collectedQuote = $this->getQuote(true);
            if ($this->restriction->canSubmit()) {
                $this->collectedQuote->collectTotals();
            }
        }

        return $this->collectedQuote;
    }

    /**
     * Get quote totals.
     *
     * @return \Magento\NegotiableQuote\Model\Quote\Totals
     */
    private function getQuoteTotals()
    {
        if (!$this->quoteTotals) {
            $this->quoteTotals = $this->quoteTotalsFactory->create(['quote' => $this->getCollectedQuote()]);
        }

        return $this->quoteTotals;
    }

    /**
     * Is tax included to grand total value.
     *
     * @return bool
     */
    protected function isTaxDisplayedWithGrandTotal()
    {
        return $this->taxConfig->displaySalesTaxWithGrandTotal($this->_storeManager->getStore());
    }
}
