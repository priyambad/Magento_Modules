<?php

namespace Redington\Otp\Controller\Index;

use Plumrocket\CartReservation\Helper\Data;
use Plumrocket\CartReservation\Helper\Item;
use Plumrocket\CartReservation\Model\Config\Source\TimerType;

class Resetreservetime extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Plumrocket\CartReservation\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Plumrocket\CartReservation\Helper\Item
     */
    protected $itemHelper;

    /**
     * @var \Plumrocket\CartReservation\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \Plumrocket\CartReservation\Helper\Product
     */
    protected $productHelper;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Plumrocket\CartReservation\Helper\Data $dataHelper
     * @param \Plumrocket\CartReservation\Helper\Item $itemHelper
     * @param \Plumrocket\CartReservation\Helper\Config $configHelper
     * @param \Plumrocket\CartReservation\Helper\Product $productHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $pageFactory, \Magento\Framework\Controller\Result\JsonFactory $jsonFactory, \Magento\Customer\Model\Session $customerSession, \Magento\Checkout\Model\Session $checkoutSession, \Plumrocket\CartReservation\Helper\Data $dataHelper, \Plumrocket\CartReservation\Helper\Item $itemHelper, \Plumrocket\CartReservation\Helper\Config $configHelper, \Plumrocket\CartReservation\Helper\Product $productHelper
    ) {
        $this->pageFactory = $pageFactory;
        $this->resultJsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        $this->itemHelper = $itemHelper;
        $this->configHelper = $configHelper;
        $this->productHelper = $productHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            $response = [
                'error' => true,
                'message' => 'Not Post',
            ];
            return $resultJson->setData($response);
        }

        if (!$this->dataHelper->moduleEnabled()) {
            return;
        }

        try {
            if ($this->customerSession->isLoggedIn()) {
                $this->itemHelper->switchMode(\Plumrocket\CartReservation\Helper\Data::TIMER_MODE_CART);
				$expireAt = '';
                $items = $this->itemHelper->getQuoteItems();
                foreach ($items as $item) {
                    if ($this->configHelper->getCartReservationType() == TimerType::TYPE_SEPARATE) {
                        // Item can change id after merge so need to update saved timer code.
                        $item->removeOption('additional_options')->saveItemOptions();
                    }

                    //if ($this->itemHelper->getReservationStatus($item) == Item::RESERVATION_GUEST_DISABLED) {
                    //                        if ($item->getData('prcr_guest_time_updated')) {
                    //                            continue;
                    //                        }

                    $productId = $item->getProductId();
                    $items = $this->productHelper->getReservations($productId, $item->getQuoteId());

//                        if (isset($items[$productId]) && $items[$productId]['reserved_qty'] + $item->getQty() > $items[$productId]['max_qty']
                    //                        ) {
                    //                            continue;
                    //                        }

                    $expireAt = $this->dataHelper->getExpireAt(
                        $this->configHelper->getCartTime()
                    );

                    $this->itemHelper->updateItem($item, [
                        'timer_expire_at' => $expireAt,
                        'original_cart_expire_at' => $expireAt,
                        'prcr_guest_time_updated' => true,
                    ]);
                    //}
                }

                if ($this->configHelper->getCartReservationType() == TimerType::TYPE_GLOBAL) {
                    $this->itemHelper->updateGlobalTimer(
                        $this->itemHelper->getQuoteId()
                    );
                }

                $returnStatus = 'SUCCESS';
                $response = [
					'original_cart_expire_at'=>$expireAt,
                    'success' => true,
                    'message' => 'cart reservation time reset successfully',
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please login..',
                    'is_display_message' => true,
                ];
            }
            return $resultJson->setData($response);
        } catch (\Exception $e) {
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
                'is_display_message' => false,
            ];
            return $resultJson->setData($response);
        }
    }

}
