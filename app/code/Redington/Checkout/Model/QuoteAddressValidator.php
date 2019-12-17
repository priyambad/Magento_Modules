<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class QuoteAddressValidator extends \Magento\Quote\Model\QuoteAddressValidator
{
    /**
     * Address factory.
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * Customer repository.
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @deprecated 101.1.1 This class is not a part of HTML presentation layer and should not use sessions.
     */
    protected $customerSession;

    /**
     * @var \Redington\Company\Helper\Data
     */
    protected $rednCompanyHelper;

    /**
     * Constructs a quote shipping address validator service object.
     *
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository Customer repository.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Redington\Company\Helper\Data $rednCompanyHelper
     */
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Redington\Company\Helper\Data $rednCompanyHelper
    ) {
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->rednCompanyHelper = $rednCompanyHelper;
    }

    /**
     * Validate address to be used for cart.
     *
     * @param CartInterface $cart
     * @param AddressInterface $address
     * @return void
     * @throws \Magento\Framework\Exception\InputException The specified address belongs to another customer.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified customer ID or address ID is not valid.
     */
    public function validateForCart(CartInterface $cart, AddressInterface $address): void
    {
        // $superUserId = $this->rednCompanyHelper->getCompanyAdminId();
        // $this->validate($address, $cart->getCustomerIsGuest() ? null : $cart->getCustomer()->getId());
    }

    /**
     * Validate address.
     *
     * @param AddressInterface $address
     * @param int|null $customerId Cart belongs to
     * @return void
     * @throws \Magento\Framework\Exception\InputException The specified address belongs to another customer.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified customer ID or address ID is not valid.
     */
    private function doValidate(AddressInterface $address, ?int $customerId): void
    {
        return;
        //validate customer id
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            if (!$customer->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid customer id %1', $customerId)
                );
            }
        }

        if ($address->getCustomerAddressId()) {
            //Existing address cannot belong to a guest
            if (!$customerId) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
            //Validating address ID
            try {
                $this->addressRepository->getById($address->getCustomerAddressId());
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid address id %1', $address->getId())
                );
            }
            //Finding available customer's addresses
            $applicableAddressIds = array_map(function ($address) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                return $address->getId();
            }, $this->customerRepository->getById($customerId)->getAddresses());
            if (!in_array($address->getCustomerAddressId(), $applicableAddressIds)) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
        }
    }

    /**
     * Validates the fields in a specified address data object.
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $addressData The address data object.
     * @return bool
     * @throws \Magento\Framework\Exception\InputException The specified address belongs to another customer.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified customer ID or address ID is not valid.
     */
    /*public function validate(\Magento\Quote\Api\Data\AddressInterface $addressData)
    {
    //validate customer id
    if ($addressData->getCustomerId()) {
    $customer = $this->customerRepository->getById($addressData->getCustomerId());
    if (!$customer->getId()) {
    throw new \Magento\Framework\Exception\NoSuchEntityException(
    __('Invalid customer id %1', $addressData->getCustomerId())
    );
    }
    }

    if ($addressData->getCustomerAddressId()) {
    try {
			$this->addressRepository->getById($addressData->getCustomerAddressId());
		} catch (NoSuchEntityException $e) {
		throw new \Magento\Framework\Exception\NoSuchEntityException(
		__('Invalid address id %1', $addressData->getId())
		);
    }

    $applicableAddressIds = array_map(function ($address) {
		/** @var \Magento\Customer\Api\Data\AddressInterface $address */
		/*   return $address->getId();
			}, $this->customerRepository->getById($addressData->getCustomerId())->getAddresses());
		if (!in_array($addressData->getCustomerAddressId(), $applicableAddressIds)) {
			throw new \Magento\Framework\Exception\NoSuchEntityException(
			__('Invalid customer address id %1', $addressData->getCustomerAddressId())
			);
			}
		}
	return true;
	}*/
}
