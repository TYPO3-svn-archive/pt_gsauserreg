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
 * helper class for country specific stuff in the pt_hosting framework
 *
 * $Id: class.tx_ptgsauserreg_countrySpecifics.php,v 1.9 2008/11/20 14:28:50 ry44 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-05-12
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customerAccessor.php';  // extension specific database accessor class for customer data
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_lib.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_staticInfoTables.php'; // country-infos
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class

/**
 * Helper class for country specific stuff, only static methods
 *
 * Information comes mostly from static_info_tables
 *
 * @access      public
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-05-12
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_countrySpecifics {
    
    /**
     * Checks if the given ISO 2 CountryCode belongs to a foreign country
     *
     * @param   string      ISO2 country code to check
     * @return  bool		given country is not configured hostcountry
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-05-12
     */
	public static function isForeignCountry($iso2cc) {

		return (strtoupper($iso2cc) != strtoupper(tx_ptgsauserreg_lib::getGsaUserregConfig('hostCountry')));

	}

    /**
     * Checks if the given ISO 2 CountryCode belongs to an overseas country
	 * with overseas defined as not being on the same continent as the host
     *
     * @param   string      ISO2 country code to check
     * @return  bool		given country is not on host continent
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-09-22
     */
	public static function isOverseasCountry($iso2cc) {

		return ! tx_pttools_staticInfoTables::areSameContinent($iso2cc, tx_ptgsauserreg_lib::getGsaUserregConfig('hostCountry'));

	}

    /**
     * Checks if the given ISO 2 CountryCode belongs to a EU member country
     *
     * @param   string      ISO2 country code to check
     * @return  bool		given country is EU member
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-05-12
     */
	public static function isEuMember($iso2cc) {

		return tx_pttools_staticInfoTables::isEuMember($iso2cc);

	}

    /**
     * Checks if the given ISO 2 CountryCode needs state/region in addresses
     *
     * @param   string      ISO2 country code to check
     * @return  bool		given country needs region in address
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-05-15
     */
	public static function needsRegion($iso2cc) {

		return tx_pttools_staticInfoTables::needsRegion($iso2cc);

	}

    /**
     * Returns array with country code and short names in current interface language
     *
     * @param   bool		limit to configured countries only
     * @global  object      $GLOBALS['TSFE']: TYPO3 FrontEnd config (language, allowedCountries)
     * @return  array		country codes => names
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-05-12
     */
	public static function getCountryList($limited = false) {

		if ($GLOBALS['TSFE'] instanceof tslib_fe) {
			$lang = $GLOBALS['TSFE']->config['config']['language'];
		}
		if (! $lang) {
			$lang = tx_ptgsauserreg_lib::getGsaUserregConfig('hostLanguage');
		}

		$countryList = array();
		$dblist = tx_pttools_staticInfoTables::selectCountries($lang);
		if ($limited) {
			$allowed = tx_ptgsauserreg_lib::getGsaUserregConfig('allowedCountries');
			if ($allowed == '') {
				// no allowed list defined, so make it unlimited
				$limited = false;
			}
			else {
				$checkArray = explode(',', $allowed);
				trace($checkArray);
			}
		}
		foreach ($dblist as $country) {
			if ((! $limited) || in_array($country['cn_iso_2'], $checkArray))
				$countryList[$country['cn_iso_2']] = $country['cn_short'];
		}

		trace($countryList);
		return $countryList;

	}

    /**
     * Returns name of given country in given or host language
     *
     * @param   string      ISO2 country code to check
     * @param   string      (optional) languageCode
     * @return  string		country name
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-05-12
     */
	public static function getCountryName($iso2cc, $lang = '') {

		if (! $lang) {
			$lang = tx_ptgsauserreg_lib::getGsaUserregConfig('hostLanguage');
		}
		return tx_pttools_staticInfoTables::selectCountryName($iso2cc, $lang);

	}

    /**
     * country uses titles AFTER name in address
	 *
	 * will probably someday come from static_info_tables
	 *
     * @param   string		iso2cc
     * @return  bool		title cames after name
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-12
     */
    public static function titleAfterName($iso2cc) {
        
		switch (strtoupper($iso2cc)) {
			case 'GB':
			case 'IE':
			case 'US':
			case 'CA':
			case 'AU':
			case 'NZ':
				return true;
		}
        return false;
    }
    
    /**
     * returns zip/city/region line of address depending on address
	 * format in given country
	 *
     * @param   string		iso2cc
     * @param   string		zipcode
     * @param   string		city name
     * @param   string		region/state
     * @return  string		city line of address
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-05-12
     */
    public static function getCityLine($iso2cc, $zip, $city, $region) {
        
		switch (tx_pttools_staticInfoTables::addressFormat($iso2cc)) {
			// write address depending on country specific format
			case 1: // zip city
				$cityline = $zip . ' ' . $city;
				break;

			case 2: // city zip
				$cityline = $city . ' ' . $zip;
				break;

			case 3: // city region zip
				$cityline = $city . ' ' . $region . ' ' . $zip;
				break;

			case 4: // city (region) zip
				$cityline = $city . ' (' . $region . ') ' . $zip;
				break;

			case 5: // city / zip
				$cityline = $city . ' / ' . $zip;
				break;

			case 6: // zip city, region
				$cityline = $zip . ' ' . $city . ', ' . $region;
				break;

			case 7: // zip city region
				$cityline = $zip . ' ' . $city . ' ' . $region;
				break;

			case 8: // zip city (region)
			default:
				$cityline = $zip . ' ' . $city . ' (' . $region . ')';
				break;
		}
		return $cityline;
    }
    

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_countrySpecifics.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_countrySpecifics.php']);
}

?>
