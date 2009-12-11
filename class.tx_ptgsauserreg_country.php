<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Wolfgang Zenker (t3extensions@punkt.de)
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
 * Class/Function which manipulates the item-array for table/field fe_users_tx_ptgsauserreg_country.
 *
 * $Id: class.tx_ptgsauserreg_country.php,v 1.4 2008/04/01 10:49:37 ry37 Exp $
 *
 * @author	Wolfgang Zenker <t3extensions@punkt.de>
 */


class tx_ptgsauserreg_country {

    /**
     * itemsProcFunc callback function for tx_ptgsauserreg_country (called from ext_tables.php). Manipulates the item-array for table/field fe_users_tx_ptgsauserreg_country.
     *
     * @param   array   parameters with the selectorbox item-array in the key 'items' (default param for itemsProcFunc callback function, passed by reference)
     * @param   t3lib_tceforms  t3lib_tceforms object (default param for itemsProcFunc callback function, passed by reference)
     * @return  void    (no return value - the $params and $pObj variables are passed by reference, so content is passed back automatically)
     * @see     ext_tables.php
     * @author  Wolfgang Zenker <zenker@punkt.de>
    */                          
	function main(&$params,&$pObj)	{

		try {
			$row = t3lib_befunc::getRecordsByField('static_countries', '1','1');
			foreach($row AS $key=>$value) {
				$params["items"][]=Array($value['cn_short_en'],$value['cn_iso_2']);
			}
		} catch (tx_pttools_exception $excObj) {

			// if an exception has been caught, handle it
			$excObj->handleException();
			echo $excObj;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/class.tx_ptgsauserreg_country.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/class.tx_ptgsauserreg_country.php']);
}

?>
