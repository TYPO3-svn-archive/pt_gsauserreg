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
 * user collection class for the 'pt_gsauserreg' extension
 *
 * $Id: class.tx_ptgsauserreg_userCollection.php,v 1.7 2008/11/28 13:13:53 ry44 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2007-06-01
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_user.php';// extension specific address class (user)

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class



/**
 * GSUser collection class
 *
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2007-06-01
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_userCollection extends tx_pttools_objectCollection {
    
    /**
     * Properties
     */
	protected $selectedId = 0;		// uid of last selected collection item
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates a user collection object
     *
     * @param   integer	(optional) GSA ID of customer, if 0 create empty collection, otherwise collection of all user objects for this customer
     * @param   boolean	(optional) fetch ALL users for all customers (only if gsauid = 0)
     * @return  void
 	 * @author	Wolfgang Zenker <zenker@punkt.de>
 	 * @since   2007-06-01
     */
    public function __construct($gsauid = 0, $fetchall = false) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');

		$userIdArr = array();

		if (($gsauid != 0) || $fetchall) {
			// load collection from database
			$userIdArr = tx_ptgsauserreg_userAccessor::getInstance()->getUserIdArr($gsauid, $fetchall);
			foreach ($userIdArr as $userId) {
				$this->addItem(new tx_ptgsauserreg_user($userId), $userId);
			}
		}
    }   
    
    /***************************************************************************
     *   extended collection methods
     **************************************************************************/
    
	/***************************************************************************
	 * Load collection by searching for given search criteria
	 *
	 * @param   array		array of key => value pairs for properties to search for
	 * @param   boolean		(optional) use exact match instead of wildcard match
	 * @param   integer		(optional) max. no. of users to load (0 = no limit)
	 * @return  integer		no. of matching users
	 * @author  Wolfgang Zenker <zenker@punkt.de>
	 * @since   2007-07-25
	 */
    public function loadBySearchlist($searchlist, $exactMatch = false, $limit = 0) {

		// remove any items present
		$this->clearItems();
		$this->selectedId = 0;

		// load collection from database
		$userIdArr = tx_ptgsauserreg_userAccessor::getInstance()->searchUserIdArr($searchlist, $exactMatch);
		if (($limit == 0) || (count($userIdArr) <= $limit)) {
			foreach ($userIdArr as $userId) {
				$this->addItem(new tx_ptgsauserreg_user($userId), $userId);
			}
		}
		return count($userIdArr);
    }

	/***************************************************************************
	 * Adds one address to the address collection
	 *
	 * @param   object      address to add, object of type tx_ptgsauserreg_user required
	 * @param   integer     (optional)
	 * @return  void
	 * @author  Wolfgang Zenker <zenker@punkt.de>
	 * @since   2006-09-13
	 */
    public function addItem(tx_ptgsauserreg_user $userObj, $id=0) {

		parent::addItem($userObj, $id);
		if ($this->selectedId == 0) {
			$this->set_selectedId($id);
		}
    }

 
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * get selection array of user objects in collection
     *
     * @param   array	(optional) array containing keys to hide
     * @return  array	2-dimensional array of index, selectionString
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-01
     */
    public function getUserSelectionArray($hideKeyArr = array()) {
        
		$stepper = $this->getIterator();
        // throw exception if collection is empty
        if ($stepper->count() < 1) {
            throw new tx_pttools_exception('No user found in user collection', 3);
        }
        
		$selectionArray = array();
		foreach ($stepper as $userId => $userObj) {
			if (! in_array($userId, $hideKeyArr)) {
				$selString = $userObj->getFullname();
				$department = $userObj->get_department();
				if ($department) {
					$selString .= ', '.$department;
				}
				$selString .= ', '.$userObj->get_zip();
				$selString .= ' '.$userObj->get_city();
				$selectionArray[$userId] = $selString;
			}
		}

		return $selectionArray;
    }
    
    /**
     * get selected item from collection
     *
     * @param   void
     * @return  tx_ptgsauserreg_user	user object that has been selected with set_selectedId(), new empty user object if non is selected
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-10-05
     */
    public function getSelectedItem() {
		if ($this->get_selectedId() > 0) {
			$user = $this->itemsArr[$this->get_selectedId()];
		}
		if (! isset($user)) {
			$user = new tx_ptgsauserreg_user();
		}

		return $user;
	}

    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer		property value
     * @since   2006-09-16
     */
    public function get_selectedId() {
        return $this->selectedId;
    }


    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2006-09-16
     */
    public function set_selectedId($selectedId) {
        $this->selectedId = intval($selectedId);
    }

} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_userCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_userCollection.php']);
}

?>
