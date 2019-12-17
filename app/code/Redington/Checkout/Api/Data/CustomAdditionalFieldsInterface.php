<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Api\Data;

interface CustomAdditionalFieldsInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    const ID = 'id';
    const LPOREFERENCEDOCUMENT = 'lpo_reference_document';
    const LPONUMBER = 'lponumber';
    /** code added by vilas */
    const PDCDOCUMENT = 'pdc_document';
    const CDCDOCUMENT = 'cdc_document';
    const CDCREFNUMBER = 'cdc_ref_no';
    const PDCREFNUMBER = 'pdc_ref_no';
    const CASHDOCUMENT = 'cash_document';
    const CASHREFNUMBER = 'cash_ref_no';
    /**#@-*/

    /**
     * @return string|null
     */
    public function getPdcRefNo();

    /**
     * @param string|null $pdcReferenceno
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setPdcRefNo($pdcReferenceno);

    /**
     * @return string|null
     */
    public function getCashRefNo();

    /**
     * @param string|null $cashReferenceno
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setCashRefNo($cashReferenceno);

    /**
     * @return string|null
     */
    public function getCdcRefNo();

    /**
     * @param string|null $cdcReferenceno
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setCdcRefNo($cdcReferenceno);

    /**
     * @return string|null
     */
    public function getPdcDocument();

    /**
     * @param string|null $pdcReferencedoc
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setPdcDocument($pdcReferencedoc);

    /**
     * @return string|null
     */
    public function getCashDocument();

    /**
     * @param string|null $cashReferencedoc
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setCashDocument($cashReferencedoc);

    /**
     * @return string|null
     */
    public function getCdcDocument();

    /**
     * @param string|null $cdcReferencedoc
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setCdcDocument($cdcReferencedoc);

    /** code ended by vilas */

    /**
     * @return string|null
     */
    public function getLpoReferenceDocument();

    /**
     * @param string|null $lpoReferenceDocument
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setLpoReferenceDocument($lpoReferenceDocument);

    /**
     * @return string|null
     */
    public function getLponumber();

    /**
     * @param string|null $registerDob
     *
     * @return \Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface
     */
    public function setLponumber($lponumber);
}
