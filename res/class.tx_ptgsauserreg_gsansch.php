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
 * Class for gsa backed address objects in the pt_hosting framework
 *
 * $Id: class.tx_ptgsauserreg_gsansch.php,v 1.23 2008/03/19 12:56:50 ry37 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-08-29
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsanschAccessor.php';  // extension specific database accessor class for address data
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsaSpecialsAccessor.php'; // GSA specific stuff
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';  // helper class for country specific stuff
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_address.php'; // general address helper class

/**
 *  Class for gsa backed address objects
 *
 * @access		public
 * @author		Wolfgang Zenker <zenker@punkt.de>
 * @since		2006-08-29
 * @package		TYPO3
 * @subpackage	tx_ptgsauserreg
 */
class tx_ptgsauserreg_gsansch extends tx_pttools_address {
    
    /**
     * Properties
     */

    protected $uid = 0;			// ID of TYPO3 DB record
    protected $gsauid = 0;        // ID of master database record (GSA-DB: ADRESSE.NUMMER)
	protected $anschid = 0;		// ID of backing gsa record (GSA-DB: ANSCH.NUMMER, 0 means master address from ADRESSE)

	protected $postmanu = 0;		// address in GSA has been modified
	// the following fields are generated from standard address fields
    protected $post1 = '';        // postal address line 1 to 7
    protected $post2 = '';
    protected $post3 = '';
    protected $post4 = '';
    protected $post5 = '';
    protected $post6 = '';
    protected $post7 = '';

	protected $gstandard = false; // address flagged as "standard" in GSA
    protected $dirty = false;     // flag for "dirty" address, e.g. if archived address and current GSA address are not equal or current GSA address does not exist anymore for given UID [Fabrizio Branca, 2007-04]
	protected $deprecated = false; // address should not be used for new orders
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) uid of record tx_ptgsauserreg_gsansch table. If missing, create empty object
     * @return	void   
     * @throws  tx_pttools_exception   if the first param is not numeric  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-10
     */
	public function __construct($anschId = 0) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($anschId)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if database IDs are given, retrieve array from database accessor (and overwrite 2nd param)
        if ($anschId > 0) {
            $anschDataArr = tx_ptgsauserreg_gsanschAccessor::getInstance()->selectAnschData($anschId);
        	$this->setAnschFromGivenArray($anschDataArr);
        }

        trace($this);
    }
    
    
    
   /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the object properties using data given by param array
     *
     * @param   array     Array containing address data to set as object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class.
     * @return  void        
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-08-30
     */
    protected function setAnschFromGivenArray($anschDataArr) {
        
		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($anschDataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($anschDataArr[$propertyname]);
			}
		}

		$postArray = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance()->getPostFields($this);
		if (($this->postmanu == false) && ($this->anschid != 0)) {
			for ($i = 1; $i <= 7; $i++) {
				$fname = 'post'.$i;
				if ($this->$fname != $postArray[$fname]) {
					$this->postmanu = true;
					break;
				}
			}
		}
    }
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-08-30
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
     * Stores current address data in TYPO3 and GSA DBs
	 * if uid is non-zero, this record is updated;
	 * otherwise a new record is created
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-08-30
     */
    public function storeSelf() {

		$dataArray = $this->getDataArray();
        $this->uid = tx_ptgsauserreg_gsanschAccessor::getInstance()->storeAnschData($dataArray);

	}

    /**
     * Deletes current gsansch object from TYPO3 and GSA DBs
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-07-13
     */
    public function deleteSelf() {

		$dataArray = $this->getDataArray();
        $this->uid = tx_ptgsauserreg_gsanschAccessor::getInstance()->deleteAnschData($dataArray);

	}

    /**
     * Returns the full name of customer
     * If customer is a company, use company property,
     * otherwise concatenate firstname and lastname
     *
	 * overwrites function from parent class
	 *
     * @param   void        
     * @return  string      full name
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-10
     */
    public function getFullName() {
        
        if ($this->company) {
            $fullName = $this->company;
		}
		else {
        	$fullName  = ($this->firstname) ? $this->firstname.' ' : '';
        	$fullName .= $this->lastname;
		}
        
        return $fullName;
    }
    
    /**
     * Returns the address label composed from all "post" fields
     *
     * @param   string      (optional) line delimiter (Default: '<br />') 
     * @param   boolean     (optional) use XSS prevention for "post" field content (Default: 1); set to 0 only for plain text usage!
     * @return  string      address label
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-10-11
     */
    public function getAddressLabel($delimiter='<br />', $useXssPrevention=1){
        
        $address = array();
        
        for ($i=1; $i<=7; $i++) {
            $getter = 'get_post'.$i;
            $tmp = ($useXssPrevention == 0 ? $this->$getter() : tx_pttools_div::htmlOutput($this->$getter()));
            if ($tmp) { 
                $address[] = $tmp;
            }
        }
        
        return implode($delimiter, $address);   
        
    }
    
    /**
     * Overwrites the post1-7 properties with address
     * constructed from other fields and clears postmanu
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @global  
     * @since   2006-04-21
     */
    public function rewritePostFields() {
        
		$postArray = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance()->getPostFields($this);

		$this->postmanu = false;
		for ($i = 1; $i <= 7; $i++) {
			$fname = 'post'.$i;
			$this->$fname = $postArray[$fname];
		}
    }
    
    /** 
     * Returns an shorthand selection entry for the gsansch object (e.g. to identify address in a selectorbox)
     *
     * @param   void
     * @return  string      shorthand selection entry for the shipping address
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-16
     */
    public function getSelectionEntry() {
        
        $selectionEntry = '';
		if ($this->get_postmanu()) {
			// use data from post1-7
			$cnt = 5;
			for ($i = 1; $cnt && ($i <= 7); $i++) {
				$getter = 'get_post'.$i;
				if ($this->$getter() != '') {
					$cnt--;
					if ($selectionEntry != '') {
						$selectionEntry .= ' ';
					}
					$selectionEntry .= $this->$getter();
				}
			}
		}
		else {
			$selectionEntry = $this->getFullName();
			if ($this->get_streetAndNo() != '') {
				$selectionEntry .= ', '.$this->get_streetAndNo();
			}
			else if ($this->get_poBox != '') {
				if (($this->country == 'DE') || ($this->country == 'AT')) {
					$selectionEntry .= ', Postfach';
				}
				else {
					$selectionEntry .= ', P.O.Box';
				}
				$selectionEntry .= ' '.$this->get_poBox();
			}
			$selectionEntry .= ', '.$this->get_city();
			if (tx_ptgsauserreg_countrySpecifics::isForeignCountry($this->get_country())) {
				$selectionEntry .= ', '.$this->get_country();
			}
		}

		return $selectionEntry;
	}
    
    /** 
     * returns if object represents customers base address
     *
     * @param   void
     * @return  boolean		address is customers base address
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-17
     */
    public function isBaseAdress() {
		return ($this->get_uid() != 0) && ($this->get_anschid() == 0);
	}

        
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-09-17
     */
    public function get_uid() {
        return $this->uid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-04-10
     */
    public function get_gsauid() {
        return $this->gsauid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-08-30
     */
    public function get_anschid() {
        return $this->anschid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-04-10
     */
    public function get_postmanu() {
        return $this->postmanu;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post1() {
        return $this->post1;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post2() {
        return $this->post2;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post3() {
        return $this->post3;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post4() {
        return $this->post4;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post5() {
        return $this->post5;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post6() {
        return $this->post6;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post7() {
        return $this->post7;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-04-10
     */
    public function get_gstandard() {
        return $this->gstandard;
    }


    /**
     * Returns the property value
     * 
     * @param   void
     * @return  bool    property value
     * @since   2007-04-17
     * @author Fabrizio Branca <branca@punkt.de>
     */
    public function get_dirty(){
        return $this->dirty;
    } 

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2008-01-09
     */
    public function get_deprecated() {
        return $this->deprecated;
    }
    

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-09-17
     */
    public function set_uid($uid) {
        $this->uid = intval($uid);
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-04-10
     */
    public function set_gsauid($gsauid) {
        $this->gsauid = intval($gsauid);
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-08-30
     */
    public function set_anschid($anschid) {
        $this->anschid = intval($anschid);
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-04-10
     */
    public function set_postmanu($postmanu) {
        $this->postmanu = intval($postmanu);
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_post1($post1) {
        $this->post1 = (string) $post1;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post2($post2) {
        $this->post2 = (string) $post2;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post3($post3) {
        $this->post3 = (string) $post3;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_post4($post4) {
        $this->post4 = (string) $post4;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_post5($post5) {
        $this->post5 = (string) $post5;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post6($post6) {
        $this->post6 = (string) $post6;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post7($post7) {
        $this->post7 = (string) $post7;
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-08-30
     */
    public function set_gstandard($gstandard) {
        $this->gstandard = $gstandard ? true : false;
    }
    
    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2007-04-17
     * @author	Fabrizio Branca <branca@punkt.de>
     */
    public function set_dirty($dirty) {
        $this->dirty = $dirty ? true : false;
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2008-01-09
     */
    public function set_deprecated($deprecated) {
        $this->deprecated = $deprecated ? true : false;
    }
    
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsansch.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsansch.php']);
}

?>
