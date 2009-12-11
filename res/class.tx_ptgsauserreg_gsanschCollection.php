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
 * Address (gsansch) collection class for the 'pt_gsauserreg' extension
 *
 * $Id: class.tx_ptgsauserreg_gsanschCollection.php,v 1.14 2008/03/18 16:04:23 ry42 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-09-13
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsansch.php';// extension specific address class (gsansch)

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php'; // abstract object Collection class



/**
 * GSAnsch collection class
 *
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-09-13
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_gsanschCollection extends tx_pttools_objectCollection {
    
    /**
     * Properties
     */
	private   $baseId = 0;			// uid of base address
	protected $defship = 0;			// uid of default shipping address
	protected $defbill = 0;			// uid of default billing address
	protected $selectedId = 0;		// uid of last selected collection item
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor: creates a gsansch address collection object
     *
     * @param   integer	(optional) GSA ID of customer, if 0 create empty collection, otherwise collection of all address objects for this customer
     * @param   integer	(optional) uid of default shipping address (overrides GSA default if != 0)
     * @param   integer	(optional) uid of default billing address (overrides GSA default if != 0) 
     * @return  void
     * @global  
 	 * @author	Wolfgang Zenker <zenker@punkt.de>
 	 * @since   2006-09-13
     */
    public function __construct($gsauid=0, $defShipAddr=0, $defBillAddr=0) { 
    
        trace('***** Creating new '.__CLASS__.' object. *****');

		if ($gsauid != 0) {
			$gstandard = 0;	// id of address marked as default delivery in GSA

			// load collection from database
			$anschIdArr = tx_ptgsauserreg_gsanschAccessor::getInstance()->getAnschIdArr($gsauid);
			foreach ($anschIdArr as $anschId) {
				$this->addItem(new tx_ptgsauserreg_gsansch($anschId), $anschId);
				$anschObj = $this->getItemById($anschId);
				if ($anschObj->isBaseAdress()) {
					$this->baseId = $anschId;
				}
				if (! $anschObj->get_deprecated()) { // address is not deprecated
					// check if it should be used as default shipping/billing addr
					if ($anschId == intval($defShipAddr)) {
						$this->defship = $anschId;
					}
					if ($anschId == intval($defBillAddr)) {
						$this->defbill = $anschId;
					}
					if ($anschObj->get_gstandard()) {
						$gstandard = $anschId;
					}
				}
			}

			// verify/update address defaults
			if ($this->defship == 0) { // unknown default shipping addr.
				// use GSA standard or base address
				$this->defship = $gstandard ? $gstandard : $this->baseId;
			}
			if ($this->defbill == 0) { // unknown default billing addr.
				// use base address
				$this->defbill = $this->baseId;
			}
		}
    }   
    
    /***************************************************************************
     *   extended collection methods
     **************************************************************************/
    
	/***************************************************************************
	 * Adds one shipping address to the shipping address collection
	 *
	 * @param   object     shipping address to add, object of type tx_ptgsauserreg_gsansch required
	 * @param   integer    (optional) id of the shipping address
	 * @return  void
	 * @global
	 * @author  Wolfgang Zenker <zenker@punkt.de>
	 * @since   2006-09-13
	 */
    public function addItem(tx_ptgsauserreg_gsansch $gsanschObj, $id=0) {

		parent::addItem($gsanschObj, $id);
		if ($this->selectedId == 0) {
			$this->set_selectedId($id);
		}
    }

 
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * get selection array of gsansch objects in collection
     *
     * @param   array	(optional) array containing keys to hide
     * @param   boolean	(optional) hide deprecated items (default: true)
     * @return  array	2-dimensional array of index, selectionString
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-10-05
     */
    public function getAddressSelectionArray($hideKeyArr = array(), $hideDeprecated = true) {
        
		$stepper = $this->getIterator();
        // throw exception if collection is empty
        if ($stepper->count() < 1) {
            throw new tx_pttools_exception('No addresses found in address collection', 3);
        }
        
		$selectionArray = array();
		foreach ($stepper as $anschId => $anschObj) {
			if ($hideDeprecated && ($anschObj->get_deprecated())) {
				continue;
			}
			if (! in_array($anschId, $hideKeyArr)) {
				$selectionArray[$anschId] = $anschObj->getSelectionEntry();
			}
		}

		return $selectionArray;
    }
    
    /**
     * get selected item from collection
     *
     * @param   void
     * @return  object	gsansch object that has been selected with set_selectedId(), new empty gsansch object if non is selected
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-10-05
     */
    public function getSelectedItem() {
		if ($this->get_selectedId() > 0) {
			$ansch = $this->itemsArr[$this->get_selectedId()];
		}
		if (! isset($ansch)) {
			$ansch = new tx_ptgsauserreg_gsansch();
		}

		return $ansch;
	}

    /**
     * get default Billing Address
     *
     * @param   void
     * @return  object	gsansch object with default billing address
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-10-18
     */
    public function getDefBillingAddress() {
		$anschObj = $this->getItemById($this->defbill);
		if (! $anschObj) {
			$anschObj = $this->getItemById($this->baseId);
		}
		return $anschObj;
	}

    /**
     * get default Shipping Address
     *
     * @param   void
     * @return  object	gsansch object with default shipping address
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-10-18
     */
    public function getDefShippingAddress() {
		$anschObj = $this->getItemById($this->defship);
		if (! $anschObj) {
			$anschObj = $this->getItemById($this->baseId);
		}
		return $anschObj;
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

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer		property value
     * @since   2006-09-22
     */
    public function get_defship() {
        return $this->defship;
    }


    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2006-09-22
     */
    public function set_defship($defship) {
        $this->defship = intval($defship);
    }


    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer		property value
     * @since   2008-02-01
     */
    public function get_defbill() {
        return $this->defbill;
    }


    /**
     * Set the property value
     *
     * @param   integer
     * @return  void
     * @since   2008-02-01
     */
    public function set_defbill($defbill) {
        $this->defbill = intval($defbill);
    }


} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsanschCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsanschCollection.php']);
}

?>
