<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007 Wolfgang Zenker <zenker@punkt.de>
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
 * Hooking class of the 'pt_gsauserreg' extension for hooks in t3lib_userauth
 *
 * $Id: class.tx_ptgsauserreg_hooks_t3lib_userauth.php,v 1.4 2008/08/14 14:30:10 ry96 Exp $
 *
 * @author  Wolfgang Zenker <zenker@punkt.de>
 * @since   2008-07-29
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */


/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class


/**
 * Class being included by t3lib using hooks in t3lib_userauth
 *
 * @author      Wolfgang Zenker <zenker@punkt.de>
 * @since       2008-07-29
 * @package     TYPO3
 * @subpackage  tx_ptgsashophosting
 */
class tx_ptgsauserreg_hooks_t3lib_userauth {
    
	/*
	 * Class constants
	 */
    const SESSION_KEY_NAME = 'pt_gsauserregCustomer'; // (string) session key name to store customer in session
    const SESSION_KEY_ACTPAGE = 'pt_gsauserreg_actpage'; // (string) session key name to store active page in session
    const SESSION_KEY_BACKURL = 'pt_gsauserreg_backurl'; // (string) session key name to store back url in session
    const SESSION_KEY_RETURNVAR = 'pt_gsauserreg_returnvar'; // (string) session key name to store name of return variable in session
    const SESSION_KEY_USER = 'pt_gsauserregUser'; // (string) session key name to store user object in session
    const SESSION_KEY_ANSCH = 'pt_gsauserregAnsch'; // (string) session key name to store gsansch object in session
    const SESSION_KEY_FILTER = 'pt_gsauserregFilter'; // (string) session key name to store filter object in session

	/*
	 * Class properties
	 */

    /**
     * This method is called by hooks in t3lib_userauth after login and logout and is used to clear out any stuff that has accidentally been left in session storage
     *
     * @param   void
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2008-07-29
     */
	public function exec_clearSessionStorage() {
		$logintype = t3lib_div::GPvar('logintype');
		// we have to check if this is really a login/logout event here,
		// because the logout hook is actually called before every page
		if (($logintype == 'login') || ($logintype == 'logout')) {
        	t3lib_div::devLog('Delete Session_keys at '.$logintype, 'pt_gsauserreg', 1);
			$sessionStore = tx_pttools_sessionStorageAdapter::getInstance();
/*
			$sessionStore->delete(self::SESSION_KEY_NAME);
			$sessionStore->delete(self::SESSION_KEY_ACTPAGE);
			$sessionStore->delete(self::SESSION_KEY_BACKURL);
			$sessionStore->delete(self::SESSION_KEY_RETURNVAR);
			$sessionStore->delete(self::SESSION_KEY_USER);
			$sessionStore->delete(self::SESSION_KEY_ANSCH);
			$sessionStore->delete(self::SESSION_KEY_FILTER);
*/
		}
	}
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_hooks_t3lib_userauth.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_hooks_t3lib_userauth.php']);
}

?>
