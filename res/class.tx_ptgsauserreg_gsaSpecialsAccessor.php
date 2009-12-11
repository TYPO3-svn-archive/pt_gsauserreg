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
 * Database accessor class for special GSA stuff
 *
 * $Id: class.tx_ptgsauserreg_gsaSpecialsAccessor.php,v 1.8 2008/04/01 10:49:37 ry37 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-09-15
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_gsasocket').'res/class.tx_ptgsasocket_paradoxDataAccessor.php'; // parent class for all GSA database accessor classes
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general helper library class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_staticInfoTables.php'; // country-infos

/**
 *  Database accessor class for GSA special stuff
 *
 * @access      public
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-09-15
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_gsaSpecialsAccessor extends tx_ptgsasocket_paradoxDataAccessor {
    
    /**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsauserreg_customerAccessor object) Singleton unique instance
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
    /**
     * Returns a unique instance (Singleton) of the object. Use this method instead of the private/protected class constructor.
     *
     * @param   void
     * @return  object      unique instance of the object (Singleton) 
     * @global     
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2005-11-15
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            self::$uniqueInstance = new tx_ptgsauserreg_gsaSpecialsAccessor;
        }
        return self::$uniqueInstance;
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/

    /**
     * Translates GSA country into ISO 3166-2 country code
     *
     * @param   string		country from GSA record
     * @return  string		matching ISO CC if possible, else input value
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-20
     */
    public function country2ISO($gsaCountry) {

		$tld = $this->getTldByAbbr($gsaCountry);
		if ($tld) {	// found tld in GSA px_laender
			$iso = tx_pttools_staticInfoTables::getIsoByTld($tld);
		}
		else { // try iso-3 cc
			$iso = tx_pttools_staticInfoTables::getIso2ByIso3($gsaCountry);
		}
		if (! $iso) { // found neither in px_laender nor iso-3 cc
			$iso = $gsaCountry;	// use input
		}

		return $iso;
	}

    /**
     * Translates ISO 3166-2 country code into GSA country
	 * If we don't find the country in GSAs px_laender table, we use the
	 * ISO 3166-3 code
     *
     * @param   string		ISO 3166-2 CC
     * @return  string		matching GSA code if possible, else ISO 3166-3 CC
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-20
     */
    public function country2GSA($isoCountry) {

		$tld = tx_pttools_staticInfoTables::getTldByIso($isoCountry);
		$gsaCountry = $this->getAbbrByTld($tld);
		if (! $gsaCountry) { // not found in GSA country table
			$gsaCountry = tx_pttools_staticInfoTables::getIso3ByIso2($isoCountry);
		}
		return $gsaCountry;
	}

    /**
     * Tries to extract name information from internal KONTAKT field for companies
     *
     * @param   array		raw data from GSA DB
     * @return  array		possibly updated data with fields extracted from kontakt
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-20
     */
    public function extractKontakt($dataArray) {

		$kontakt = $dataArray['INTERNAL_kontakt'];
		if (! $kontakt) {
			return $dataArray;
		}
		if ($dataArray['salutation']) {
			$len = strlen($dataArray['salutation']);
			if (strncmp($dataArray['salutation'], $kontakt, $len) == 0) {
				$kontakt = substr($kontakt, $len);
				// fix special case: GSA has "Herrn" quite often
				if ($kontakt[0] == 'n') {
					$kontakt = substr($kontakt, 1);
				}
				$kontakt = trim($kontakt);
				if (! $kontakt) {
					return $dataArray;
				}
			}
			else {
				return $dataArray;
			}
		}
		if ($dataArray['title']) {
			$len = strlen($dataArray['title']);
			if (strncmp($dataArray['title'], $kontakt, $len) == 0) {
				$kontakt = trim(substr($kontakt, $len));
				if (! $kontakt) {
					return $dataArray;
				}
			}
			else {
				return $dataArray;
			}
		}
		$lastname = strrchr($kontakt, ' ');
		if (! $lastname) {
			$lastname = $kontakt;
		}
		$len = strlen($lastname);
		$firstname = substr($kontakt, 0, -$len);
		$dataArray['firstname'] = trim($firstname);
		$dataArray['lastname'] = trim($lastname);

		return $dataArray;
	}

    /**
     * Create text for posting label from address object use same method as GSA for german language addresses.
	 * gets address format information from static_info extension
	 *
     * @param   object	pt_tools address object        
     * @return  array	array with fields named post1 - post7
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @global  
     * @since   2006-09-18
     */
    public function getPostFields($addrObj) {
        
		$result = array();
		for ($i = 1; $i <= 7; $i++) {
			$key = 'post'.$i;
			$result['$key'] = '';
		}
		
		$isCompany = (bool) $addrObj->get_company();
		$hasContact = $isCompany && $addrObj->get_lastname();
		$usePOBox = $addrObj->get_poBox() && ! $addrObj->get_streetAndNo();
		$country = $addrObj->get_country();

		if ($isCompany) {
			if (($country == 'DE') || ($country == 'AT')) {
				$result['post1'] = 'Firma';
			}
			$result['post2'] = $addrObj->getFullName();
		}
		else {
			$result['post1'] = $addrObj->get_salutation();
			if (tx_ptgsauserreg_countrySpecifics::titleAfterName($country)) {
				$result['post2'] = $addrObj->getFullName() . ' ' . $addrObj->get_title();
			}
			else {
				$result['post2'] = $addrObj->get_title() . ' ' . $addrObj->getFullName();
			}
			$result['post2'] = trim($result['post2']);
		}
		$nextfield = 3;
		if ($hasContact) {
			if (($country == 'DE') || ($country == 'AT')) {
				$result['post3'] = 'z. H. ';
			}
			else
			{
				$result['post3'] = 'Attn. ';
			}
			if ($addrObj->get_salutation()) {
				$result['post3'] .= $addrObj->get_salutation() . ' ';
			}
			if (!(tx_ptgsauserreg_countrySpecifics::titleAfterName($country))) {
				if ($addrObj->get_title()) {
					$result['post3'] .= $addrObj->get_title() . ' ';
				}
			}
			if ($addrObj->get_firstname()) {
				$result['post3'] .= $addrObj->get_firstname() . ' ';
			}
			$result['post3'] .= $addrObj->get_lastname();
			if (tx_ptgsauserreg_countrySpecifics::titleAfterName($country)) {
				if ($addrObj->get_title()) {
					$result['post3'] .= ' ' . $addrObj->get_title();
				}
			}
			$nextfield++;
		}
		$nextpost = 'post'.$nextfield;
		if ($usePOBox) {
			$zip = $addrObj->get_poBoxZip();
			$city = $addrObj->get_poBoxCity();
			if (($country == 'DE') || ($country == 'AT')) {
				$result[$nextpost] = 'Postfach ';
			}
			else {
				$result[$nextpost] = 'P.O.Box ';
			}
			$result[$nextpost] .= $addrObj->get_poBox();
		}
		else {
			$zip = $addrObj->get_zip();
			$city = $addrObj->get_city();
			$result[$nextpost] = $addrObj->get_streetAndNo();
		}
		$nextpost = 'post'.++$nextfield;
		if ($addrObj->get_addrSupplement()) {
			$result[$nextpost] = $addrObj->get_addrSupplement();
			$nextpost = 'post'.++$nextfield;
		}
		$result[$nextpost] = tx_ptgsauserreg_countrySpecifics::getCityLine($country, $zip, $city, $addrObj->get_state());
		$nextpost = 'post'.++$nextfield;
		if (tx_ptgsauserreg_countrySpecifics::isForeignCountry($country)) {
			$result[$nextpost] = tx_ptgsauserreg_countrySpecifics::getCountryName($country);
		}

		return $result;
    }
    
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsaSpecialsAccessor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_gsaSpecialsAccessor.php']);
}

?>
