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
 * Class for user objects in the GSA (General Shop Application) framework
 *
 * $Id: class.tx_ptgsauserreg_user.php,v 1.24 2009/08/24 06:03:27 ry25 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-05-04
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_userAccessor.php';  // extension specific database accessor class for customer data
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper functions
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_address.php'; // general address helper class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_lib.php';

/**
 *  Class for user objects
 *
 * @access      public
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-05-04
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_user extends tx_pttools_address {
    
	/**
	 * Constants
	 */
	const EXTKEY = 'pt_gsauserreg';

    /**
     * Properties
     */

    protected $feuid = 0;         // ID of fe_user record
	protected $pid = 0;			// page id of user storage sysfolder
    protected $gsauid = 0;        // ID of customer database record (GSA-DB: ADRESSE.NUMMER)
    protected $isPrivileged = 0;	// user has special privileges
    protected $isRestricted = 0;	// user has restricted rights
	protected $username = '';		// login name
	protected $password = '';		// login password
	protected $usergroup = '';	// uid of fe group(s) user is member of
    protected $defBillAddr = 0;	// uid of default Billing Address (0 = use customer default)
    protected $defShipAddr = 0;	// uid of default Shipping Address (0 = use customer default)
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) uid of the fe_user record. Set to 0 if you want to use the 2nd param.
     * @param   array       Array containing address data to set as address object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class. This param has no effect if the 1st param is set to something other than 0.
     * @return	void   
     * @throws  tx_pttools_exception   if the first param is not numeric  
     * @author  Wolfgang Zenker <t3extensions@punkt.de>
     * @since   2006-05-04
     */
	public function __construct($userId=0, $userDataArr=array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($userId)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if a user record ID is given, retrieve user array from database accessor (and overwrite 2nd param)
        if ($userId > 0) {
            $userDataArr = tx_ptgsauserreg_userAccessor::getInstance()->selectUserData($userId);
        }
        
        $this->setUserFromGivenArray($userDataArr);
           
        trace($this);
        
    }
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the user properties using data given by param array
     *
     * @param   array     Array containing user data to set as user object's properties; array keys have to be named exactly like the properties of this class and it's parent class.
     * @return  void        
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-04
     */
    protected function setUserFromGivenArray($userDataArr) {
        
		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($userDataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($userDataArr[$propertyname]);
			}
		}
    }
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-04
     */
    protected function getDataArray() {

		$dataArray = array();

		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			$getter = 'get_'.$propertyname;
			$dataArray[$propertyname] = $this->$getter();
		}

		return $dataArray;
	}
        
    /**
     * Stores current user data in TYPO3 DB
	 * if feuid is non-zero, these records are updated;
	 * otherwise new records are created
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-04
     */
    public function storeSelf() {
		trace('[CMD] '.__METHOD__);

		$dataArray = $this->getDataArray();
        $this->feuid = tx_ptgsauserreg_userAccessor::getInstance()->storeUserData($dataArray);
	}

    /**
     * Marks current user as deleted in TYPO3 DB
	 * if feuid is zero, nothing is done
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-01
     */
    public function deleteSelf() {
		trace('[CMD] '.__METHOD__);

		$feuid = $this->get_feuid();
		if ($feuid) {
        	tx_ptgsauserreg_userAccessor::getInstance()->deleteUserData($feuid);
		}
	}

    /**
     * check if username is ok for the current feuid
	 *
     * @param   void        
     * @return  boolean		username property is ok for current feuid
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-03-07
     */
    public function usernameOk() {
		trace('[CMD] '.__METHOD__);

		$result = false;
		if ($this->username) {
			$feuid = tx_ptgsauserreg_userAccessor::getInstance()->getUidByUsername($this->username);
			if (($feuid == 0) || ($feuid == $this->feuid)) {
				$result = true;
			}
		}
		return $result;
	}

    /**
     * add given group id to usergroup list
	 *
     * @param   integer		fe_group uid
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-01
     */
    public function addGroupId($groupUid) {
		trace('[CMD] '.__METHOD__);

		if (! t3lib_div::inList($this->usergroup, intval($groupUid))) {
			$this->usergroup .= ','.intval($groupUid);
			/* ToDo: find out how to make this effective immediately
			if ((TYPO3_MODE == 'FE') && ($GLOBALS['TSFE']->loginUser == 1) && ($GLOBALS['TSFE']->fe_user->user['uid'] == $this->feuid)) {
				// we are modifying our own user, so modify TSFE as well
				$GLOBALS['TSFE']->fe_user->user['usergroup'] = $this->usergroup;
			}
			/* */
		}
	}

    /**
     * delete given group id from usergroup list
	 *
     * @param   integer		fe_group uid
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-01
     */
    public function delGroupId($groupUid) {
		trace('[CMD] '.__METHOD__);

		$this->usergroup = t3lib_div::rmFromList(intval($groupUid), $this->usergroup);
		/* ToDo: find out how to make this effective immediately
		if ((TYPO3_MODE == 'FE') && ($GLOBALS['TSFE']->loginUser == 1) && ($GLOBALS['TSFE']->fe_user->user['uid'] == $this->feuid)) {
			// we are modifying our own user, so modify TSFE as well
			$GLOBALS['TSFE']->fe_user->user['usergroup'] = $this->usergroup;
		}
		/* */
	}

    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-05-04
     */
    public function get_feuid() {
        return $this->feuid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-05-10
     */
    public function get_pid() {
        return $this->pid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-05-04
     */
    public function get_gsauid() {
        return $this->gsauid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-05-04
     */
    public function get_isPrivileged() {
        return $this->isPrivileged;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-05-04
     */
    public function get_isRestricted() {
        return $this->isRestricted;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-05-08
     */
    public function get_username() {
        return $this->username;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-05-08
     */
    public function get_password() {
        return $this->password;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-05-08
     */
    public function get_usergroup() {
        return $this->usergroup;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-09-04
     */
    public function get_defBillAddr() {
        return $this->defBillAddr;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-09-04
     */
    public function get_defShipAddr() {
        return $this->defShipAddr;
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-05-04
     */
    public function set_feuid($feuid) {
        $this->feuid = intval($feuid);
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-05-10
     */
    public function set_pid($pid) {
        $this->pid = intval($pid);
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-05-04
     */
    public function set_gsauid($gsauid) {
        $this->gsauid = intval($gsauid);
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-05-04
     */
    public function set_isPrivileged($isPrivileged) {

		$privGroup = intval(tx_ptgsauserreg_lib::getGsaUserregConfig('privilegedGroup'));
		if ($isPrivileged) {
			$this->isPrivileged = true;
			if ($privGroup) {
				$this->addGroupId($privGroup);
			}
		}
		else {
			$this->isPrivileged = false;
			if ($privGroup) {
				$this->delGroupId($privGroup);
			}
		}
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-05-04
     */
    public function set_isRestricted($isRestricted) {
        $this->isRestricted = $isRestricted ? true : false;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-05-08
     */
    public function set_username($username) {
        $this->username = (string) $username;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-05-08
     */
    public function set_password($password) {

		if (tx_ptgsauserreg_lib::getGsaUserregConfig('cryptPw')) {
			// use encrypted passwords
			if ($password != $this->password) {
				// password has changed
				if ($password) {
					// new password being set
					if (strncmp($password, '$1$', 3) == 0) {
						// new password apparently already encrypted
						if ($this->password == '') {
							// allowed for new set passwords (e.g. from DB)
							$this->password = (string) $password;
						}
						else {
							// treat as cleartext pw
							$this->password = tx_pttools_div::cryptPw($password);
						}
					}
					else {
						// encrypt cleartext pw
						$this->password = tx_pttools_div::cryptPw($password);
					}
				}
				else {
					// clear pw
					$this->password = '';
				}
			}
		}
		else {
			// use cleartext pw only
        	$this->password = (string) $password;
		}
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-05-08
     */
    public function set_usergroup($usergroup) {
        $this->usergroup = (string) $usergroup;
    }

    /**
     * Set the property value
     *
     * @param   int
     * @return  void
     * @throws  tx_pttools_exception  if setting individual Billing address is not supported 
     * @since   2006-08-04
     */
    public function set_defBillAddr($defBillAddr) {
		if ($defBillAddr != 0) {
			throw new tx_pttools_exception('Calling error', 3, 'Setting individual Billing address is not supported');
		}
        $this->defBillAddr = intval($defBillAddr);
    }

    /**
     * Set the property value
     *
     * @param   int
     * @return  void
     * @since   2006-08-04
     */
    public function set_defShipAddr($defShipAddr) {
        $this->defShipAddr = intval($defShipAddr);
    }
    
	/**
     * Magic method for function call
     * Use the hook inside to add various getter and setter to the user object
     * 
	 * @param $method		Name of the method to be called
	 * @param $arguments	Arguments passed to the function
     * @return unknown_type
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 14.08.2009
     */
    public function __call($method, $arguments)	{
		
    	// restrict to getter and setter methods
		if (!in_array(substr($method, 0, 4),array('get_', 'set_'))) {
			throw new tx_pttools_exceptionInternal(
					$method . ' is not allowed here - use getter and setter only.',
					$method . ' is not allowed here - use getter and setter only.'
			);
		}
    	
	    // HOOK: allow multiple hooks to simulate getter and setter
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['user_hooks']['simulateGetterSetterHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['user_hooks']['simulateGetterSetterHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				
				if(method_exists($hookObj, $method)) {
					return $hookObj->$method($this, $arguments);
				} else {
					throw new tx_pttools_exceptionInternal('No method defined in the hook to handle the method ' . $method, 'No method defined in the hook to handle the method ' . $method);
				}
				
			}
		} else {
			throw new tx_pttools_exceptionInternal('No hook defined to handle the method ' . $method, 'No hook defined to handle the method ' . $method);
		} 	
    }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_user.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_user.php']);
}

?>
