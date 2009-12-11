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
 * Database accessor class for order wrappers of the 'pt_gsashop' extension
 *
 * $Id: class.tx_ptgsauserreg_userAccessor.php,v 1.20 2008/11/20 14:28:50 ry44 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-05-08
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php'; 
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iSingleton.php'; // abstract class for Singleton design pattern
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';  // helper class for country specific stuff
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_lib.php';


/**
 *  Database accessor class for frontend user accounts
 *
 * @author	    WOlfgang Zenker <zenker@punkt.de>
 * @since       2006-05-08
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_userAccessor implements tx_pttools_iSingleton {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsauserreg_userAccessor object) Singleton unique instance
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
 
    /**
     * Private class constructor: must not be called directly in order to use getInstance() to get the unique instance of the object.
     *
     * @param   void
     * @return  void
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-06-23
     */
    private function __construct() {

        trace('***** Creating new '.__CLASS__.' object. *****');

    }

	/**
     * Final method to prevent object cloning (using 'clone') of the inheriting class, in order to use only the singleton unique instance of the object.
     * @param   void
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-05-24
     */
    public final function __clone() {

        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }

    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  tx_ptgsauserreg_userAccessor      unique instance of the object (Singleton) 
     * @global     
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-08
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            self::$uniqueInstance = new tx_ptgsauserreg_userAccessor;
        }
        return self::$uniqueInstance;
        
    }
    
    
    /***************************************************************************
     *   TYPO3 DB RELATED METHODS
     **************************************************************************/
 
    /**
     * get Array of feUser IDs for customer
     * @param   integer GSA-DB ADRESSE.NUMMER of customer
     * @param   boolean (optional) fetch ALL customer ids (only if gsauid = 0)
     * @return  array	array of fe_user uids for this customer
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-01
     */
    
    public function getUserIdArr($gsauid, $fetchall = false) {
        trace('[CMD] '.__METHOD__);

		if ($gsauid != 0) {
			$fetchall = false;
		}

		// prepare query
		$select = 'uid';
		$from	= 'fe_users';
		if ($fetchall) {
			$where	= '1';
		} else {
			$where	= 'tx_ptgsauserreg_gsa_adresse_id = '.intval($gsauid);
		}
		$where   .= tx_pttools_div::enableFields($from);
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1);
        }

        // Build Array from Result
        $userIdArr = array();
    
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $userIdArr[] = $row['uid'];
        }

        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($userIdArr);
        return $userIdArr;
    }
 
    /**
     * get Array of feUser IDs matching the given search criteria
     * @param   array	array of propertyName => value pairs with search words
     * @param	boolean	(optional) match search string exactly instead of wildcard
     * @return  array	array of fe_user uids for this search criteria
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-07-25
     */
    
    public function searchUserIdArr($searchlist, $exactMatch = false) {
        trace('[CMD] '.__METHOD__);

		// prepare query
		$select = 'uid';
		$from	= 'fe_users';
		$where	= '1';	// start with "true", so we can always add "AND"
		foreach ($searchlist as $propname => $value) {
			$fieldname = $propname;
			$fieldtype = 'string';
			switch ($propname) {
				case 'feuid':
					$fieldname = 'uid';
					$fieldtype = 'int';
					break;
				case 'pid':
					$fieldtype = 'int';
					break;
				case 'gsauid':
					$fieldname = 'tx_ptgsauserreg_gsa_adresse_id';
					$fieldtype = 'int';
					break;
				case 'isPrivileged':
					$fieldname = 'tx_ptgsauserreg_isprivileged';
					$fieldtype = 'bool';
					break;
				case 'isRestricted':
					$fieldname = 'tx_ptgsauserreg_isrestricted';
					$fieldtype = 'bool';
					break;
				case 'firstname':
					$fieldname = 'tx_ptgsauserreg_firstname';
					break;
				case 'lastname':
					$fieldname = 'tx_ptgsauserreg_lastname';
					break;
				case 'salutation':
					$fieldname = 'tx_ptgsauserreg_salutation';
					break;
				case 'streetAndNo':
					$fieldname = 'address';
					break;
				case 'state':
					$fieldname = 'tx_ptgsauserreg_state';
					break;
				case 'country':
					$fieldname = 'tx_ptgsauserreg_country';
					break;
				case 'phone1':
					$fieldname = 'telephone';
					break;
				case 'mobile1':
					$fieldname = 'tx_ptgsauserreg_mobile';
					break;
				case 'fax1':
					$fieldname = 'fax';
					break;
				case 'email1':
					$fieldname = 'email';
					break;
				case 'url':
					$fieldname = 'www';
					break;
				case 'department':
					$fieldname = 'tx_ptgsauserreg_department';
					break;
				case 'defBillAddr':
					$fieldname = 'tx_ptgsauserreg_defbilladdr_uid';
					break;
				case 'defShipAddr':
					$fieldname = 'tx_ptgsauserreg_defshipaddr_uid';
					break;
			}
			switch ($fieldtype) {
				case 'int':
				case 'bool':
					$where .= ' AND '.$fieldname.' = '.intval($value);
					break;
				case 'string':
					if ($exactMatch) {
						$where .= ' AND '.$fieldname.' = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($value, $from);
					}
					else {
						$where .= ' AND '.$fieldname.' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($value, $from).'%\'';
					}
			}
		}
		$where   .= tx_pttools_div::enableFields($from);
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';
        
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1);
        }

        // Build Array from Result
        $userIdArr = array();
    
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $userIdArr[] = $row['uid'];
        }

        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        trace($userIdArr);
        return $userIdArr;
    }
 
    /**
     * Returns data of an user record (specified by UID) from the TYPO3 database
     *
     * @param   integer     UID of the user record in the TYPO3 database
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  array       data of the specified user record
     * @throws  tx_pttools_exception   if the query fails/returns false
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-08
     */
    public function selectUserData($uid) {
		trace('[CMD] '.__METHOD__);
        
        // query preparation
        $select  = 'uid AS feuid, '.
				   'pid, '.
				   'tx_ptgsauserreg_gsa_adresse_id AS gsauid, '.
				   'tx_ptgsauserreg_isprivileged AS isPrivileged, '.
				   'tx_ptgsauserreg_isrestricted AS isRestricted, '.
				   'username, '.
				   'password, '.
				   'usergroup, '.
				   'tx_ptgsauserreg_firstname AS firstname, '.
				   'tx_ptgsauserreg_lastname AS lastname, '.
				   'tx_ptgsauserreg_salutation AS salutation, '.
				   'title, '.
				   'address AS streetAndNo, '.
				   'zip, '.
				   'city, '.
				   'tx_ptgsauserreg_state AS state, '.
				   'tx_ptgsauserreg_country AS country, '.
				   'telephone AS phone1, '.
				   'tx_ptgsauserreg_mobile AS mobile1, '.
				   'fax AS fax1, '.
				   'email AS email1, '.
				   'www AS url, '.
				   'company, '.
                   'tx_ptgsauserreg_department AS department, '.
                   'tx_ptgsauserreg_defbilladdr_uid AS defBillAddr, '.
                   'tx_ptgsauserreg_defshipaddr_uid AS defShipAddr';
        $from    = 'fe_users';
        $where   = 'uid = '.intval($uid).' '.
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
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        trace($a_row); 
        return $a_row;
        
    }

    /**
     * Returns uid of user identified by username, 0 if not found
     *
     * @param   string		username
     * @global  object      $GLOBALS['TYPO3_DB']: t3lib_db Object (TYPO3 DB API)
     * @return  integer		uid of feuser (0 if not found)
     * @throws  tx_pttools_exception   if the query fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-03-07
     */
    public function getUidByUsername($username) {
		trace('[CMD] '.__METHOD__);
        
		$uid = 0;

        // query preparation
        $select  = 'uid';
        $from    = 'fe_users';
        $where   = 'username = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($username, $from).' '.
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
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
		if ($a_row) {
			$uid = intval($a_row['uid']);
		}
        
		trace($uid);
        return $uid;
        
    }

    /**
     * Stores frontend user data with values from the given array
     *
     * @param   array	    data from user object
     * @return  integer		uid of fe_user record
     * @global  object      $GLOBALS['TSFE']: TYPO3 FrontEnd config (language)
     * @throws  tx_pttools_exception   if the operation fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-08
     */
    public function storeUserData($dataArray) {
    
    	tx_pttools_assert::isInstanceOf($GLOBALS['TSFE'], 'tslib_fe', array('message' => 'No TSFE found!'));
    
		trace('[CMD] '.__METHOD__);

        // query preparation
		$uid = intval($dataArray['feuid']);
        $table           = 'fe_users';
        $where           = 'uid = '.$uid;
        $updateFieldsArr = array(
			'pid' => $dataArray['pid'],
			'tx_ptgsauserreg_gsa_adresse_id' => intval($dataArray['gsauid']),
			'tx_ptgsauserreg_isprivileged' => $dataArray['isPrivileged'],
			'tx_ptgsauserreg_isrestricted' => $dataArray['isRestricted'],
			'username' => $dataArray['username'],
			'password' => $dataArray['password'],
			'usergroup' => $dataArray['usergroup'],
			'tx_ptgsauserreg_firstname' => $dataArray['firstname'],
			'tx_ptgsauserreg_lastname' => $dataArray['lastname'],
			'tx_ptgsauserreg_salutation' => $dataArray['salutation'],
			'title' => $dataArray['title'],
			'address' => $dataArray['streetAndNo'],
			'zip' => $dataArray['zip'],
			'city' => $dataArray['city'],
			'city' => $dataArray['city'],
			'tx_ptgsauserreg_state' => $dataArray['state'],
			'tx_ptgsauserreg_country' => $dataArray['country'],
			'telephone' => $dataArray['phone1'],
			'tx_ptgsauserreg_mobile' => $dataArray['mobile1'],
			'fax' => $dataArray['fax1'],
			'email' => $dataArray['email1'],
			'www' => $dataArray['url'],
			'company' => $dataArray['company'],
			'tx_ptgsauserreg_department' => $dataArray['department'],
			'tx_ptgsauserreg_defbilladdr_uid' => intval($dataArray['defBillAddr']),
			'tx_ptgsauserreg_defshipaddr_uid' => intval($dataArray['defShipAddr']),
			'name' => trim($dataArray['firstname'].' '.$dataArray['lastname']),
			'country' => tx_ptgsauserreg_countrySpecifics::getCountryName($dataArray['country'], is_array($GLOBALS['TSFE']->config['config']) ? $GLOBALS['TSFE']->config['config']['language'] : ''),
		);
        
        // exec query using TYPO3 DB API
		if ($uid == 0) {
			// insert new fe_user record
        	if ($GLOBALS['TSFE']->loginUser == 1) {
				$updateFieldsArr['fe_cruser_id'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
			}
			$updateFieldsArr = tx_pttools_div::expandFieldValuesForQuery($updateFieldsArr, true);
        	$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $updateFieldsArr);
        	trace(tx_pttools_div::returnLastBuiltInsertQuery($GLOBALS['TYPO3_DB'], $table, $updateFieldsArr));
			if ($res) {
				$uid = intval($GLOBALS['TYPO3_DB']->sql_insert_id());
			}
		}
		else {
			// update existing fe_user record
			$updateFieldsArr = tx_pttools_div::expandFieldValuesForQuery($updateFieldsArr);
        	$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        	trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], $table, $where, $updateFieldsArr));
		}
        if ($res == false) {
            throw new tx_pttools_exception('StoreUserData() failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
        
        trace($uid); 
        return $uid;
        
    }
 
    /**
     * Marks frontend user with given uid as deleted
     *
     * @param   integer		uid of fe_user record
     * @return  void
     * @throws  tx_pttools_exception   if the operation fails
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-01
     */
    public function deleteUserData($feuid) {
		trace('[CMD] '.__METHOD__);

        // query preparation
		$uid = intval($feuid);
        trace($uid); 
        $table           = 'fe_users';
        $where           = 'uid = '.$uid;
        $updateFieldsArr = array(
			'deleted' => '1',
		);
        
        // exec query using TYPO3 DB API
		$updateFieldsArr = tx_pttools_div::expandFieldValuesForQuery($updateFieldsArr);
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        trace(tx_pttools_div::returnLastBuiltUpdateQuery($GLOBALS['TYPO3_DB'], $table, $where, $updateFieldsArr));
        if ($res == false) {
            throw new tx_pttools_exception('deleteUserData() failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }
    }
 
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_userAccessor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_userAccessor.php']);
}

?>
