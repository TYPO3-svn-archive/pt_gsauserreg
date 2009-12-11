<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Fabrizio Branca, Dorit Rottner (branca@punkt.de, rottner@punkt.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * Extension specific library containing static methods and configuration constants for the 'pt_gsauserreg' extension
 *
 * $Id: class.tx_ptgsauserreg_lib.php,v 1.2 2008/11/20 19:25:35 ry42 Exp $
 *
 * @author      Fabrizio Branca, Dorit Rottner <branca@punkt.de, rottner@punkt.de>
 * @since       2005-03
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php';



/**
 * Provides extension specific static library methods and configuration constants for the 'pt_gsauserreg' extension
 *
 * @author      Fabrizio Branca, Dorit Rottner <branca@punkt.de, rottner@punkt.de>
 * @since       2005-03
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_lib {
     
    
    /***************************************************************************
     *   STATIC EXTENSION SPECIFIC METHODS
     **************************************************************************/
     
      
    /**
     * Returns the typoscript config of GSA Userreg
     * 
     * @param   string  (optional) name of the configuration value to get (if not set [=default], the complete "config.tx_ptgsauserreg." array is returned)
     * @param   boolean (optional) flag whether an exception will be thrown if no configuration found, default=true
     * @return  mixed   array: complete GSA Userreg typoscript config "config.tx_ptgsauserreg." (if $configValue='') OR mixed: typoscript config value (if $configValue is given)
     * @author  Fabrizio Branca, Dorit Rottner <branca@punkt.de, rottner@punkt.de>
     * @since   2008-10-16
     */
    public static function getGsaUserregConfig($configValue='', $throwExceptionIfNoConfigFound=true) {
        
        $gsaUserregConfigArray = tx_pttools_div::typoscriptRegistry('config.pt_gsauserreg.', NULL, 'pt_gsauserreg', 'tsConfigurationPid');
        if ($throwExceptionIfNoConfigFound == true) {
            tx_pttools_assert::isNotEmptyArray($gsaUserregConfigArray, array('message' => 'No GSA Userreg typoscript config found.'));
        }
        
        if (!empty($configValue)) {
            $returnValue = $gsaUserregConfigArray[$configValue];
        } else {
            $returnValue = $gsaUserregConfigArray;
        }
        
        return $returnValue;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_lib.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_lib.php']);
}

?>
