<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */
namespace Redington\Quotation\Block;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Framework\Data\Helper\PostHelper;

/**
 * Quote block
 * 
 */
class Quote extends \Magento\Framework\View\Element\Template
{
    /**
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    
    /**
     * 
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param ImageHelper $imageHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param RestrictionInterface $restriction
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param PostHelper $postDataHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        ImageHelper $imageHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        RestrictionInterface $restriction,		
        \Magento\Company\Api\AuthorizationInterface $authorization,
		PostHelper $postDataHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->cart = $cart;
        $this->imageHelper = $imageHelper;
        $this->productFactory = $productFactory;  
		$this->restriction = $restriction;
        $this->authorization = $authorization;
		$this->postDataHelper = $postDataHelper;
        $this->checkoutSession = $checkoutSession;
		$this->_productRepositoryFactory = $productRepositoryFactory;
    }
    /**
     * 
     * @return string
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    
    }
    /**
     * Retrieve current quote
     *
     * @return string
     */
    public function getQuote()
    {
        return $this->cart->getQuote();
    }
    
    /**
     * 
     * @param type $product
     * @param type $width
     * @param type $height
     * @return type
     */
    public function getProductImageUrl($product, $width, $height) {
		//$product = $this->_productRepositoryFactory->create()->getById($product->getId());
        $product = $this->getProductById($product->getProductId());
		
        return $this->imageHelper->init($product, 'product_page_image_small')
                ->constrainOnly(FALSE)
                ->keepAspectRatio(TRUE)
                ->keepFrame(FALSE)
                ->resize($width, $height)
                ->getUrl();	
				
				
    }
    
    /**
     * 
     * @param int $id
     * @return object
     */
    public function getProductById($id) {
        return $this->productFactory->create()->load($id);
    }
	
	/**
     * Returns post data for removing item from quote.
     *
     * @return string
     */
    public function getRemoveParams($quoteId, $quoteItemId)
    {
        $url = $this->getUrl(
            '*/*/itemDelete',
            [
                'quote_id' => $quoteId,
                'quote_item_id' => $quoteItemId
            ]
        );

        return $this->postDataHelper->getPostData($url);
    }
	
	/**
     * Perform permissions check.
     *
     * @return bool
     */
    public function isAllowedManage()
    {
        return $this->authorization->isAllowed('Magento_NegotiableQuote::manage');
    }

    /**
     * Is submit available.
     *
     * @return bool
     */
    public function isSubmitAvailable()
    {
        return $this->restriction->canSubmit();
    }
    
    /**
     * @return string
     */
    public function getContinueShoppingUrl()
    {
        $url = $this->getData('continue_shopping_url');
        if ($url === null) {
            $url = $this->checkoutSession->getContinueShoppingUrl(true);
            if (!$url) {
                $url = $this->_urlBuilder->getUrl();
            }
            $this->setData('continue_shopping_url', $url);
        }
        return $url;
    }
}
