<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Model\NegotiableQuote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Webapi\Rest\Request\ParamOverriderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;

/**
 * Replaces a "%cart_id%" value with the current authenticated customer's cart
 */
class ParamOverriderCartId extends \Magento\Quote\Model\Webapi\ParamOverriderCartId
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * Constructs an object to override the cart ID parameter on a request.
     *
     * @param UserContextInterface $userContext
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        UserContextInterface $userContext,
        CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->userContext = $userContext;
        $this->cartManagement = $cartManagement;
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getOverriddenValue()
    {
         $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_NegotiableQuote.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
        try {
            if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
                $customerId = $this->userContext->getUserId();
               $logger->info('customerId'.$customerId);
                /** @var \Magento\Quote\Api\Data\CartInterface */
                 if (strpos($_SERVER['HTTP_REFERER'],'negotiableQuoteId') !== false) {
                    $url = $_SERVER['HTTP_REFERER'];
                     $array = explode("/",$url);
                     $last_item_index = count($array) - 2;
                     $negotiableQuoteId = $array[$last_item_index];
                       
                    $logger->info("negotiableQuoteId".$negotiableQuoteId);
                      return $negotiableQuoteId;
                  }else{
                     $cart = $this->cartManagement->getCartForCustomer($customerId);
                        if ($cart) {
                            return $cart->getId();
                        }
                  }
              
            }
        } catch (NoSuchEntityException $e) {
            $logger->info('Exception'.$e);
            throw new NoSuchEntityException(__('Current customer does not have an active cart.'));
        }
        return null;
    }
}
