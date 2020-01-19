<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Plugin\Checkout;


use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrlManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\FormatInterface as LocaleFormat;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface as ShippingMethodManager;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Framework\App\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class DefaultConfigProvider
{
    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerUrlManager
     */
    private $customerUrlManager;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteItemRepository
     */
    private $quoteItemRepository;

    /**
     * @var ShippingMethodManager
     */
    private $shippingMethodManager;

    /**
     * @var ConfigurationPool
     */
    private $configurationPool;

    /**
     * @param QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var LocaleFormat
     */
    protected $localeFormat;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    protected $viewConfig;

    /**
     * @var \Magento\Directory\Model\Country\Postcode\ConfigInterface
     */
    protected $postCodesConfig;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryHelper;

    /**
     * @var Cart\ImageProvider
     */
    protected $imageProvider;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingMethodConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
	
    /**
     * @var AddressMetadataInterface
     */
    private $addressMetadata;
	
	/**
     * @var AttributeOptionManagementInterface
     */
    protected $attributeOptionManager;


    /**
     * @var \Redington\Checkout\Helper\Data
     */
	protected $redingtonCheckoutHelper;

    /**
     * @param CheckoutHelper $checkoutHelper
     * @param Session $checkoutSession
     * @param CustomerRepository $customerRepository
     * @param CustomerSession $customerSession
     * @param CustomerUrlManager $customerUrlManager
     * @param HttpContext $httpContext
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param QuoteItemRepository $quoteItemRepository
     * @param ShippingMethodManager $shippingMethodManager
     * @param ConfigurationPool $configurationPool
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param LocaleFormat $localeFormat
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param FormKey $formKey
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Framework\View\ConfigInterface $viewConfig
     * @param \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig
     * @param Cart\ImageProvider $imageProvider
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Shipping\Model\Config $shippingMethodConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param UrlInterface $urlBuilder
	 * @param AddressMetadataInterface $addressMetadata
     * @param AttributeOptionManagementInterface $attributeOptionManager
     * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CheckoutHelper $checkoutHelper,
        CheckoutSession $checkoutSession,
        CustomerRepository $customerRepository,
        CustomerSession $customerSession,
        CustomerUrlManager $customerUrlManager,
        HttpContext $httpContext,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        ShippingMethodManager $shippingMethodManager,
        ConfigurationPool $configurationPool,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LocaleFormat $localeFormat,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        FormKey $formKey,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig,
        \Magento\Checkout\Model\Cart\ImageProvider $imageProvider,
        \Magento\Directory\Helper\Data $directoryHelper,
        CartTotalRepositoryInterface $cartTotalRepository,
        ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Model\Config $shippingMethodConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        UrlInterface $urlBuilder,
		AddressMetadataInterface $addressMetadata = null,
        AttributeOptionManagementInterface $attributeOptionManager = null,
        \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerUrlManager = $customerUrlManager;
        $this->httpContext = $httpContext;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->configurationPool = $configurationPool;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->localeFormat = $localeFormat;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->formKey = $formKey;
        $this->imageHelper = $imageHelper;
        $this->viewConfig = $viewConfig;
        $this->postCodesConfig = $postCodesConfig;
        $this->imageProvider = $imageProvider;
        $this->directoryHelper = $directoryHelper;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->scopeConfig = $scopeConfig;
        $this->shippingMethodConfig = $shippingMethodConfig;
        $this->storeManager = $storeManager;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->urlBuilder = $urlBuilder;
		$this->addressMetadata = $addressMetadata ?: ObjectManager::getInstance()->get(AddressMetadataInterface::class);
        $this->attributeOptionManager = $attributeOptionManager ??
            ObjectManager::getInstance()->get(AttributeOptionManagementInterface::class);
        $this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
        $result['customerData'] = $this->getCustomerData();
        return $result;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    private function getCustomerData()
    {
        $customerData = [];
		try {
			if ($this->isCustomerLoggedIn()) {
				$superUserId = $this->redingtonCheckoutHelper->getCompanyAdminId();

				$customer = $this->customerRepository->getById($superUserId);
				//$customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
				$customerData = $customer->__toArray();
				$defaultShippingAddress = [];
				$defaultShippingAddress = $customer->getDefaultShipping();
				foreach ($customer->getAddresses() as $key => $address) {
					if ($address->getCustomAttributes()) {
						$customerCustomAttributes = $customerData['addresses'][$key]['custom_attributes'];
						if(array_key_exists('is_forwarder', $customerCustomAttributes) && array_key_exists('approved', $customerCustomAttributes) && array_key_exists('is_valid', $customerCustomAttributes)){
							if($customerCustomAttributes['is_forwarder']['value'] == 0 && $customerCustomAttributes['approved']['value'] == 1 && $customerCustomAttributes['is_valid']['value'] == 1){
								$customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
								$customerData['addresses'][$key]['custom_attributes'] = $this->filterNotVisibleAttributes(
																						$customerCustomAttributes
																					);
								//$customerData['addresses'][$key]['customer_id']	= '94';												
								if($defaultShippingAddress != "" && $defaultShippingAddress == $customerData['addresses'][$key]['id']){
									$defaultShippingAddress = ['keyVal'=>$key, 'addressVal'=>$customerData['addresses'][$key]];
								}
							}
							else{
								unset($customerData['addresses'][$key]);
							}
						}else{
							unset($customerData['addresses'][$key]);
						}
					}
					else{
						unset($customerData['addresses'][$key]);
					}
				}
			}
			if($defaultShippingAddress && !empty($defaultShippingAddress)){
				$firstAddressVal = $customerData['addresses']['1'];
				$customerData['addresses']['1'] = $defaultShippingAddress['addressVal'];
				$customerData['addresses'][$defaultShippingAddress['keyVal']] = $firstAddressVal;
			}
			//if ($this->customerSession->getCustomerId() != $superUserId) {
				unset($customerData['addresses'][0]);
			//}
			$this->redingtonCheckoutHelper->debugLog('SUCCESS:(DefaultConfigProvider) Shipping address filter with company super user', false);
		} catch (\Exception $e) {
			$this->redingtonCheckoutHelper->debugLog('ERROR:(DefaultConfigProvider) Shipping address filter with company super user: '.$e->getMessage(), false);			
        }
        return $customerData;
    }
	
    /**
     * Set additional customer address data
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    private function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

	/**
     * Filter not visible on storefront custom attributes.
     *
     * @param array $attributes
     * @return array
     */
    private function filterNotVisibleAttributes(array $attributes)
    {
        $attributesMetadata = $this->addressMetadata->getAllAttributesMetadata();
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                unset($attributes[$attributeMetadata->getAttributeCode()]);
            }
        }

        return $this->setLabelsToAttributes($attributes);
    }
	
	/**
     * Set Labels to custom Attributes
     *
     * @param array $customAttributes
     * @return array $customAttributes
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function setLabelsToAttributes(array $customAttributes) : array
    {
        if (!empty($customAttributes)) {
            foreach ($customAttributes as $customAttributeCode => $customAttribute) {
                $attributeOptionLabels = $this->getAttributeLabels($customAttribute, $customAttributeCode);
                if (!empty($attributeOptionLabels)) {
                    $customAttributes[$customAttributeCode]['label'] = implode(', ', $attributeOptionLabels);
                }
            }
        }

        return $customAttributes;
    }
	
	/**
     * Get Labels by CustomAttribute and CustomAttributeCode
     *
     * @param array $customAttribute
     * @param string|integer $customAttributeCode
     * @return array $attributeOptionLabels
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function getAttributeLabels(array $customAttribute, string $customAttributeCode) : array
    {
        $attributeOptionLabels = [];

        if (!empty($customAttribute['value'])) {
            $customAttributeValues = explode(',', $customAttribute['value']);
            $attributeOptions = $this->attributeOptionManager->getItems(
                \Magento\Customer\Model\Indexer\Address\AttributeProvider::ENTITY,
                $customAttributeCode
            );

            if (!empty($attributeOptions)) {
                foreach ($attributeOptions as $attributeOption) {
                    $attributeOptionValue = $attributeOption->getValue();
                    if (in_array($attributeOptionValue, $customAttributeValues)) {
                        $attributeOptionLabels[] = $attributeOption->getLabel() ?? $attributeOptionValue;
                    }
                }
            }
        }

        return $attributeOptionLabels;
    }
	
    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
