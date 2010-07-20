<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Dorit Rottner (rottner@punkt.de)
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
 * customer collection class for the 'pt_gsauserreg' extension
 *
 * $Id: class.tx_ptgsauserreg_customerCollection.php,v 1.3 2008/02/21 16:27:50 ry96 Exp $
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 * @since   2007-06-12
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
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_iTemplateable.php';



/**
 * GSA Customer collection class
 *
 * @author	    Dorit Rottner <rottner@punkt.de>
 * @since       2007-06-12
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_customerCollection extends tx_pttools_objectCollection implements tx_pttools_iTemplateable {

    /**
     * Properties
     */



	/***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/

    /**
     * Class constructor: creates a collection of customer objects. If no parameter is specified all Customers of type Onlinekunde are given back
     *
     * @param   string  (optional) firstname of customer
     * @param   string  (optional) lastname of customer
     * @param   string  (optional) city of customer
     * @param   string  (optional) street and Number of customer
     * @param   string  (optional) email1 of customer
     * @param   bool    (optional) get ALL customers instead of only ONLINEKUNDE
     * @return  void
 	 * @author	Dorit Rottner <rottner@punkt.de>
 	 * @since   2007-06-12
     */
    public function __construct($firstname = '', $lastname = '', $city = '', $streetAndNo = '', $email1 = '', $fetchall = false) {

        trace('***** Creating new '.__CLASS__.' object. *****');

		// load collection from database
		$idArr = tx_ptgsauserreg_customerAccessor::getInstance()->getCustomerIdArr($firstname, $lastname, $city, $streetAndNo, $email1, $fetchall);
		foreach ($idArr as $customerId) {
			$this->addItem(new tx_ptgsauserreg_customer($customerId), $customerId);
		}
    }

    /***************************************************************************
	 * Methods implementing the "tx_pttools_iTemplateable" interface
	 **************************************************************************/

	/**
	 * Returns a marker array
	 *
	 * @param   void
	 * @return  array
	 * @author  Simon Schaufelberger <schaufelberger@punkt.de>
	 * @since   2010-07-15
	 */
	public function getMarkerArray() {
		$markerArray = array();
		foreach ($this as $column) {
			$markerArray[] = $column->getMarkerArray();
		}
		return $markerArray;
	}

	/**
     * get selection array of user objects in collection
     *
     * @param   array	(optional) array containing keys to hide
     * @return  array	2-dimensional array of index, selectionString
     * @author  Simon Schaufelberger <schaufelberger@punkt.de>
     * @since   2010-07-15
     */
    public function getCustomerSelectionArray($hideKeyArr = array()) {

		$stepper = $this->getIterator();
		// throw exception if collection is empty
		if ($stepper->count() < 1) {
			throw new tx_pttools_exception('No customer found in user collection', 3);
		}

		$selectionArray = array();
		foreach ($stepper as $customerId => $customerObj) {
			if (! in_array($customerId, $hideKeyArr)) {
				$selString = $customerObj->getFullname();
				$department = $customerObj->get_department();
				if ($department) {
					$selString .= ', '.$department;
				}
				$selString .= ', '.$customerObj->get_zip();
				$selString .= ' '.$customerObj->get_city();
				$selectionArray[$customerId] = $selString;
			}
		}

		return $selectionArray;
    }

} // end class


/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_customerCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_customerCollection.php']);
}
?>