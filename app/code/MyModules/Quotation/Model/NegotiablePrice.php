<?php
 

namespace Redington\Quotation\Model;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote as NegotiableQuoteResource;

/**
 * Validator for changing negotiable quote prices.
 */
class NegotiablePrice extends \Magento\NegotiableQuote\Model\Validator\NegotiablePrice
{
    /**
     * @var NegotiableQuoteInterfaceFactory
     */
    private $negotiableQuoteFactory;

    /**
     * @var NegotiableQuoteResource
     */
    private $negotiableQuoteResource;

    /**
     * @var \Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory
     */
    private $validatorResultFactory;

    /**
     * Minimal available percentage discount for negotiable quote.
     *
     * @var float
     */
    private $minPercentageDiscount = 0.01;

    /**
     * Maximal available percentage discount for negotiable quote.
     *
     * @var int
     */
    private $maxPercentageDiscount = 100;

    /**
     * @param NegotiableQuoteInterfaceFactory $negotiableQuoteFactory
     * @param NegotiableQuoteResource $negotiableQuoteResource
     * @param \Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory $validatorResultFactory
     */
    public function __construct(
        NegotiableQuoteInterfaceFactory $negotiableQuoteFactory,
        NegotiableQuoteResource $negotiableQuoteResource,
        \Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory $validatorResultFactory
    ) {
        $this->negotiableQuoteFactory = $negotiableQuoteFactory;
        $this->negotiableQuoteResource = $negotiableQuoteResource;
        $this->validatorResultFactory = $validatorResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data)
    {
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_Negotiableprice.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
				$logger->info('In custom Negotiableprice');
        $result = $this->validatorResultFactory->create();
        $negotiableQuote = $this->retrieveNegotiableQuote($data);
        if (empty($negotiableQuote)) {
            return $result;
        }
        $oldQuote = $this->negotiableQuoteFactory->create();
        $this->negotiableQuoteResource->load($oldQuote, $negotiableQuote->getQuoteId());

        $type = $this->retrieveNegotiableQuoteData(
            NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE,
            $negotiableQuote,
            $oldQuote
        );
        $value = $this->retrieveNegotiableQuoteData(
            NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE,
            $negotiableQuote,
            $oldQuote
        );
        if ($type === $oldQuote->getNegotiatedPriceType()
            && $value === $oldQuote->getNegotiatedPriceValue()
        ) {
            return $result;
        }
        $quoteOriginalPrice = $this->retrieveNegotiableQuoteData(
            NegotiableQuoteInterface::BASE_ORIGINAL_TOTAL_PRICE,
            $negotiableQuote,
            $oldQuote
        );

        $types = [
            NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT,
            NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_AMOUNT_DISCOUNT,
            NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL,
        ];

        $requiredFieldFailed = $this->retrieveFailedRequiredField($type, $value, $negotiableQuote);
        /*if (!empty($requiredFieldFailed)) {
            $result->addMessage(
                __(
                    '"%fieldName" is required. Enter and try again.',
                    ['fieldName' => $requiredFieldFailed]
                )
            );
            return $result;
        }
        if (!empty($type) && !in_array($type, $types)) {
            $result->addMessage(
                __(
                    'Invalid attribute value. Row ID: %fieldName = %fieldValue',
                    [
                        'fieldName' => NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE,
                        'fieldValue' => $type
                    ]
                )
            );
            return $result;
        }*/

        return $this->checkPriceValueByType($type, $value, $quoteOriginalPrice);
    }

    /**
     * Check required fields and return field name if it isn't exist.
     *
     * @param int $type
     * @param float $value
     * @param NegotiableQuote $negotiableQuote
     * @return string
     */
    private function retrieveFailedRequiredField($type, $value, NegotiableQuote $negotiableQuote)
    {
        if (!isset($type) && isset($value)) {
            return NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE;
        }
        if ($negotiableQuote->getNegotiatedPriceType()
            && !$negotiableQuote->hasData(NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE)
        ) {
            return NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE;
        }

        return '';
    }

    /**
     * Retrieve data from negotiable quote or old negotiable quote if this field doesn't exist.
     *
     * @param string $key
     * @param NegotiableQuote $negotiableQuote
     * @param NegotiableQuote $oldQuote
     * @return mixed
     */
    private function retrieveNegotiableQuoteData(
        $key,
        NegotiableQuote $negotiableQuote,
        NegotiableQuote $oldQuote
    ) {
        return $negotiableQuote->hasData($key)
            ? $negotiableQuote->getData($key)
            : $oldQuote->getData($key);
    }

    /**
     * Validate price value for negotiable quote by price type.
     * Check percent is between 0.01 and 100 or amount discount doesn't more than quote subtotal.
     *
     * @param int $type
     * @param float $value
     * @param float $quoteOriginalPrice
     * @return ValidatorResult
     */
    private function checkPriceValueByType(
        $type,
        $value,
        $quoteOriginalPrice
    ) {
        $result = $this->validatorResultFactory->create();
        if ($value === null) {
            return $result;
        }
       /* if ($value < 0 || !is_numeric($value)) {
            $result->addMessage(
                __(
                    'Invalid attribute value. The price must be NULL, 0 or a positive number. '
                    . 'Row ID: %fieldName = %fieldValue',
                    [
                        'fieldName' => NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE,
                        'fieldValue' => $value
                    ]
                )
            );
            return $result;
        }

        if (($type == NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT
                && ($value < $this->minPercentageDiscount || $value > $this->maxPercentageDiscount))
            || ($type != NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT
                && $value > $quoteOriginalPrice)
        ) {
            $result->addMessage(
                __(
                    'Invalid attribute value. Row ID: %fieldName = %fieldValue',
                    [
                        'fieldName' => NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE,
                        'fieldValue' => $value
                    ]
                )
            );
        }*/

        return $result;
    }

    /**
     * Retrieve negotiable quote from $data.
     *
     * @param array $data
     * @return NegotiableQuote
     */
    private function retrieveNegotiableQuote(array $data)
    {
        $negotiableQuote = !empty($data['negotiableQuote']) ? $data['negotiableQuote'] : null;
        if (!$negotiableQuote && !empty($data['quote']) && $data['quote']->getExtensionAttributes()
            && $data['quote']->getExtensionAttributes()->getNegotiableQuote()
            && $data['quote']->getExtensionAttributes()->getNegotiableQuote()->getIsRegularQuote()
        ) {
            $negotiableQuote = $data['quote']->getExtensionAttributes()->getNegotiableQuote();
        }

        return $negotiableQuote;
    }
}
