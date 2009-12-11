<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Wolfgang Zenker (t3extensions@punkt.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Database accessor class for customer data of the 'pt_gsauserreg' extension
 *
 * $Id: class.tx_ptgsauserreg_customerAccessor.php,v 1.52 2008/11/20 14:28:50 ry44 Exp $
 *
 * @author	Wolfgang Zenker <t3extensions@punkt.de>
 * @since   2005-11-14
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_gsaDbAccessor.php'; // parent class for all GSA database accessor classes
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_staticInfoTables.php'; // country-infos
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsaSpecialsAccessor.php'; // GSA specific stuff
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_lib.php';

/**
 *  Database accessor class for addresses (based on GSA database structure)
 *
 * @access      public
 * @author	    Wolfgang Zenker <t3extensions@punkt.de>
 * @since       2005-11-15
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_customerAccessor extends tx_ptgsasocket_gsaDbAccessor {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsauserreg_customerAccessor object) Singleton unique instance
	private $extKey = 'pt_gsauserreg';  // The extension key.
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsauserreg_customerAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-15
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            self::$uniqueInstance = new tx_ptgsauserreg_customerAccessor;                                     
        }
        return self::$uniqueInstance;
        
    }
    
    
    
    /***************************************************************************
     *   PRIVATE SUPPORTING FUNCTIONS
     **************************************************************************/
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * get Array of  IDs for online customers 
     * @param   string  firstname of customer
     * @param   string  lastname of customer
     * @param   string  city of customer
     * @param   string  streetAndNo of customer
     * @param   string  email1 of customer
     * @param   bool	(optional) fetch all and not only online customers
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @return  array   uids of online cutomers 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-12
     */
    
    public function getCustomerIdArr($firstname = '', $lastname = '', $city = '', $streetAndNo = '', $email1 = '', $fetchall = false) {
        trace('[METHOD] '.__METHOD__);
        // prepare query
        $from   = $this->getTableName('ADRESSE');
        $select  = 'NUMMER AS customerId';

		if ($fetchall) {
			$where   = '1';
		} else {
			$where   = 'ONLINEKUNDE=1';
		}
        if ($firstname !='') {
            $where   .= ' AND VORNAME LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($firstname, $from).'%\'';
        }
        if ($lastname !='') {
            $where   .= ' AND NAME LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($lastname,$from).'%\'';
        }
        if ($city !='') {
            $where   .= ' AND ORT LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($city,$from).'%\'';
        }
        if ($streetAndNo !='') {
            $where   .= ' AND STRASSE LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($streetAndNo,$from).'%\'';
        }
        if ($email1 !='') {
            $where   .= ' AND EMAIL1 LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($email1,$from).'%\'';
        }
        $groupBy = '';
        $orderBy = $this->getTableName('ADRESSE').'.NUMMER';
        $limit = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === false) {
            throw new tx_pttools_exception('Query failed for getting customer Id Array ', 1, $this->gsaDbObj->sql_error());
        }
        

        // Build Array from Result
        $idArr = array();

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $idArr[] = $row['customerId'];
        }

        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        trace ($idArr,0,'$idArr');
        return $idArr;
    }


    /**
     * Returns an array with all ONLINEKUNDE from the GSA database/table adresse.
     *
     * @param	boolean		(optional) get suppliers instead of customers
     * @return  array       associative array with all online ONLINEKUNDE from the GSA database
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Ursula Klinger <klinger@punkt.de>
     * @since   2005-11-15
     */
	public function selectGsaAddress($supplier = false)	{
		trace('[METHOD] '.__METHOD__);

		if ($supplier) {
			$select  = 'NUMMER, LIEFNR AS KUNDNR, '.$this->getTableName('ADRESSE').'.MATCH';
			$where   = 'LIEFNR IS NOT NULL';
		}
		else {
			$select  = 'NUMMER, KUNDNR, '.$this->getTableName('ADRESSE').'.MATCH';
			$where   = 'ONLINEKUNDE=1';
		}
        $from    = $this->getTableName('ADRESSE');
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        while($row = $this->gsaDbObj->sql_fetch_assoc($res)){
			if ($this->charsetConvEnabled == 1) {
				$row = tx_pttools_div::iconvArray($row, $this->gsaCharset, $this->siteCharset);
			}
			$a_row[] = $row;
		}
        $this->gsaDbObj->sql_free_result($res);
        
        trace($a_row); 
        return $a_row;
	}

    /**
     * Returns the current cumulated transaction volume for customer
     *
     * @param	integer     ADRESSE.NUMMER of customer
     * @return  double      cumulated transaction Volume (ADRESSE.KUMSATZ)
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2008-08-26
     */
	public function getTransactionVolume($gsauid)	{
		trace('[METHOD] '.__METHOD__);

		$amount = (double) 0;

		$select  = 'KUMSATZ';
		$where   = 'NUMMER = '.intval($gsauid);
        $from    = $this->getTableName('ADRESSE');
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        $row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
        trace($row); 

		$amount = (double) $row['KUMSATZ'];
        return $amount;
	}

    /**
     * Returns ADRESSE.KUNDNR for given ADRESSE.NUMMER
     *
     * @param	integer		ADRESSE.NUMMER
     * @return  string		ADRESSE.KUNDNR
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-14
     */
	private function getKundNr($gsauid)	{
		trace('[METHOD] '.__METHOD__);

		$select  = 'KUNDNR';
		$where   = 'NUMMER = '.intval($gsauid);
        $from    = $this->getTableName('ADRESSE');
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        $row = $this->gsaDbObj->sql_fetch_assoc($res);
		if ($this->charsetConvEnabled == 1) {
			$row = tx_pttools_div::iconvArray($row, $this->gsaCharset, $this->siteCharset);
		}
        $this->gsaDbObj->sql_free_result($res);
        
        trace($row); 
        return $row['KUNDNR'];
	}

    /**
     * SET ADRESSE.LKONTAKT to todays date for given customer
     *
     * @param	integer		ADRESSE.NUMMER
     * @return  void
     * @throws  tx_pttools_exception   if the update fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-26
     */
	public function registerLastContact($gsauid) {
		trace('[METHOD] '.__METHOD__);

		$where   = 'NUMMER = '.intval($gsauid);
        $table    = $this->getTableName('ADRESSE');
		$updateFieldsArr = array(
			'LKONTAKT' => 'CURDATE()',
		);
		$noQuoteFields = array('LKONTAKT');
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFields);
        
        if ($res === false) {
            throw new tx_pttools_exception('Update failed', 1, $this->gsaDbObj->sql_error());
        }
	}

    /**
     * register given transaction volume for customer
     *
     * @param	integer		ADRESSE.NUMMER
     * @param	double		amount to register
     * @return  void
     * @throws  tx_pttools_exception   if the update fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-26
     */
	public function registerTransactionVolume($gsauid, $amount)	{
		trace('[METHOD] '.__METHOD__);

		// make sure amount is a double
		$amount = (double) $amount;

		// updating ADRESSE and KUNDE separately should not be a problem because we make only atomic updates relative to current values. We need to use IFNULL() database function, because DB fields might be NULL

		// update ADRESSE.KUMSATZ
		$where   = 'NUMMER = '.intval($gsauid);
        $table    = $this->getTableName('ADRESSE');
		$updateFieldsArr = array(
			'KUMSATZ' => 'IFNULL(KUMSATZ, 0) + '.$amount,
		);
		$noQuoteFields = array('KUMSATZ');
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFields);
        
        if ($res === false) {
            throw new tx_pttools_exception('Update ADRESSE failed', 1, $this->gsaDbObj->sql_error());
        }

		// update KUNDE.UMSATZ, KUNDE.SALDO and KUNDE.LETZTERUMSATZ
		$where   = 'ADRINR = '.intval($gsauid);
        $table    = $this->getTableName('KUNDE');
		$updateFieldsArr = array(
			'UMSATZ' => 'IFNULL(UMSATZ, 0) + '.$amount,
			'SALDO' => 'IFNULL(SALDO, 0) - '.$amount,
			'LETZTERUMSATZ' => 'NOW()',
		);
		$noQuoteFields = array('UMSATZ','SALDO','LETZTERUMSATZ');
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFields);
        
        if ($res === false) {
            throw new tx_pttools_exception('Update KUNDE failed', 1, $this->gsaDbObj->sql_error());
        }
	}


    /**
     * register given transaction payment for customer
     *
     * @param   integer     (required) ADRESSE.NUMMER
     * @param   double      (required) amount to register
     * @param   double      (optional) discount to register
     * @return  void
     * @throws  tx_pttools_exception   if the update fails
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-29
     */
    public function registerTransactionPayment($gsauid, $amount, $discount=0) {
        trace('[METHOD] '.__METHOD__);

        // make sure amount and discount are doubles
        $amount   = (double) $amount;
        $discount = (double) $discount;

        trace($amount,0,'$amount');
        trace($discount,0,'$discount');

        // update ADRESSE.KUMSATZ
        $where   = 'NUMMER = '.intval($gsauid);
        $table    = $this->getTableName('ADRESSE');
        $updateFieldsArr = array(
            'KUMSATZ' => 'IFNULL(KUMSATZ, 0) - '.$discount,
        );
        $noQuoteFields = array('KUMSATZ');
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFields);
        
        if ($res === false) {
            throw new tx_pttools_exception('Update ADRESSE failed', 1, $this->gsaDbObj->sql_error());
        }


        // update KUNDE.UMSATZ, KUNDE.SALDO and KUNDE.LETZTERUMSATZ
        $where   = 'ADRINR = '.intval($gsauid);
        $table    = $this->getTableName('KUNDE');
        $updateFieldsArr = array(
            'SALDO'  => 'IFNULL(SALDO, 0) + ' . ($amount + $discount),
            'UMSATZ' => 'IFNULL(UMSATZ, 0) - '. $discount,
            'LETZTERUMSATZ' => 'NOW()',
        );
        
        $noQuoteFields = array('UMSATZ','SALDO','LETZTERUMSATZ');
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr, $noQuoteFields);
        
        if ($res === false) {
            throw new tx_pttools_exception('Update KUNDE failed', 1, $this->gsaDbObj->sql_error());
        }
    }

    /**
     * Returns an array with customer data ready to fill in a customer object
	 * Data is retrieved from the relevant GSA DB tables (mostly ADRESSE) and
	 * mangled to fit our customer object
     *
     * Note: The GSA database field name `MATCH` is a reserved (My)SQL word, so it has to be used with backticks or <tablename>.MATCH !
     *
     * @param   integer     UID of the master address record in the GSA database (GSA database field "ADRESSE.NUMMER")
     * @return  array       associative array with customer data (incl. address and payment data)
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-10
     */
    public function selectCustomerData($gsaMasterAddressId) {
		trace('[METHOD] '.__METHOD__);
        
        // query preparation
        $select  = 'ADR.NUMMER as gsauid, '.
                   'DEB.EGIDENTNR AS euVatId, '.
                   'DEB.TAGNETTO AS gsa_tagnetto, '.
                   'KND.KUNDGR AS gsa_kundgr, '.
                   'KND.PRBRUTTO AS gsa_prbrutto, '.
                   'KND.PREISGR AS priceGroup, '.
                   'ADR.ONLINEKUNDE AS isOnlinekunde, '.
                   'ADR.GEBURT AS birthdate, '.
                   'ADR.NAME AS lastname, '.
                   'ADR.VORNAME AS firstname, '.
                   'ADR.ANREDE AS salutation, '.
                   'ADR.TITEL AS title, '.
                   'ADR.STRASSE AS streetAndNo, '.
                   'ADR.ZUSATZ AS addrSupplement, '.
                   'ADR.PLZ AS zip, '.
                   'ADR.ORT AS city, '.
                   'ADR.POSTFACH AS poBox, '.
                   'ADR.PPLZ AS poBoxZip, '.
                   'ADR.ORT AS poBoxCity, '.
                   'ADR.KUNDNR AS gsa_kundnr, '.
                   'ADR.BUNDESLAND AS state, '.
                   'ADR.LAND AS country, '.  
                   'ADR.TELEFON1 AS phone1, '.
                   'ADR.TELEFON2 AS phone2,  '.
                   'ADR.DFUE AS mobile1, '.
                   'ADR.MOBILTELEFON AS mobile2, '.
                   'ADR.TELEFAX AS fax1, '.
                   'ADR.TELEFAX2 AS fax2, '.  
                   'ADR.EMAIL1 AS email1, '.
                   'ADR.EMAIL2 AS email2, '.
                   'ADR.HOMEPAGE AS url, '.
                   'ADR.POSTMANU AS postmanu, '.
                   'ADR.POST1 AS post1, '.
                   'ADR.POST2 AS post2, '.
                   'ADR.POST3 AS post3, '.
                   'ADR.POST4 AS post4, '.
                   'ADR.POST5 AS post5, '.
                   'ADR.POST6 AS post6, '.
                   'ADR.POST7 AS post7, '.
                   'KND.VERSART AS gsa_versart, '.
                   'KND.ZAHLART AS paymentMethod, '.
                   'KND.KREDLIMIT AS gsa_creditLimit, '.
                   'DEB.MAHNART AS gsa_dunningMethod, '.
                   'DEB.MAHNTAGE AS gsa_dunningDays, '.
                   'DEB.MAHNGEB1 AS gsa_dunningCharge1, '.
                   'DEB.MAHNGEB2 AS gsa_dunningCharge2, '.
                   'DEB.MAHNGEB3 AS gsa_dunningCharge3, '.
                   'DEB.LMAHNUNG AS gsa_dunningLastDate, '.
                   'DEB.BANK1 AS bankName, '.
                   'DEB.BLZ1 AS bankCode, '.
                   'DEB.KONTO1 AS bankAccount, '.
                   'DEB.DTAUSER AS bankAccountHolder, '.
                   'DEB.BIC1 AS bankBIC, '.
                   'DEB.IBAN1 AS bankIBAN, '.
                   'ADR.KARTENTYP AS ccType, '.
                   'ADR.KARTENNR AS ccNumber, '.
                   'ADR.VALIDTHRU AS ccExpiry, '.
                   'ADR.KARTENINHABER AS ccHolder, '.
                   'ADR.KONTAKT AS INTERNAL_kontakt';
        $from    = $this->getTableName('ADRESSE').' AS ADR '.
				   'INNER JOIN '.$this->getTableName('DEBITOR').' AS DEB ON ADR.NUMMER = DEB.ADRINR '.
				   'INNER JOIN '.$this->getTableName('KUNDE').' AS KND ON ADR.NUMMER = KND.ADRINR';
        $where   = 'ADR.NUMMER = '.intval($gsaMasterAddressId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
		if ($this->charsetConvEnabled == 1) {
			$a_row = tx_pttools_div::iconvArray($a_row, $this->gsaCharset, $this->siteCharset);
		}
        
		// mangle data to conform to customer object
		$specObj = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance();
		$a_row['country'] = $specObj->country2ISO($a_row['country']);
		if ((! $a_row['firstname'])&& $a_row['lastname']) {
			// treat as company
			$a_row['company'] = $a_row['lastname'];
			$a_row['lastname'] = '';
			$a_row = $specObj->extractKontakt($a_row);
		}
		if ($a_row['birthdate'] == '0000-00-00') {
			$a_row['birthdate'] = '';
		}
		if ($a_row['paymentMethod'] == 'DTA-Buchung/Abbuchung') {
			$a_row['paymentMethod'] = 'DTA-Buchung';
		}
		if ($a_row['gsa_dunningLastDate'] == '0000-00-00') {
			$a_row[''] = '';
		}

        trace($a_row,0,'customerData'); 
        return $a_row;
        
    }
    
    /**
     * Returns outstanding Amount for customer prices are gross according to ZAHLUNG which only incoming payments from Customer  
     * 
     * @param   integer     UID of the master address record in the GSA database (GSA database field "ADRESSE.NUMMER")
     * @return  double      outstanding Amount for this customer in gross 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-13
     */
    public function selectOutstandingAmount($gsaMasterAddressId) {
        /*
        if ($type == 'net') {
            $pricefield = 'ENDPRN';
        } else {
            $pricefield = 'ENDPRB';
        }
        */
        $pricefield = 'ENDPRB';
        $select = 'sum('.$pricefield. ') AS amount, sum(GUTSUMME) as creditMemoAmount, 
                   sum(BEZSUMME) as paymentAmount '; 

        // Invoice
        $where   = 'ADRINR = '.intval($gsaMasterAddressId).
                    ' AND ERFART=\'04RE\' AND AUFTRAGOK=0 AND GEBUCHT=1';
        $from = $this->getTableName('FSCHRIFT');
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        $a_row = $this->gsaDbObj->sql_fetch_assoc($res);
        $invoiceAmount = $a_row['amount'];
        $creditmemoAmount = $a_row['creditMemoAmount'];
        $paymentAmount = $a_row['paymentAmount'];
        
        
        $outstandingAmount = (double)$invoiceAmount - (double)$creditmemoAmount -  (double)$paymentAmount;
        if ($outstandingAmount == NULL) {
            $outstandingAmount = 0;
        }
        return $outstandingAmount;

    }

    /**
     * Creates and initializes ADRESSE, KUNDE & DEBITOR records for a new customer.
     *
     * @param   void
     * @return  int		gsauid of newly created record
     * @throws  tx_pttools_exception   if the create fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-25
     */
    private function createCustomerRecords() {
		trace('[METHOD] '.__METHOD__);
        
        // get extension config for defining customer number ranges for GSA-DB tables KUNDNR / DEBINR
        $minKundNr = tx_ptgsauserreg_lib::getGsaUserregConfig('minKundNr');
        $maxKundNr = tx_ptgsauserreg_lib::getGsaUserregConfig('maxKundNr');
        $virtualTableCustNo = tx_ptgsauserreg_lib::getGsaUserregConfig('virtualTableCustNo');
        
		// get unique ids
		$adrid = $this->getNextId($this->getTableName('ADRESSE'));
		$debid = $this->getNextId($this->getTableName('DEBITOR'));
		$kndid = $this->getNextId($this->getTableName('KUNDE'));

		// get Kundennummer and Debitor using getNextId for virtual table
		$kundnr = $this->getNextId($virtualTableCustNo, $minKundNr);
		if ((($minKundNr >= 0) && ($kundnr < $minKundNr)) || (($maxKundNr > 0) && ($kundnr > $maxKundNr))) {
            throw new tx_pttools_exception('KUNDNR out of range', 2, 'check GSA DB SYNEWNUMBER['.$virtualTableCustNo.']');
		}
        $debinr = intval(tx_ptgsauserreg_lib::getGsaUserregConfig('fixedDebitor'));
		if ($debinr <= 0) {
			// no fixed DEBINR, make it same as KUNDNR
			$debinr = $kundnr;
		}

		// Insert new records in DB
		$insertFieldsArr = array(
			'NUMMER' => $kndid,
			'ADRINR' => $adrid,
			'PRBRUTTO' => 0,
			'PREISGR' => 1,
			'ZAHLART' => '',
			'EURO' => 1,
		);
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($this->getTableName('KUNDE'), $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Insert KUNDE failed', 1, $this->gsaDbObj->sql_error());
        }
        
		$insertFieldsArr = array(
			'NUMMER' => $debid,
			'ADRINR' => $adrid,
			'DEBINR' => $debinr,
			'TAGNETTO' => 5,
			'MAHNART' => 'Mahnlauf',
			'DTAKTO' => 1,
			'EURO' => 1,
		);
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($this->getTableName('DEBITOR'), $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Insert DEBITOR failed', 1, $this->gsaDbObj->sql_error());
        }
        
		$insertFieldsArr = array(
			'NUMMER' => $adrid,
			'KUNDNR' => $kundnr,
			'DEBINR' => $debinr,
		);
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_INSERTquery($this->getTableName('ADRESSE'), $insertFieldsArr);
        if ($res == false) {
            throw new tx_pttools_exception('Insert ADRESSE failed', 1, $this->gsaDbObj->sql_error());
        }
        
		return $adrid;
	}

    
    /**
     * Updates customer data with values from the given array
     *
     * Note: The GSA database field name `MATCH` is a reserved (My)SQL word, so it has to be used with backticks or <tablename>.MATCH !
     *
     * @param   array	    data from customer object
     * @return  void
     * @throws  tx_pttools_exception   if the update fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-20
     */
    private function updateCustomerData($dataArray) {
		trace('[METHOD] '.__METHOD__);

		// verify gsaid and identify related records
		$gsaMasterAddressId = $dataArray['gsauid'];
		$select = 'ADR.NUMMER AS adrid, DEB.NUMMER AS debid, KND.NUMMER as kndid';
        $from    = $this->getTableName('ADRESSE').' AS ADR '.
				   'INNER JOIN '.$this->getTableName('DEBITOR').' AS DEB ON ADR.NUMMER = DEB.ADRINR '.
				   'INNER JOIN '.$this->getTableName('KUNDE').' AS KND ON ADR.NUMMER = KND.ADRINR';
        $where   = 'ADR.NUMMER = '.intval($gsaMasterAddressId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        $uids = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
		trace($uids);
        
        // update KUNDE, DEBITOR and ADRESSE
        $table           = $this->getTableName('KUNDE');
        $where           = 'NUMMER = '.intval($uids['kndid']);
        $updateFieldsArr = array(
			'KUNDGR' => $dataArray['gsa_kundgr'],
			'PRBRUTTO' => intval($dataArray['gsa_prbrutto']),
			'VERSART' => $dataArray['gsa_versart'],
			'PREISGR' => intval($dataArray['priceGroup']),
			'ZAHLART' => $dataArray['paymentMethod'],
            'KREDLIMIT' => floatval($dataArray['gsa_creditLimit']),
		);
		// if enabled, do charset conversion of all non-binary string data
		if ($this->charsetConvEnabled == 1) {
			$updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
		}
		$res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace($res); 
        if ($res == false) {
            throw new tx_pttools_exception('Update KUNDE failed', 1, $this->gsaDbObj->sql_error());
		}

        $table           = $this->getTableName('DEBITOR');
        $where           = 'NUMMER = '.intval($uids['debid']);
        $updateFieldsArr = array(
			'NAME' => $dataArray['lastname'],
			'ORT'  => $dataArray['city'],
			'BANK1'  => $dataArray['bankName'],
			'BLZ1'  => $dataArray['bankCode'],
			'KONTO1'  => $dataArray['bankAccount'],
			'DTAUSER' => $dataArray['bankAccountHolder'],
			'BIC1'  => $dataArray['bankBIC'],
			'IBAN1'  => $dataArray['bankIBAN'],
			'EGIDENTNR'  => $dataArray['euVatId'],
			'AUSLAND'  => $dataArray['isForeigner'] ? 1 : 0,
			'EGAUSLAND'  => $dataArray['isEUForeigner'] ? 1 : 0,
			'TAGNETTO' => intval($dataArray['gsa_tagnetto']),
			'MAHNART' => $dataArray['gsa_dunningMethod'],
			'MAHNTAGE' => intval($dataArray['gsa_dunningDays']),
			'MAHNGEB1' => doubleval($dataArray['gsa_dunningCharge1']),
			'MAHNGEB2' => doubleval($dataArray['gsa_dunningCharge2']),
			'MAHNGEB3' => doubleval($dataArray['gsa_dunningCharge3']),
			'LMAHNUNG' => $dataArray['gsa_dunningLastDate'],
		);
		// if enabled, do charset conversion of all non-binary string data
		if ($this->charsetConvEnabled == 1) {
			$updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
		}
		$res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace($res); 
        if ($res == false) {
            throw new tx_pttools_exception('Update DEBITOR failed', 1, $this->gsaDbObj->sql_error());
		}

        $table           = $this->getTableName('ADRESSE');
        $where           = 'NUMMER = '.intval($uids['adrid']);
        $updateFieldsArr = array(
			$this->getTableName('ADRESSE').'.MATCH' => $dataArray['INTERNAL_match'],
			'ANREDE' => $dataArray['salutation'],
			'TITEL' => $dataArray['title'],
			'NAME' => $dataArray['lastname'],
			'VORNAME' => $dataArray['firstname'],
			'ZUSATZ' => $dataArray['addrSupplement'],
			'KONTAKT' => $dataArray['INTERNAL_kontakt'],
			'STRASSE' => $dataArray['streetAndNo'],
			'POSTFACH' => $dataArray['poBox'],
			'LAND' => $dataArray['country'],
			'PLZ' => $dataArray['zip'],
			'PPLZ' => $dataArray['poBoxZip'],
			'ORT' => $dataArray['city'],
			'TELEFON1' => $dataArray['phone1'],
			'TELEFON2' => $dataArray['phone2'],
			'TELEFAX' => $dataArray['fax1'],
			'TELEFAX2' => $dataArray['fax2'],
			'DFUE' => $dataArray['mobile1'],
			'MOBILTELEFON' => $dataArray['mobile2'],
			'EMAIL1' => $dataArray['email1'],
			'EMAIL2' => $dataArray['email2'],
			'GEBURT' => $dataArray['birthdate'],
			'POST1' => $dataArray['post1'],
			'POST2' => $dataArray['post2'],
			'POST3' => $dataArray['post3'],
			'POST4' => $dataArray['post4'],
			'POST5' => $dataArray['post5'],
			'POST6' => $dataArray['post6'],
			'POST7' => $dataArray['post7'],
			'POSTMANU' => $dataArray['postmanu'],
			'KUNDNR' => $dataArray['gsa_kundnr'],
			'BUNDESLAND' => $dataArray['state'],
			'ONLINEKUNDE' => $dataArray['isOnlinekunde'],
			'HOMEPAGE' => $dataArray['url'],
			'KARTENTYP' => $dataArray['ccType'],
			'KARTENNR' => $dataArray['ccNumber'],
			'VALIDTHRU' => $dataArray['ccExpiry'],
			'KARTENINHABER' => $dataArray['ccHolder'],
		);
		// if enabled, do charset conversion of all non-binary string data
		if ($this->charsetConvEnabled == 1) {
			$updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
		}
		$res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace($res); 
        if ($res == false) {
            throw new tx_pttools_exception('Update ADRESSE failed', 1, $this->gsaDbObj->sql_error());
		}

	}
        
    /**
     * Stores customer data with values from the given array after mangling it to fit the ERPs expectations.
     *
     * @param   array	    data from customer object
     * @return  integer		ID of GSA-DB ADRESSE record
     * @throws  tx_pttools_exception   if the operation fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-20
     */
    public function storeCustomerData($dataArray) {
		trace('[METHOD] '.__METHOD__);

		/* data mangling
			- country code
			- kontakt / name
			- match
			- onlinekunde
		 */
		$dataArray['country'] = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance()->country2GSA($dataArray['country']);
		if ($dataArray['company'] && $dataArray['lastname']) {
			$dataArray['INTERNAL_kontakt'] = '';
			if ($dataArray['salutation']) {
				$dataArray['INTERNAL_kontakt'] .= $dataArray['salutation'] . ' ';
			}
			if ($dataArray['title']) {
				$dataArray['INTERNAL_kontakt'] .= $dataArray['title'] . ' ';
			}
			if ($dataArray['firstname']) {
				$dataArray['INTERNAL_kontakt'] .= $dataArray['firstname'] . ' ';
			}
			$dataArray['INTERNAL_kontakt'] .= $dataArray['lastname'];
			$dataArray['firstname'] = '';
		}
		if ($dataArray['company']) {
			$dataArray['lastname'] = $dataArray['company'];
		}
		$dataArray['INTERNAL_match'] = substr($dataArray['lastname'], 0, 40);
		if ($dataArray['isForeigner']) {
			$dataArray['useGrosPrice'] = 0;
		}
		

		if (intval($dataArray['gsauid']) == 0) {
			$dataArray['gsauid'] = $this->createCustomerRecords();
			$dataArray['gsa_kundnr'] = $this->getKundNr($dataArray['gsauid']);
		}

		// update database records
		$this->updateCustomerData($dataArray);
		return intval($dataArray['gsauid']);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_customerAccessor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_customerAccessor.php']);
}

?>
