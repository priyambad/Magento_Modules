<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Model\Email;

/**
 * Class for sending email notifications.
 */
class Sender extends \Magento\NegotiableQuote\Model\Email\Sender
{
    /**#@+
     * Configuration paths for email templates and identities.
     */
    const XML_PATH_QUOTE_EMAIL_NOTIFICATIONS_ENABLED = 'sales_email/quote/enabled';
    const XML_PATH_QUOTE_EMAIL_NOTIFICATIONS_COPY_METHOD = 'sales_email/quote/copy_method';
    const XML_PATH_QUOTE_EMAIL_NOTIFICATIONS_COPY_TO = 'sales_email/quote/copy_to';
    const XML_PATH_SELLER_NEW_QUOTE_CREATED_BY_BUYER_TEMPLATE = 'sales_email/quote/new_seller_template';
    const XML_PATH_SELLER_QUOTE_UPDATED_BY_BUYER_TEMPLATE = 'sales_email/quote/updated_seller_template';
    const XML_PATH_BUYER_QUOTE_DECLINED_BY_SELLER_TEMPLATE = 'sales_email/quote/declined_buyer_template';
    const XML_PATH_BUYER_QUOTE_UPDATED_BY_SELLER_TEMPLATE = 'sales_email/quote/updated_buyer_template';
    /**#@-*/

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var RecipientFactory
     */
    private $recipientFactory;

    /**
     * @var LinkBuilder
     */
    private $linkBuilder;

    /**
     * @var Provider\SalesRepresentative
     */
    private $salesRepresentativeProvider;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Store scope value in scope config.
     *
     * @var string
     */
    private $storeValueInScopeConfig = 'store';

    /**
     * Frontend area code.
     *
     * @var string
     */
    private $frontendAreaCode = 'frontend';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\NegotiableQuote\Model\Email\RecipientFactory $recipientFactory
     * @param \Magento\NegotiableQuote\Model\Email\LinkBuilder $linkBuilder
     * @param \Magento\NegotiableQuote\Model\Email\Provider\SalesRepresentative $salesRepresentativeProvider
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\NegotiableQuote\Model\Email\RecipientFactory $recipientFactory,
        \Magento\NegotiableQuote\Model\Email\LinkBuilder $linkBuilder,
        \Magento\NegotiableQuote\Model\Email\Provider\SalesRepresentative $salesRepresentativeProvider,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->recipientFactory = $recipientFactory;
        $this->linkBuilder = $linkBuilder;
        $this->salesRepresentativeProvider = $salesRepresentativeProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function sendChangeQuoteEmailToMerchant(\Magento\Quote\Api\Data\CartInterface $quote, $emailTemplate)
    {
        if ($quote && $quote->getCustomer()) {
            try {
                $emailData = $this->recipientFactory->createForQuote($quote);
                if ($emailData->getStoreId()
                    && $this->isEmailNotificationsEnabled($emailData->getStoreId())
                    && $this->salesRepresentativeProvider->getSalesRepresentativeForQuote($quote)) {
                    $merchantUser = $this->salesRepresentativeProvider->getSalesRepresentativeForQuote($quote);
                    $this->sendEmailTemplate(
                        $merchantUser->getEmail(),
                        $merchantUser->getName(),
                        $emailTemplate,
                        $emailData->getCustomerEmail(),
                        $emailData->getCustomerName(),
                        [
                            'data' => $emailData,
                            'merchantName' => $merchantUser->getName(),
                            'quote_url' => $this->linkBuilder->getBackendUrl(
                                'quotes/quote/view',
                                ['quote_id' => $quote->getId()]
                            )
                        ],
                        $emailData->getStoreId()
                    );
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendChangeQuoteEmailToBuyer(
        \Magento\Quote\Api\Data\CartInterface $quote,
        $emailTemplate,
        $comment = ''
    ) {
        if ($quote && $quote->getCustomer()) {
            try {
                $emailData = $this->recipientFactory->createForQuote($quote);
                if ($emailData->getStoreId()
                    && $this->isEmailNotificationsEnabled($emailData->getStoreId())
                    && $this->salesRepresentativeProvider->getSalesRepresentativeForQuote($quote)) {
                    $merchantUser = $this->salesRepresentativeProvider->getSalesRepresentativeForQuote($quote);
                    $templateData = [
                        'data' => $emailData,
                        'merchantName' => $merchantUser->getName()
                    ];
                    $store = $this->storeManager->getStore($emailData->getStoreId());
                    if ($store->getCode()) {
                        $templateData['frontend_quote_url'] = $this->linkBuilder->getFrontendUrl(
                            'negotiable_quote/quote/view',
                            $store->getCode(),
                            $store->getCode(),
                            $quote->getId()
                        );
                    }
                    if ($comment != '') {
                        $templateData['reason'] = $comment;
                    }
                    $this->sendEmailTemplate(
                        $emailData->getCustomerEmail(),
                        $emailData->getCustomerName(),
                        $emailTemplate,
                        $merchantUser->getEmail(),
                        $merchantUser->getName(),
                        $templateData,
                        $emailData->getStoreId()
                    );
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * Checks whether email notifications is enabled.
     *
     * @param int $storeId
     * @return bool
     */
    private function isEmailNotificationsEnabled($storeId)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_QUOTE_EMAIL_NOTIFICATIONS_ENABLED,
            $this->storeValueInScopeConfig,
            $storeId
        );
    }

    /**
     * Send corresponding email template.
     *
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $template configuration path of email template
     * @param string $senderEmail
     * @param string $senderName
     * @param array $templateParams [optional]
     * @param int|null $storeId [optional]
     * @return $this
     */
    private function sendEmailTemplate(
        $recipientEmail,
        $recipientName,
        $template,
        $senderEmail,
        $senderName,
        array $templateParams = [],
        $storeId = null
    ) {
        $templateId = $this->scopeConfig->getValue($template, $this->storeValueInScopeConfig, $storeId);
        $copyMethod = $this->scopeConfig->getValue(
            self::XML_PATH_QUOTE_EMAIL_NOTIFICATIONS_COPY_METHOD,
            $this->storeValueInScopeConfig,
            $storeId
        );
        $emailRecipients = $this->getAllEmailRecipients($recipientName, $recipientEmail, $copyMethod, $storeId);
        $sender = [
			'name' =>  $this->scopeConfig->getValue("trans_email/ident_sales/name", $this->storeValueInScopeConfig),
            'email' => $this->scopeConfig->getValue("trans_email/ident_sales/email", $this->storeValueInScopeConfig),
        ];
        foreach ($emailRecipients as $recipient) {
            $transportBuilder = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => $this->frontendAreaCode, 'store' => $storeId])
                ->setTemplateVars($templateParams)
                ->setFrom($sender)
                ->setReplyTo($senderEmail, $senderName)
                ->addTo($recipient['email'], $recipient['name']);
            if ($copyMethod == self::EMAIL_COPY_METHOD_BCC) {
                $copyEmails = $this->getCopyEmailAddresses($storeId);
                if (!empty($copyEmails)) {
                    foreach ($copyEmails as $copyRecipientEmail) {
                        $transportBuilder->addBcc($copyRecipientEmail);
                    }
                }
            }
			
		
            $transport = $transportBuilder->getTransport();
            $transport->sendMessage();
        }
        return $this;
    }

    /**
     * Get all recipients of notifications.
     *
     * @param string $mainRecipientName
     * @param string $mainRecipientEmail
     * @param string $copyMethod
     * @param int $storeId
     * @return array
     */
    private function getAllEmailRecipients($mainRecipientName, $mainRecipientEmail, $copyMethod, $storeId)
    {
        $recipients = [
            0 => [
                'name' => $mainRecipientName,
                'email' => $mainRecipientEmail
            ]
        ];
        if ($copyMethod == self::EMAIL_COPY_METHOD_SEPARATE_EMAIL) {
            $copyEmails = $this->getCopyEmailAddresses($storeId);
            if (!empty($copyEmails)) {
                foreach ($copyEmails as $copyRecipientEmail) {
                    $recipients[] = [
                        'name' => '',
                        'email' => $copyRecipientEmail
                    ];
                }
            }
        }
        return $recipients;
    }

    /**
     * Return email addresses for sending copy of notifications.
     *
     * @param int $storeId
     * @return array
     */
    private function getCopyEmailAddresses($storeId)
    {
        $emailCopyAddress = $this->scopeConfig->getValue(
            self::XML_PATH_QUOTE_EMAIL_NOTIFICATIONS_COPY_TO,
            $this->storeValueInScopeConfig,
            $storeId
        );
        $emailCopyAddress = trim($emailCopyAddress);
        if ($emailCopyAddress != '') {
            $arrayOfAddresses = explode(',', $emailCopyAddress);
            if (!empty($arrayOfAddresses)) {
                $arrayOfAddresses = array_map('trim', $arrayOfAddresses);
                return $arrayOfAddresses;
            }
        }
        return [];
    }
}
