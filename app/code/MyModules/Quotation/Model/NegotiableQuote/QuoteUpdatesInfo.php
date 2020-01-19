<?php


namespace Redington\Quotation\Model\NegotiableQuote;

/**
 * Class for retrieving quote updates data.
 */
class QuoteUpdatesInfo extends \Magento\NegotiableQuote\Model\QuoteUpdatesInfo
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\TotalsFactory
     */
    private $quoteTotalsFactory;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    private $quoteHelper;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $negotiableQuoteItemManagement;

    /**
     * @var \Magento\Framework\Filter\StripTags
     */
    private $tagFilter;

    /**
     * Quote totals.
     *
     * @var \Magento\NegotiableQuote\Model\Quote\Totals
     */
    private $totals;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \Magento\NegotiableQuote\Model\QuoteUpdatesInfo\ProductOptions
     */
    private $productOptions;

    /**
     * @var \Magento\NegotiableQuote\Model\Status\LabelProviderInterface
     */
    private $labelProvider;

    /**
     * @var \Magento\NegotiableQuote\Model\Discount\StateChanges\Provider
     */
    private $messageProvider;

    /**
     * QuoteUpdatesInfo constructor.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param Quote\TotalsFactory $quoteTotalsFactory
     * @param \Magento\NegotiableQuote\Helper\Quote $quoteHelper
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagement
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\NegotiableQuote\Model\QuoteUpdatesInfo\ProductOptions $productOptions
     * @param \Magento\NegotiableQuote\Model\Status\LabelProviderInterface $labelProvider
     * @param \Magento\NegotiableQuote\Model\Discount\StateChanges\Provider $messageProvider
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\NegotiableQuote\Model\Quote\TotalsFactory $quoteTotalsFactory,
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper,
        \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagement,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Magento\Framework\UrlInterface $url,
        \Magento\NegotiableQuote\Model\QuoteUpdatesInfo\ProductOptions $productOptions,
        \Magento\NegotiableQuote\Model\Status\LabelProviderInterface $labelProvider,
        \Magento\NegotiableQuote\Model\Discount\StateChanges\Provider $messageProvider,
		\Magento\Framework\Pricing\Helper\Data $pricingHelper,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
		\Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
		\Magento\Quote\Model\Quote\ItemFactory $itemFactory,
		\Redington\Quotation\Helper\Data $helperData
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->quoteTotalsFactory = $quoteTotalsFactory;
        $this->quoteHelper = $quoteHelper;
        $this->negotiableQuoteItemManagement = $negotiableQuoteItemManagement;
        $this->tagFilter = $tagFilter;
        $this->url = $url;
        $this->productOptions = $productOptions;
        $this->labelProvider = $labelProvider;
        $this->messageProvider = $messageProvider;
		$this->pricingHelper = $pricingHelper;
		$this->customerFactory = $customerFactory;
		$this->sourceCollectionFactory = $sourceCollectionFactory;
		$this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
		$this->itemFactory = $itemFactory;
		$this->helperData = $helperData;
    }

    /**
     * Get quote update data.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param array $quoteData [optional]
     * @return array
     */
    public function getQuoteUpdatedData(\Magento\Quote\Api\Data\CartInterface $quote, array $quoteData = [])
    {
        $hasUnconfirmedChanges = false;
        if ($quote->getExtensionAttributes() !== null
            && $quote->getExtensionAttributes()->getNegotiableQuote() !== null) {
            $hasUnconfirmedChanges = $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->getHasUnconfirmedChanges();
        }
        $status = $this->getQuoteStatus($quote);
		$currencyRate = $quote->getBaseToQuoteRate();
        $data = [
            'quoteId' => $quote->getId(),
            'items' => $this->getAllUpdatedItems($quote),
            'cost' => $this->formatPrice($quote, $this->getQuoteTotals($quote)->getTotalCost()),
            'subtotal' => $this->formatPrice($quote, $quote->getSubtotal()),
            'subtotalTax' => $this->formatPrice($quote, $this->getQuoteTotals($quote)->getCatalogTotalPriceWithTax()),
            'discount' => $this->formatPrice($quote, $this->getQuoteTotals($quote)->getCartTotalDiscount()),
            'discountOrigin' => $this->getQuoteTotals($quote)->getCartTotalDiscount()* $currencyRate,
            'tax' => $this->formatPrice($quote, $this->getQuoteTotals($quote)->getOriginalTaxValue()),
           'catalogPrice' => $this->getDuplicatePriceArray(
                $quote,
                $this->getQuoteTotals($quote)->getCatalogTotalPrice(),
                $this->getQuoteTotals($quote)->getCatalogTotalPrice(true)
            ),
            'catalogPriceValue' => $quote->getSubtotal(),
            'quoteTax' => $this->formatPrice($quote, $this->getQuoteTotals($quote)->getTaxValue()),
            'quoteTaxAdd' => $this->getQuoteTotals($quote)->getTaxValueForAddInTotal(),
            'quoteSubtotal' => $this->getDuplicatePriceArray(
                $quote,
                $this->getQuoteTotals($quote)->getSubtotal(),
                $this->getQuoteTotals($quote)->getSubtotal(true)
            ),
            'grandTotal' => $this->getDuplicatePriceArray(
                $quote,
                $this->getQuoteTotals($quote)->getGrandTotal(),
                $this->getQuoteTotals($quote)->getGrandTotal(true)
            ),
            'hasChanges' => $hasUnconfirmedChanges,
            'shippingPrice' => $this->formatPrice($quote, $this->getQuoteProposedShippingPrice($quote, $quoteData)),
            'currencyRate' => $this->getCurrencyRate($quote),
            'currencyLabel' => $this->getCurrencyRateLabel($quote),
            'quoteStatus' => $this->labelProvider->getLabelByStatus($status)
        ];

        return $data;
    }

    /**
     * Get quote messages.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return array
     */
    public function getMessages(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $notifications = $this->messageProvider->getChangesMessages($quote);
        $messages = [];
        foreach ($notifications as $message) {
            if ($message) {
                $messages[] = ['type' => 'warning', 'text' => $message];
            }
        }

        if ($this->quoteHelper->isLockMessageDisplayed()) {
            $message = __(
                'Quote is sent to partner. It will available once released by the partner.'
            );
            $messages[] = ['type' => 'warning', 'text' => $message];
        }

        return $messages;
    }

    /**
     * Get quote status.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return string
     */
    private function getQuoteStatus(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $status = '';
        if ($quote->getExtensionAttributes() !== null
            && $quote->getExtensionAttributes()->getNegotiableQuote() !== null) {
            $status = $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->getStatus();
        }

        return $status;
    }

    /**
     * Get all quote updated items info.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return array
     */
    private function getAllUpdatedItems(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $updatedItems = [];
        $items = $this->getVisibleQuoteItems($quote);
        $itemIdInc = 1;
		$currencyRate = $quote->getBaseToQuoteRate();
		
		$plantcode = $this->helperData->getCustomerDetails($quote->getId());	
		 
        foreach ($items as $item) {
			$qty = $this->getProductQtyInPlant($item->getSku(),$plantcode);
			
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $price = $item->getBasePrice() - $item->getBaseDiscountAmount() / $item->getQty();
			$price = $price * $currencyRate;
			$model = $this->itemFactory->create()->load($item->getId());
			$customPrice = $model->getCustomPrice();
			$rowTotal = $model->getRowTotal();
			$tax = $model->getTaxAmount();
			$subtotal = $customPrice * $item->getQty();
			$baseRowTotal = $item->setBaseRowTotal($subtotal);
			$subtotal = $this->getQuoteTotals($quote)->isTaxDisplayedWithSubtotal()
                ? $baseRowTotal + $item->getBaseTaxAmount()
                : $baseRowTotal;
            $originalPrice = 0;
			if ($item->getExtensionAttributes() !== null
                && $item->getExtensionAttributes()->getNegotiableQuoteItem() !== null) {
                $originalPrice = $item->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalPrice();
            }
            $updatedItems[] = [
                'id' => $item->getItemId() ?? $itemIdInc++,
                'productId' => (int)$item->getProduct()->getId(),
                'name' => $item->getProduct()->getName(),
                'url' => $this->getProductUrlByItem($item),
                'sku' => $item->getProduct()->getSku(),
                'cost' => $this->formatPrice($quote, $item->getProduct()->getPrice() * $currencyRate),
                'stock' => number_format($qty),
                'qty' => $item->getQty(),
                'subtotal' => $this->formatPrice($quote, $rowTotal - $item->getDiscountAmount()),
                'tax' => $this->formatPrice($quote, $tax),
                'subtotalTax' => $this->formatPrice($quote, $rowTotal - $item->getDiscountAmount()),
                'config' => $this->quoteHelper->retrieveCustomOptions($item),
                'canConfig' => $item->getProduct()->canConfigure(),
                'messages' => $this->getItemsMessages($item),
                'proposedPrice' => $this->formatPrice($quote, $customPrice),
                'cartPrice' => $this->formatPrice(
                    $quote,
                    $this->negotiableQuoteItemManagement->getOriginalPriceByItem($item)
                ),
                'productSku' => $item->getProduct()->getData('sku'),
                'options' => $this->getItemOptions($item),
                'originalPrice' => $this->formatPrice($quote, $originalPrice)
            ];
        }

        return $updatedItems;
    }

    /**
     * Get quote item options.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return array
     */
    private function getItemOptions(\Magento\Quote\Api\Data\CartItemInterface $item)
    {
        $optionsReturn = [];
        $configuration = $this->productOptions->getConfigurationForProductType($item->getProductType());
        $options = $configuration->getOptions($item);
        if ($options) {
            $params = [
                'max_length' => 55,
                'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
            ];
            foreach ($options as $option) {
                $formattedOptionValue = $this->productOptions->getFormattedOptionValue($option, $params);
                $optionsReturn[] = [
                    'label' => $option['label'],
                    'value' => $formattedOptionValue['full_view']
                        ?? $this->tagFilter->filter($formattedOptionValue['value'])
                ];
            }
        }
        return $optionsReturn;
    }

    /**
     * Retrieve product url.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return string
     */
    private function getProductUrlByItem(\Magento\Quote\Api\Data\CartItemInterface $item)
    {
        $params = [
            'id' => $item->getProduct()->getId()
        ];
        return $this->url->getUrl('catalog/product/edit', $params);
    }

    /**
     * Get items messages.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return array
     */
    private function getItemsMessages(\Magento\Quote\Api\Data\CartItemInterface $item)
    {
        $messages = [];
        foreach ($item->getMessage(false) as $messageError) {
            if (!empty($messageError)) {
                $messages[] = [
                    'type' => $item->getHasError() ? 'error' : 'notice',
                    'message' => $messageError
                ];
            }
        }
        return $messages;
    }

    /**
     * Format price.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $price
     * @return string
     */
    private function formatPrice(\Magento\Quote\Api\Data\CartInterface $quote, $price)
    {
        return $this->priceCurrency->format(
            $price,
            false,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
           // $quote->getCurrency()->getCurrentCurrencyCode()
		   $quote->getQuoteCurrencyCode()
        );
    }

    /**
     * Get visible quote items.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return array
     */
    private function getVisibleQuoteItems(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $items = [];
        $quoteItems = $quote->getItemsCollection();
        foreach ($quoteItems as $item) {
            if (!$item->isDeleted() && !$item->getParentItem()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Get quote totals.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return \Magento\NegotiableQuote\Model\Quote\Totals
     */
    private function getQuoteTotals(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        if (!$this->totals) {
            $this->totals = $this->quoteTotalsFactory->create(['quote' => $quote]);
        }
		
		return $this->totals;
    }

    /**
     * Get quote shipping price.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param array $quoteData
     * @return float|null
     */
    private function getQuoteProposedShippingPrice(\Magento\Quote\Api\Data\CartInterface $quote, array $quoteData)
    {
        $shippingPrice = $this->getQuoteTotals($quote)->getQuoteShippingPrice();

        if (isset($quoteData['shipping'])) {
            if (!empty($quoteData['shipping'])) {
                $shippingPrice = $quoteData['shipping'];
            }

            if (isset($quoteData['shippingMethod'])
                && ($quoteData['shipping'] === '' && !$shippingPrice)
            ) {
                $shippingPrice = $quote->getShippingAddress()->getBaseShippingAmount();
            }
        }

        return $shippingPrice;
    }

    /**
     * Get label for currency rate if base and quote currencies are different.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return string
     */
    private function getCurrencyRateLabel(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $label = '';
        $quoteCurrency = $quote->getCurrency();
        if ($quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()) {
            $label = $quoteCurrency->getQuoteCurrencyCode();
        }
        return $label;
    }

    /**
     * Get currency rate if base and quote currencies are different.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return string
     */
    private function getCurrencyRate(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $rate = 1;
        $quoteCurrency = $quote->getCurrency();
        if ($quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()
            && $quoteCurrency->getBaseToQuoteRate()
        ) {
            $rate = $quoteCurrency->getBaseToQuoteRate();
        }
        return $rate;
    }

    /**
     * Get array with  keys base and quote for price in different currencies.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param float $basePrice
     * @param float $quotePrice
     * @return array
     */
    private function getDuplicatePriceArray(\Magento\Quote\Api\Data\CartInterface $quote, $basePrice, $quotePrice)
    {
        
        $quoteCurrency = $quote->getCurrency();
        if ($quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()) {
            $priceArray['quote'] = $this->formatPriceInQuoteCurrency($quote, $quotePrice);
        }else{
			$priceArray = ['base' => $this->formatPrice($quote, $basePrice)];
			
		}
        return $priceArray;
    }

    /**
     * Convert and format price in quote currency.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $price
     * @return string
     */
    private function formatPriceInQuoteCurrency(\Magento\Quote\Api\Data\CartInterface $quote, $price)
    {
        return $this->priceCurrency->format(
            $price,
            false,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $quote->getCurrency()->getQuoteCurrencyCode()
        );
    }
	
	/*public function getCustomerDetails($quote){
		
		
		$customerId = $quote->getCustomerId();
		$customerModel = $this->customerFactory->create()->loadByEmail($quote->getCustomerEmail());
		
		 $sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled', 1)
                ->addFieldToFilter('distribution', $customerModel->getZDistributionChannel())
                ->addFieldToFilter('sap_account_code',  $customerModel->getZSapCode());
			
			$data = $sourceCollection->getData();
			if(isset($data) && $data!=''){
				return $data[0]['plant_code'];
			}else{
				return '';
			}
		
	}*/
	
	public function getProductQtyInPlant($productSku,$plantCode){
        $filePath = '/var/log/Redington_admin_catalog_Qty.log';
        try{
            
            $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku',$productSku)
			->addFieldToFilter('source_code',$plantCode);
            if(sizeof($sourceCollection) > 0){
                $sourceData = $sourceCollection->getFirstItem();
                $qtyInPlant = $sourceData->getQuantity();
                $this->logMessage('Available qty in plant '.$plantCode. ' is '.$qtyInPlant .' for product ' .$productSku, $filePath);
                return $qtyInPlant;
            }else{
                return false;
            }
        }catch(\Exception $e){
            $this->logMessage('Error in getting product qty'.$e->getMessage(), $filePath);
            return false;
        }
    }
	
	/**
    * Write log
    */
    public function logMessage($message, $filePath = '/var/log/system.log') {        
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}
