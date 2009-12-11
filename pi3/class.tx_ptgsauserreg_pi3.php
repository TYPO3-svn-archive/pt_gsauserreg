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
 * Plugin 'Customer groups' for the 'pt_gsauserreg' extension. This plugin is not implemented yet.
 *
 * $Id: class.tx_ptgsauserreg_pi3.php,v 1.4 2008/04/01 10:49:37 ry37 Exp $
 *
 * @author	Wolfgang Zenker <t3extensions@punkt.de>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_ptgsauserreg_pi3 extends tslib_pibase {
	var $prefixId = 'tx_ptgsauserreg_pi3';		// Same as class name
	var $scriptRelPath = 'pi3/class.tx_ptgsauserreg_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey = 'pt_gsauserreg';	// The extension key.
	
	/**
	 * [Put your description here]
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		$content='nothing yet';
		return $this->pi_wrapInBaseClass($content);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi3/class.tx_ptgsauserreg_pi3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi3/class.tx_ptgsauserreg_pi3.php']);
}

?>
