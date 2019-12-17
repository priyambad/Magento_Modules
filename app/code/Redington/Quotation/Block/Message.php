<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Block;

use Magento\NegotiableQuote\Helper\Quote as QuoteHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

/**
 * Class Message
 *
 * @api
 */
class Message extends \Magento\NegotiableQuote\Block\Quote\Message
{
    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface
     */
    private $restriction;

    /**
     * @param TemplateContext $context
     * @param QuoteHelper $quoteHelper
     * @param \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $restriction
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper,
        \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $restriction,
        array $data = []
    ) {
        parent::__construct($context, $quoteHelper,$restriction,$data);
        $this->quoteHelper = $quoteHelper;
        $this->restriction = $restriction;
        $this->_initMessages();
    }

    /**
     * Get messages for quote.
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set additional message.
     * @param string $message
     * @return void
     */
    public function setAdditionalMessage($message)
    {
        if ($message) {
            $this->messages[] = $message;
        }
    }

    /**
     * Collect all messages for quote.
     * @return void
     */
    protected function _initMessages()
    {
        if ($this->quoteHelper->isLockMessageDisplayed()) {
            $this->messages[] = __(
                'Quote is sent to admin. It will available once released by the admin.'
            );
        }

        if ($this->quoteHelper->isExpiredMessageDisplayed()) {
            $this->messages[] = __(
                'Your quote has expired and the product prices have been updated as per the latest prices in your'
                . ' catalog. You can either re-submit the quote to seller for further negotiation or go to checkout.'
            );
        }

        if (!$this->restriction->isOwner()) {
            $this->messages[] = __(
                'You are not an owner of this quote. You cannot edit it or take any actions on it.'
            );
        }
    }
}
