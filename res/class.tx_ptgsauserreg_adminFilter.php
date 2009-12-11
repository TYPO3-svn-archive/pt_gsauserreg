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
 * Class for user input on Admin Interface Search page
 *
 * $Id: class.tx_ptgsauserreg_adminFilter.php,v 1.4 2008/03/19 12:56:50 ry37 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2007-07-25
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper functions
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class

/**
 *  Class for user Input on Admin Interface
 *
 * @access      public
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2007-07-25
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_adminFilter {
    
	/**
	 * Constants
	 */

    /**
     * Properties
     */

    protected $gsa_kundnr;		// ADRESSE.KUNDNR in GSA-DB
	protected $username;
	protected $company;
	protected $firstname;
	protected $lastname;
	protected $streetAndNo;
	protected $zip;
	protected $city;
	protected $email1;
	protected $exactMatch = false;	// use exact instead of wildcard match
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-07-25
     */
    public function getDataArray() {

		$dataArray = array();

		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			$getter = 'get_'.$propertyname;
			$dataArray[$propertyname] = $this->$getter();
		}

		return $dataArray;
	}
        
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	integer	property value
	 * @since	2007-07-25
	*/
	public function get_gsa_kundnr() {
		return $this->gsa_kundnr;
	}

	/**
	 * Set the property value
	 *
	 * @param	integer
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_gsa_kundnr($gsa_kundnr) {
		$this->gsa_kundnr = intval($gsa_kundnr);
		if ($this->gsa_kundnr == 0) {
			$this->gsa_kundnr = '';
		}
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_username() {
		return $this->username;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_username($username) {
		$this->username = (string) $username;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_company() {
		return $this->company;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_company($company) {
		$this->company = (string) $company;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_firstname() {
		return $this->firstname;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_firstname($firstname) {
		$this->firstname = (string) $firstname;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_lastname() {
		return $this->lastname;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_lastname($lastname) {
		$this->lastname = (string) $lastname;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_streetAndNo() {
		return $this->streetAndNo;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_streetAndNo($streetAndNo) {
		$this->streetAndNo = (string) $streetAndNo;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_zip() {
		return $this->zip;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_zip($zip) {
		$this->zip = (string) $zip;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_city() {
		return $this->city;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_city($city) {
		$this->city = (string) $city;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2007-07-25
	*/
	public function get_email1() {
		return $this->email1;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_email1($email1) {
		$this->email1 = (string) $email1;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	boolean	property value
	 * @since	2007-07-25
	*/
	public function get_exactMatch() {
		return $this->exactMatch;
	}

	/**
	 * Set the property value
	 *
	 * @param	boolean
	 * @return	void
	 * @since	2007-07-25
	*/
	public function set_exactMatch($exactMatch) {
		$this->exactMatch = $exactMatch ? true : false;
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_adminFilter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_adminFilter.php']);
}

?>
