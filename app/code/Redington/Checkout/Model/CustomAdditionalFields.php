<?php
/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Model;

use Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface;

/**
 * @method \Redington\Checkout\Model\ResourceModel\CustomAdditionalFields getResource()
 * @method \Redington\Checkout\Model\ResourceModel\CustomAdditionalFields\Collection getCollection()
 */
class CustomAdditionalFields extends \Magento\Framework\Model\AbstractModel implements CustomAdditionalFieldsInterface
{
    protected function _construct()
    {
        $this->_init(\Redington\Checkout\Model\ResourceModel\CustomAdditionalFields::class);
    }
    /**
     * @inheritdoc
     */
    public function getLpoReferenceDocument()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::LPOREFERENCEDOCUMENT);
    }

    /**
     * @inheritdoc
     */
    public function setLpoReferenceDocument($lpoReferenceDocument)
    {
        $this->setData(CustomAdditionalFieldsInterface::LPOREFERENCEDOCUMENT, $lpoReferenceDocument);

        return $this;
    }
	
	/**
     * @inheritdoc
     */
    public function getLponumber()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::LPONUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setLponumber($lponumber)
    {
        $this->setData(CustomAdditionalFieldsInterface::LPONUMBER, $lponumber);

        return $this;
    }

    /** Added by vilas */
    /**
     * @inheritdoc
     */
    public function getPdcDocument()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::PDCDOCUMENT);
    }

    /**
     * @inheritdoc
     */
    public function setPdcDocument($pdcReferencedoc)
    {
        $this->setData(CustomAdditionalFieldsInterface::PDCDOCUMENT, $pdcReferencedoc);

        return $this;
    }
	
    /**
     * @inheritdoc
     */
    public function getCdcDocument()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::CDCDOCUMENT);
    }

    /**
     * @inheritdoc
     */
    public function setCdcDocument($cdcReferencedoc)
    {
        $this->setData(CustomAdditionalFieldsInterface::CDCDOCUMENT, $cdcReferencedoc);

        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getCashDocument()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::CASHDOCUMENT);
    }

    /**
     * @inheritdoc
     */
    public function setCashDocument($cashReferencedoc)
    {
        $this->setData(CustomAdditionalFieldsInterface::CASHDOCUMENT, $cashReferencedoc);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCdcRefNo()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::CDCREFNUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setCdcRefNo($cdcReferenceno)
    {
        $this->setData(CustomAdditionalFieldsInterface::CDCREFNUMBER, $cdcReferenceno);

        return $this;
    }
    /**
     * @inheritdoc
     */
    public function getPdcRefNo()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::PDCREFNUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setPdcRefNo($pdcReferenceno)
    {
        $this->setData(CustomAdditionalFieldsInterface::PDCREFNUMBER, $pdcReferenceno);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCashRefNo()
    {
        return $this->_getData(CustomAdditionalFieldsInterface::CASHREFNUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setCashRefNo($cashReferenceno)
    {
        $this->setData(CustomAdditionalFieldsInterface::CASHREFNUMBER, $cashReferenceno);

        return $this;
    }

}
