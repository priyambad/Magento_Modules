<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

/* File: app/code/Atwix/OrderFeedback/Plugin/OrderRepositoryPlugin.php */

namespace Redington\Checkout\Plugin\Checkout;

class OrderSave
{
    /**
     * @var \Redington\Checkout\Model\CustomAdditionalFields
     */
    protected $customAdditionalFields;

    /**
     * @var \Redington\Checkout\Model\AdditionalFields
     */
    protected $additionalFields;

    /**
     * @var \Redington\Checkout\Helper\Data
     */
    protected $redingtonCheckoutHelper;

    /**
     * @param \Redington\Checkout\Model\CustomAdditionalFields $customAdditionalFields
     * @param \Redington\Checkout\Model\AdditionalFields $additionalFields
     * @param \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
     */
    public function __construct(
        \Redington\Checkout\Model\CustomAdditionalFields $customAdditionalFields,
        \Redington\Checkout\Model\AdditionalFields $additionalFields,
        \Redington\Checkout\Helper\Data $redingtonCheckoutHelper
    ) {
        $this->customAdditionalFields = $customAdditionalFields;
        $this->additionalFields = $additionalFields;
        $this->redingtonCheckoutHelper = $redingtonCheckoutHelper;
    }

    /**
     * After save order
     */
    public function afterSave(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderInterface $resultOrder
    ) {
        $resultOrder = $this->saveAdditionalInformation($resultOrder);
        return $resultOrder;
    }

    /**
     * After save order save additional informations
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    private function saveAdditionalInformation(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        try {
            // get JSON post data
            $request_body = file_get_contents('php://input');
            // decode JSON post data into array
            $data = json_decode($request_body, true);

            $lpoReferenceDocument = "";
            $quoteId = $order->getQuoteId();
            $payment = $order->getPayment();
            $method = $payment->getMethodInstance();
            $methodTitle = $method->getTitle();
            if (array_key_exists('extensionAttribute', $data)) {
                $extensionAttributes = $data['extensionAttribute'];
                $lpoReferenceDocument = array_key_exists('lpo_reference_document', $extensionAttributes) ? $extensionAttributes['lpo_reference_document'] : "";
                $lpoReferenceNo = array_key_exists('lpo_reference_no', $extensionAttributes) ? $extensionAttributes['lpo_reference_no'] : "";
                /** Code added by vilas */
                $pdcReferencedoc = array_key_exists('pdc_document', $extensionAttributes) ? $extensionAttributes['pdc_document'] : "";
                $cdcReferencedoc = array_key_exists('cdc_document', $extensionAttributes) ? $extensionAttributes['cdc_document'] : "";
                $pdcReferenceno = array_key_exists('pdc_ref_no', $extensionAttributes) ? $extensionAttributes['pdc_ref_no'] : "";
                $cdcReferenceno = array_key_exists('cdc_ref_no', $extensionAttributes) ? $extensionAttributes['cdc_ref_no'] : "";
                $cashReferencedoc = array_key_exists('cash_document', $extensionAttributes) ? $extensionAttributes['cash_document'] : "";
                $cashReferenceno = array_key_exists('cash_ref_no', $extensionAttributes) ? $extensionAttributes['cash_ref_no'] : "";

                if ($cashReferencedoc != "" && $payment->getMethod() == 'cashpayment') {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setCashDocument($cashReferencedoc);
                        $this->customAdditionalFields->save();
                    }
                }

                if ($cashReferenceno != "" && $payment->getMethod() == 'cashpayment') {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setCashRefNo($cashReferenceno);
                        $this->customAdditionalFields->save();
                    }
                }

                if ($pdcReferenceno != "" && $payment->getMethod() == 'pdc') {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setPdcRefNo($pdcReferenceno);
                        $this->customAdditionalFields->save();
                    }
                }
                if ($cdcReferenceno != "" && $payment->getMethod() == 'cdc') {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setCdcRefNo($cdcReferenceno);
                        $this->customAdditionalFields->save();
                    }
                }
                if ($pdcReferencedoc != "" && $payment->getMethod() == 'pdc') {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setPdcDocument($pdcReferencedoc);
                        $this->customAdditionalFields->save();
                    }
                }
                if ($cdcReferencedoc != "" && $payment->getMethod() == 'cdc') {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setCdcDocument($cdcReferencedoc);
                        $this->customAdditionalFields->save();
                    }
                }
                /** Code ended by vilas */
                if ($lpoReferenceDocument != "" || $lpoReferenceNo != "") {
                    $additinalInfo = $this->additionalFields->findByField($quoteId, 'quote_id');
                    $resultData = $additinalInfo->getData();
                    $additionalId = $resultData['id'];
                    if ($additionalId) {
                        $this->customAdditionalFields->load($additionalId);
                        $this->customAdditionalFields->setLpoReferenceDocument($lpoReferenceDocument);
                        $this->customAdditionalFields->setLponumber($lpoReferenceNo);
                        $this->customAdditionalFields->save();
                    }
                }
                $this->redingtonCheckoutHelper->debugLog(print_r($extensionAttributes, true), false);
                if (array_key_exists("forwarder_address_id", $extensionAttributes)) {
                    $forwarderAddressId = $extensionAttributes['forwarder_address_id'];

                    $this->redingtonCheckoutHelper->debugLog('SUCCESS:(OrderSave) Forwarder Address Id is' . $forwarderAddressId, false);
                    $order->setForwarderAddressId($forwarderAddressId);
                }

                $warehouseCode = $extensionAttributes['warehouseCode'];
                $this->redingtonCheckoutHelper->debugLog('SUCCESS:(OrderSave) warehouse code is' . $warehouseCode, false);
                $this->redingtonCheckoutHelper->debugLog('SUCCESS:(OrderSave) saved warehouse code in order', false);
                $order->setWarehouseCode($warehouseCode);
                $order->save();
            }
        } catch (\Exception $e) {
            $this->redingtonCheckoutHelper->debugLog('ERROR:(OrderSave) During saving additional nformation: ' . $e->getMessage(), false);
        }
        return $order;
    }
}
