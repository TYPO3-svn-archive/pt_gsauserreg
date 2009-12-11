<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_ptgsauserreg_pi1 = < plugin.tx_ptgsauserreg_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_ptgsauserreg_pi1.php','_pi1','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_ptgsauserreg_pi2 = < plugin.tx_ptgsauserreg_pi2.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_ptgsauserreg_pi2.php','_pi2','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_ptgsauserreg_pi3 = < plugin.tx_ptgsauserreg_pi3.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi3/class.tx_ptgsauserreg_pi3.php','_pi3','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_ptgsauserreg_pi4 = < plugin.tx_ptgsauserreg_pi4.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi4/class.tx_ptgsauserreg_pi4.php','_pi4','list_type',0);


  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_ptgsauserreg_pi5 = < plugin.tx_ptgsauserreg_pi5.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi5/class.tx_ptgsauserreg_pi5.php','_pi5','list_type',0);

/*******************************************************************************
 * FRONTEND HOOKS   - !!IMPORTANT: clear conf cache to activate changes!!
 ******************************************************************************/

if (TYPO3_MODE == 'FE') { // WARNING: do not remove this condition since this may stop the backend from working!

    /*
     * pt_gsashop pi1 hooks
     */
    require(t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_hooks_t3lib_userauth.php');
//    $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = 'EXT:'.$_EXTKEY.'/res/class.tx_ptgsauserreg_hooks_t3lib_userauth.php:tx_ptgsauserreg_hooks_t3lib_userauth->exec_clearSessionStorage';
    
}

?>
