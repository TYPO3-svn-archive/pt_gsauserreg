<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Wolfgang Zenker (zenker@punkt.de)
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
 * $Id: class.tx_ptgsauserreg_gsanschAccessor.php,v 1.19 2008/11/20 14:28:50 ry44 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-08-30
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
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-08-30
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_gsanschAccessor extends tx_ptgsasocket_gsaDbAccessor {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsauserreg_gsanschAccessor object) Singleton unique instance
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  object      unique instance of the object (Singleton) 
     * @global     
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-08-30
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            self::$uniqueInstance = new tx_ptgsauserreg_gsanschAccessor;
        }
        return self::$uniqueInstance;
        
    }
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Returns an array with Id of all ANSCH records for given customer
     *
     * @param   integer     UID of the master address record in the GSA database (GSA database field "ADRESSE.NUMMER")
     * @return  array       array with all ANSCH record IDs for this customer
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-14
     */
	public function getAnschIdArr($customerId)
	{
		$anschIdArr = array();

		// check if gsauid is valid; throw exception if not
		$select = 'COUNT(*) AS cnt';
		$from = $this->getTableName('ADRESSE');
		$where = 'NUMMER='.intval($customerId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === FALSE) {
            throw new tx_pttools_exception('Query failed', 2, $this->gsaDbObj->sql_error());
        }
        $row = $this->gsaDbObj->sql_fetch_assoc($res);
        $this->gsaDbObj->sql_free_result($res);
		if ($row['cnt'] != 1) {
            throw new tx_pttools_exception('Customer Id invalid', 2, $customerId);
		}

		// get all gsansch records of this customer from TYPO3 DB
		$select = 'uid, gsa_ansch_id';
		$from = 'tx_ptgsauserreg_gsansch';
		$where = 'gsa_adresse_id='.intval($customerId).' '.
			     tx_pttools_div::enableFields($from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
		$basefound = false;
		$seen = '';
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$anschIdArr[] = $row['uid'];
			if (intval($row['gsa_ansch_id']) != 0) {
				if ($seen != '') {
					$seen .= ', ';
				}
				$seen .= intval($row['gsa_ansch_id']);
			}
			else {
				$basefound = true;
			}
		}
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
		// import base address if not available yet
		if (! $basefound) {
			$anschIdArr[] = $this->importAdresse($customerId);
		}

		// get all ANSCH records of this customer from GSA DB that don't have a corresponding gsansch record yet and create those
		$select = '*';
		$from = $this->getTableName('ANSCH');
		$where = 'KUNDNR='.intval($customerId);
		if ($seen != '') {
			$where .= ' AND NOT NUMMER IN ('.$seen.')';
		}
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if ($res === FALSE) {
            throw new tx_pttools_exception('Query failed', 2, $this->gsaDbObj->sql_error());
        }
        while ($row = $this->gsaDbObj->sql_fetch_assoc($res)) {
			if ($this->charsetConvEnabled == 1) {
				$row = tx_pttools_div::iconvArray($row, $this->gsaCharset, $this->siteCharset);
			}
			$anschIdArr[] = $this->importAnsch($row);
		}
        $this->gsaDbObj->sql_free_result($res);

		trace($anschIdArr);
		return $anschIdArr;
	}

    /**
     * Creates a record in the tx_ptgsauserreg_gsansch table referencing a customers base address. 
	 * As base address data is always read from GSA DB, the created record is mostly empty.
     *
     * @param   integer     UID of the master address record in the GSA database (GSA database field "ADRESSE.NUMMER")
     * @return  integer		uid of TYPO3 DB record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-15
     */
	private function importAdresse($customerId)
	{
		// insert TYPO3 record
        $table           = 'tx_ptgsauserreg_gsansch';
		$ansch = array(
			'gsa_adresse_id' => intval($customerId),
			'gsa_ansch_id' => 0,
		);
		$ansch = tx_pttools_div::expandFieldValuesForQuery($ansch, true, intval(tx_ptgsauserreg_lib::getGsaUserregConfig('feusersSysfolderPid')));
        $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $ansch);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $ansch));
		if ($res) {
			$t3uid = intval($GLOBALS['TYPO3_DB']->sql_insert_id());
		}
        if ($res == false) {
            throw new tx_pttools_exception('importAdresse() failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }

		return $t3uid;
	}

    /**
     * Load address data from customer data records (ADRESSE & DEBITOR)
     *
     * @param   integer		uid of customer (NUMMER in GSA ADRESSE)
     * @return  array		address data to fill in gsansch object
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-17
     */
	private function loadAnschData($customerId)
	{

		// extrakt data from ADRESSE & DEBITOR
        $select  = 'ADR.NUMMER as gsauid, '.
                   'DEB.EGIDENTNR AS euVatID, '.
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
                   'ADR.BUNDESLAND AS state, '.
                   'ADR.LAND AS country, '.  
                   'ADR.TELEFON1 AS phone1, '.  
                   'ADR.DFUE AS mobile1, '.  
                   'ADR.TELEFAX AS fax1, '.  
                   'ADR.EMAIL1 AS email1, '.  
                   'ADR.POST1 AS post1, '.
                   'ADR.POST2 AS post2, '.
                   'ADR.POST3 AS post3, '.
                   'ADR.POST4 AS post4, '.
                   'ADR.POST5 AS post5, '.
                   'ADR.POST6 AS post6, '.
                   'ADR.POST7 AS post7, '.
                   'ADR.POSTMANU AS postmanu, '.
                   'ADR.KONTAKT AS INTERNAL_kontakt';
        $from    = $this->getTableName('ADRESSE').' AS ADR '.
				   'INNER JOIN '.$this->getTableName('DEBITOR').' AS DEB ON ADR.NUMMER = DEB.ADRINR';
        $where   = 'ADR.NUMMER = '.intval($customerId);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
        }
        
        $row = $this->gsaDbObj->sql_fetch_assoc($res);
		if ($this->charsetConvEnabled == 1) {
			$row = tx_pttools_div::iconvArray($row, $this->gsaCharset, $this->siteCharset);
		}
        $this->gsaDbObj->sql_free_result($res);
        
		// mangle data to conform to gsansch record
		$specObj = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance();
		$row['country'] = $specObj->country2ISO($row['country']);
		if ((! $row['firstname'])&& $row['lastname']) {
			// treat as company
			$row['company'] = $row['lastname'];
			$row['lastname'] = '';
			$row = $specObj->extractKontakt($row);
		}
        trace($row); 
		$ansch = array();
		$ansch['gsa_adresse_id'] = $row['gsauid'];
		$ansch['gsa_ansch_id'] = 0;
		$ansch['company'] = $row['company'];
		$ansch['salutation'] = $row['salutation'];
		$ansch['title'] = $row['title'];
		$ansch['firstname'] = $row['firstname'];
		$ansch['lastname'] = $row['lastname'];
		$ansch['streetAndNo'] = $row['streetAndNo'];
		$ansch['addrSupplement'] = $row['addrSupplement'];
		$ansch['zip'] = $row['zip'];
		$ansch['city'] = $row['city'];
		$ansch['poBox'] = $row['poBox'];
		$ansch['poBoxZip'] = $row['poBoxZip'];
		$ansch['poBoxCity'] = $row['poBoxCity'];
		$ansch['state'] = $row['state'];
		$ansch['country'] = $row['country'];
		$ansch['phone1'] = $row['phone1'];
		$ansch['mobile1'] = $row['mobile1'];
		$ansch['fax1'] = $row['fax1'];
		$ansch['email1'] = $row['email1'];
		$ansch['post1'] = $row['post1'];
		$ansch['post2'] = $row['post2'];
		$ansch['post3'] = $row['post3'];
		$ansch['post4'] = $row['post4'];
		$ansch['post5'] = $row['post5'];
		$ansch['post6'] = $row['post6'];
		$ansch['post7'] = $row['post7'];
		$ansch['postmanu'] = $row['postmanu'];
		trace($ansch);

		return $ansch;
	}

    /**
     * Creates preliminary record from the given GSA ANSCH data array in the tx_ptgsauserreg_gsansch table
     *
     * @param   array		data from one GSA ANSCH table record
     * @return  integer		uid of TYPO3 DB record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-16
     */
	private function importAnsch($row)
	{
        trace($row); 
		$ansch = array();
		$ansch['gsa_adresse_id'] = intval($row['KUNDNR']);
		$ansch['gsa_ansch_id'] = intval($row['NUMMER']);
		// ToDo: heuristic to extract probable fields from post 1-7
		trace($ansch);

		// insert TYPO3 record
        $table           = 'tx_ptgsauserreg_gsansch';
		$ansch = tx_pttools_div::expandFieldValuesForQuery($ansch, true, intval(tx_ptgsauserreg_lib::getGsaUserregConfig('feusersSysfolderPid')));
        $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $ansch);
        trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $ansch));
        if ($res == false) {
            throw new tx_pttools_exception('importAdresse() failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
		$t3uid = intval($GLOBALS['TYPO3_DB']->sql_insert_id());

		return $t3uid;
	}


    /**
     * Returns an array with address data ready to fill in a gsansch object.
	 * Data is retrieved from the TYPO3 DB and the relevant GSA DB table (ANSCH or ADRESSE) and mangled to fit our gsansch object
     *
     * @param   integer     uid of record in tx_ptgsauserreg_gsansch table
     * @return  array       associative array with address data
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-08-30
     */
    public function selectAnschData($anschId) {
        
		// fetch record from TYPO3 DB
        $select  =	'uid, '.
					'deprecated, '.
					'gsa_adresse_id AS gsauid, '.
					'gsa_ansch_id AS anschid, '.
					'company, '.
					'salutation, '.
					'title, '.
					'firstname, '.
					'lastname, '.
					'street AS streetAndNo, '.
					'supplement AS addrSupplement, '.
					'zip, '.
					'city, '.
					'pobox AS poBox, '.
					'poboxzip AS poBoxZip, '.
					'poboxcity AS poBoxCity, '.
					'state, '.
					'country';
		$from   = 'tx_ptgsauserreg_gsansch';
		$where  = 'uid='.intval($anschId).' '.
			      tx_pttools_div::enableFields($from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        $dataArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
		if (! (is_array($dataArray) && count($dataArray) > 0)) {
            throw new tx_pttools_exception('Query failed - can not load id <'.$anschId.'>', 1, 'ansch record not found');
		}

		if (intval($dataArray['anschid']) == 0) {
			// load base address data from ADRESSE
			$row = $this->loadAnschData(intval($dataArray['gsauid']));
		}
		else {
			// load address data from ANSCH
			$select  =	'POST1 AS post1, '.
						'POST2 AS post2, '.
						'POST3 AS post3, '.
						'POST4 AS post4, '.
						'POST5 AS post5, '.
						'POST6 AS post6, '.
						'POST7 AS post7, '.
						'TELEFON AS phone1, '.
						'MOBIL AS mobile1, '.
						'FAX AS fax1, '.
						'EMAIL AS email1, '.
						'STANDARD AS gstandard';
			$from    = $this->getTableName('ANSCH');
			$where   = 'NUMMER = '.intval($dataArray['anschid']);
			$groupBy = '';
			$orderBy = '';
			$limit   = '';
	 
			// exec query using TYPO3 DB API
			$res = $this->gsaDbObj->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
			if ($res == false) {
				throw new tx_pttools_exception('Query failed', 1, $this->gsaDbObj->sql_error());
			}
 
			$row = $this->gsaDbObj->sql_fetch_assoc($res);
			if ($this->charsetConvEnabled == 1) {
				$row = tx_pttools_div::iconvArray($row, $this->gsaCharset, $this->siteCharset);
			}
			$this->gsaDbObj->sql_free_result($res);
		}

		foreach ($row AS $key => $value) {
			$dataArray[$key] = $value;
		}

		if (($dataArray['company'] == '') && ($dataArray['lastname'] == '')) {
			$dataArray['postmanu'] = 1;
		}
        
		return $dataArray;
    }
    
    /**
     * Stores gsansch data with values from the given array
     *
     * @param   array	    data from gsansch object
     * @return  integer		uid of tx_ptgsauserreg_gsansch record
     * @throws  tx_pttools_exception   if array points to customer base address
     * @throws  tx_pttools_exception   if the operation fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-17
     */
    public function storeAnschData($dataArray) {

		// check if we can do this
		$uid = intval($dataArray['uid']);
		$anschid = intval($dataArray['anschid']);
		if (($uid != 0) && ($anschid == 0)) {
			// we don't store base address data
            throw new tx_pttools_exception('StoreAnschData() failed', 1, 'Can not update base address');
		}

        // query preparation
        $table           = $this->getTableName('ANSCH');
        $where           = 'NUMMER = '.intval($dataArray['anschid']);
        $updateFieldsArr = array(
			'KUNDNR' => $dataArray['gsauid'],
			'POST1' => $dataArray['post1'],
			'POST2' => $dataArray['post2'],
			'POST3' => $dataArray['post3'],
			'POST4' => $dataArray['post4'],
			'POST5' => $dataArray['post5'],
			'POST6' => $dataArray['post6'],
			'POST7' => $dataArray['post7'],
			'TELEFON' => $dataArray['phone1'],
			'MOBIL' => $dataArray['mobile1'],
			'FAX' => $dataArray['fax1'],
			'EMAIL' => $dataArray['email1'],
			'STANDARD' => intval($dataArray['gstandard']),
		);
		if (($dataArray['company'] != 0) && ($dataArray['lastname'] != 0)) {
			$apartner = $dataArray['salutation'].' '.$dataArray['firstname'];
			$apartner = trim($apartner).' '.$dataArray['lastname'];
			$updateFieldsArr['APARTNER'] = $apartner;
		}
        
		if ($this->charsetConvEnabled == 1) {
			$updateFieldsArr = tx_pttools_div::iconvArray($updateFieldsArr, $this->siteCharset, $this->gsaCharset);
		}
        // exec query using TYPO3 DB API
		if ($anschid == 0) {
			// insert new ANSCH record
			$dataArray['anschid'] = $this->getNextId('ANSCH');
			$updateFieldsArr['NUMMER'] = intval($dataArray['anschid']);
        	$res = $this->gsaDbObj->exec_INSERTquery($table, $updateFieldsArr);
        	if ($res == false) {
            	throw new tx_pttools_exception('Insert '.$table.' failed', 1, $this->gsaDbObj->sql_error());
			}
		}
		else {
			// update existing ANSCH record
        	$res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        	if ($res == false) {
            	throw new tx_pttools_exception('Update '.$table.' failed', 1, $this->gsaDbObj->sql_error());
			}
		}
		if ($dataArray['gstandard'] != 0) {
			// clear gstandard flag on all other ANSCH records of customer
        	$table           = $this->getTableName('ANSCH');
        	$where           = 'NUMMER != '.intval($dataArray['anschid']).
								' AND KUNDNR = '.intval($dataArray['gsauid']);
        	$updateFieldsArr = array(
				'STANDARD'	=> 0,
			);
        	$res = $this->gsaDbObj->exec_UPDATEquery($table, $where, $updateFieldsArr);
        	if ($res == false) {
            	throw new tx_pttools_exception('Update '.$table.' failed', 1, $this->gsaDbObj->sql_error());
			}
		}
        
        // query preparation
        $table           = 'tx_ptgsauserreg_gsansch';
        $where           = 'uid = '.intval($dataArray['uid']);
        $updateFieldsArr = array(
			'deprecated' => $dataArray['deprecated'],
			'gsa_adresse_id' => intval($dataArray['gsauid']),
			'gsa_ansch_id' => intval($dataArray['anschid']),
			'company' => $dataArray['company'],
			'salutation' => $dataArray['salutation'],
			'title' => $dataArray['title'],
			'firstname' => $dataArray['firstname'],
			'lastname' => $dataArray['lastname'],
			'street' => $dataArray['streetAndNo'],
			'supplement' => $dataArray['addrSupplement'],
			'zip' => $dataArray['zip'],
			'city' => $dataArray['city'],
			'pobox' => $dataArray['poBox'],
			'poboxzip' => $dataArray['poBoxZip'],
			'poboxcity' => $dataArray['poBoxCity'],
			'state' => $dataArray['state'],
			'country' => $dataArray['country'],
		);
        
        // exec query using TYPO3 DB API
		if ($uid == 0) {
			// insert new tx_ptgsauserreg_gsansch record
			$updateFieldsArr = tx_pttools_div::expandFieldValuesForQuery($updateFieldsArr, true, intval(tx_ptgsauserreg_lib::getGsaUserregConfig('feusersSysfolderPid')));
        	$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $updateFieldsArr);
        	trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
			if ($res) {
				$uid = intval($GLOBALS['TYPO3_DB']->sql_insert_id());
			}
		}
		else {
			// update existing tx_ptgsauserreg_gsansch record
			$updateFieldsArr = tx_pttools_div::expandFieldValuesForQuery($updateFieldsArr);
        	$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        	trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], $table, $where, $updateFieldsArr));
		}
        if ($res == false) {
            throw new tx_pttools_exception('StoreAnschData() failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($uid); 
        return $uid;
    }

    /**
     * Deletes given object from TYPO3- and GSA-DB
     *
     * @param   array	    data from gsansch object
     * @return  void
     * @throws  tx_pttools_exception   if uid is empty
     * @throws  tx_pttools_exception   if array points to customer base address
     * @throws  tx_pttools_exception   if the operation fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-07-13
     */
    public function deleteAnschData($dataArray) {

		// check if we can do this
		$uid = intval($dataArray['uid']);
		$anschid = intval($dataArray['anschid']);
		if ($uid == 0) {
			// object should not exist in DB
            throw new tx_pttools_exception('deleteAnschData() failed', 1, 'Can not delete non-existant record');
		}
		if ($anschid == 0) {
			// we don't delete base address data
            throw new tx_pttools_exception('deleteAnschData() failed', 1, 'Can not delete base address');
		}

		// delete from TYPO3 DB
        $table           = 'tx_ptgsauserreg_gsansch';
        $where           = 'uid = '.$uid;
        
        $res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
        trace(tx_pttools_div::returnLastBuiltDeleteQuery($GLOBALS['TYPO3_DB'], $table, $where));
        if ($res == false) {
            throw new tx_pttools_exception('deleteAnschData() failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }

        // delete from GSA DB
        $table           = $this->getTableName('ANSCH');
        $where           = 'NUMMER = '.$anschid;
        
        // exec query using TYPO3 DB API
        $res = $this->gsaDbObj->exec_DELETEquery($table, $where);
        if ($res == false) {
           	throw new tx_pttools_exception('Delete '.$table.' failed', 1, $this->gsaDbObj->sql_error());
		}
    }

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsanschAccessor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsanschAccessor.php']);
}

?>
