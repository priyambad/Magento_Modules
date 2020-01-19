<?php

namespace Redington\Sales\Block\Order\Item\Renderer;

/**
 * Order item render block
 *
 * @api
 * @since 100.0.2
 */
class DefaultRenderer extends \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer
{

	/**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Store\Model\StoreManagerInterface $_storeManage,
        \Magento\Directory\Model\CurrencyFactory $currency,
        array $data = []
    ) {
        $this->string = $string;
        $this->_productOptionFactory = $productOptionFactory;
        $this->_storeManage = $_storeManage;
        $this->currency = $currency;
        parent::__construct($context, $string, $productOptionFactory, $data);
    }

    public function getCurrencySymbol(){
    	$currencyCode = $this->_storeManage->getStore()->getCurrentCurrency()->getCode();
        $currencySymbol = $this->getCurrencySymbolFromCode($currencyCode);
        return $currencySymbol;
    }

    public function getCurrencySymbolFromCode($currencyCode){
        $currencyData = $this->currency->create()->load($currencyCode);
        return $currencyData->getCurrencySymbol();
    }
}