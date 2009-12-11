<?php

########################################################################
# Extension Manager/Repository config file for ext: "pt_gsauserreg"
#
# Auto generated 18-12-2008 15:07
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'GSA User Registration',
	'description' => 'Frontend user and customer registration for GSA Shop and all extensions of the "General Shop Applications" (GSA) category, based on a data layer compatible to the German ERP system "GS AUFTRAG Professional"',
	'category' => 'General Shop Applications',
	'author' => 'Wolfgang Zenker, Dorit Rotter',
	'author_email' => 't3extensions@punkt.de',
	'shy' => '',
	'dependencies' => 'cms,static_info_tables,static_info_tables_de,pt_tools,pt_gsasocket',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'punkt.de GmbH',
	'version' => '0.1.1dev',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'static_info_tables' => '',
			'static_info_tables_de' => '',
			'pt_tools' => '0.4.1-',
			'pt_gsasocket' => '0.3.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'pt_euvatcheck' => '0.0.8-',
			'External software ktoblzcheck, see http://ktoblzcheck.sourceforge.net/ (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:63:{s:9:"ChangeLog";s:4:"a43e";s:10:"README.txt";s:4:"778f";s:33:"class.tx_ptgsauserreg_country.php";s:4:"74a3";s:40:"class.tx_ptgsauserreg_gsa_adresse_id.php";s:4:"7bba";s:38:"class.tx_ptgsauserreg_gsa_ansch_id.php";s:4:"4f00";s:21:"ext_conf_template.txt";s:4:"9304";s:12:"ext_icon.gif";s:4:"4546";s:17:"ext_localconf.php";s:4:"f084";s:14:"ext_tables.php";s:4:"cf32";s:14:"ext_tables.sql";s:4:"66fb";s:39:"icon_tx_ptgsauserreg_customergroups.gif";s:4:"475a";s:32:"icon_tx_ptgsauserreg_gsansch.gif";s:4:"475a";s:13:"locallang.xml";s:4:"bad9";s:16:"locallang_db.xml";s:4:"07c1";s:7:"tca.php";s:4:"3ceb";s:14:"doc/manual.sxw";s:4:"54f1";s:19:"doc/wizard_form.dat";s:4:"03a3";s:20:"doc/wizard_form.html";s:4:"01b6";s:33:"pi1/class.tx_ptgsauserreg_pi1.php";s:4:"1101";s:17:"pi1/locallang.xml";s:4:"085b";s:17:"pi1/template.html";s:4:"4a8c";s:24:"pi1/static/constants.txt";s:4:"d0e1";s:24:"pi1/static/editorcfg.txt";s:4:"0f94";s:20:"pi1/static/setup.txt";s:4:"944b";s:33:"pi2/class.tx_ptgsauserreg_pi2.php";s:4:"78c0";s:17:"pi2/locallang.xml";s:4:"c314";s:17:"pi2/template.html";s:4:"0956";s:24:"pi2/static/constants.txt";s:4:"e2a1";s:24:"pi2/static/editorcfg.txt";s:4:"836b";s:20:"pi2/static/setup.txt";s:4:"ca29";s:33:"pi3/class.tx_ptgsauserreg_pi3.php";s:4:"860f";s:17:"pi3/locallang.xml";s:4:"e42c";s:24:"pi3/static/editorcfg.txt";s:4:"bd6b";s:33:"pi4/class.tx_ptgsauserreg_pi4.php";s:4:"6d21";s:17:"pi4/locallang.xml";s:4:"7ca9";s:17:"pi4/template.html";s:4:"7f4b";s:24:"pi4/static/constants.txt";s:4:"795b";s:24:"pi4/static/editorcfg.txt";s:4:"3aee";s:20:"pi4/static/setup.txt";s:4:"e082";s:33:"pi5/class.tx_ptgsauserreg_pi5.php";s:4:"3acf";s:17:"pi5/locallang.xml";s:4:"5d0c";s:17:"pi5/template.html";s:4:"4ff6";s:24:"pi5/static/constants.txt";s:4:"9a39";s:24:"pi5/static/editorcfg.txt";s:4:"ddfa";s:20:"pi5/static/setup.txt";s:4:"11fe";s:41:"res/class.tx_ptgsauserreg_adminFilter.php";s:4:"1b8c";s:46:"res/class.tx_ptgsauserreg_countrySpecifics.php";s:4:"8a04";s:38:"res/class.tx_ptgsauserreg_customer.php";s:4:"9fb3";s:46:"res/class.tx_ptgsauserreg_customerAccessor.php";s:4:"ca88";s:48:"res/class.tx_ptgsauserreg_customerCollection.php";s:4:"dbfa";s:40:"res/class.tx_ptgsauserreg_feCustomer.php";s:4:"9708";s:49:"res/class.tx_ptgsauserreg_gsaSpecialsAccessor.php";s:4:"1558";s:37:"res/class.tx_ptgsauserreg_gsansch.php";s:4:"f231";s:45:"res/class.tx_ptgsauserreg_gsanschAccessor.php";s:4:"cffb";s:47:"res/class.tx_ptgsauserreg_gsanschCollection.php";s:4:"556a";s:50:"res/class.tx_ptgsauserreg_hooks_t3lib_userauth.php";s:4:"7e18";s:33:"res/class.tx_ptgsauserreg_lib.php";s:4:"9a05";s:43:"res/class.tx_ptgsauserreg_paymentMethod.php";s:4:"f44a";s:34:"res/class.tx_ptgsauserreg_user.php";s:4:"6742";s:42:"res/class.tx_ptgsauserreg_userAccessor.php";s:4:"03a8";s:44:"res/class.tx_ptgsauserreg_userCollection.php";s:4:"1965";s:20:"static/constants.txt";s:4:"65b1";s:16:"static/setup.txt";s:4:"9b08";}',
	'suggests' => array(
	),
);

?>
