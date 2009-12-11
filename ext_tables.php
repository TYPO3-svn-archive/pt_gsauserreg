<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=="BE")	include_once(t3lib_extMgm::extPath("pt_gsauserreg")."class.tx_ptgsauserreg_gsa_adresse_id.php");

$TCA["tx_ptgsauserreg_customergroups"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_customergroups',		
		'label' => 'title',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"default_sortby" => "ORDER BY crdate",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",	
			"starttime" => "starttime",	
			"endtime" => "endtime",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_ptgsauserreg_customergroups.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, starttime, endtime, title, gsa_adresse_id",
	)
);


if (TYPO3_MODE=="BE")	include_once(t3lib_extMgm::extPath("pt_gsauserreg")."class.tx_ptgsauserreg_gsa_adresse_id.php");


if (TYPO3_MODE=="BE")	include_once(t3lib_extMgm::extPath("pt_gsauserreg")."class.tx_ptgsauserreg_gsa_ansch_id.php");

$TCA["tx_ptgsauserreg_gsansch"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch',		
		'label' => 'uid',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"default_sortby" => "ORDER BY crdate",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_ptgsauserreg_gsansch.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, gsa_adresse_id, gsa_ansch_id, deprecated, company, salutation, title, firstname, lastname, street, supplement, zip, city, pobox, poboxzip, poboxcity, state, country",
	)
);


if (TYPO3_MODE=="BE")	include_once(t3lib_extMgm::extPath("pt_gsauserreg")."class.tx_ptgsauserreg_gsa_adresse_id.php");


if (TYPO3_MODE=="BE")	include_once(t3lib_extMgm::extPath("pt_gsauserreg")."class.tx_ptgsauserreg_country.php");

$tempColumns = Array (
	"tx_ptgsauserreg_gsa_adresse_id" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_gsa_adresse_id",		
		"config" => Array (
			/*
			"type" => "select",
			"items" => Array (
				Array("LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_gsa_adresse_id.I.0", "0"),
			),
			"itemsProcFunc" => "tx_ptgsauserreg_gsa_adresse_id->main",	
			"size" => 1,	
			"maxitems" => 1,
			*/
			"type" => "input",	
			"size" => "10",	
			"max" => "10",	
			"eval" => "int",
			/*"type" => "none",
			"rows" => 1,
			"cols" => 11,*/
		)
	),
	"tx_ptgsauserreg_salutation" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_salutation",		
		"config" => Array (
			"type" => "input",	
			"size" => "30",	
			"max" => "30",	
			"eval" => "trim",
		)
	),
	"tx_ptgsauserreg_firstname" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_firstname",		
		"config" => Array (
			"type" => "input",	
			"size" => "30",	
			"max" => "30",	
			"eval" => "trim",
		)
	),
	"tx_ptgsauserreg_lastname" => Array (
		"exclude" => 1,
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_lastname",
		"config" => Array (
			"type" => "input",
			"size" => "40",
			"max" => "40",
			"eval" => "trim",
		)
	),
	"tx_ptgsauserreg_department" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_department",		
		"config" => Array (
			"type" => "input",	
			"size" => "30",	
			"max" => "80",	
			"eval" => "trim",
		)
	),
	"tx_ptgsauserreg_state" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_state",		
		"config" => Array (
			"type" => "input",	
			"size" => "30",	
			"max" => "60",	
			"eval" => "trim",
		)
	),
	"tx_ptgsauserreg_country" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_country",		
		"config" => Array (
			"type" => "select",
			"items" => Array (
				Array("LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_country.I.0", "0"),
			),
			"itemsProcFunc" => "tx_ptgsauserreg_country->main",	
			"size" => 1,	
			"maxitems" => 1,
		)
	),
	"tx_ptgsauserreg_mobile" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_mobile",		
		"config" => Array (
			"type" => "input",	
			"size" => "30",	
			"max" => "30",	
			"eval" => "trim",
		)
	),
	"tx_ptgsauserreg_isprivileged" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_isprivileged",		
		"config" => Array (
			"type" => "check",
		)
	),
	"tx_ptgsauserreg_isrestricted" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_isrestricted",		
		"config" => Array (
			"type" => "check",
		)
	),
	"tx_ptgsauserreg_customergroups_uid" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_customergroups_uid",		
		"config" => Array (
			"type" => "select",	
			"foreign_table" => "tx_ptgsauserreg_customergroups",	
			"foreign_table_where" => "AND tx_ptgsauserreg_customergroups.pid=###CURRENT_PID### ORDER BY tx_ptgsauserreg_customergroups.uid",	
			"size" => 8,	
			"minitems" => 0,
			"maxitems" => 100,
		)
	),
	"tx_ptgsauserreg_defbilladdr_uid" => Array (        
		"exclude" => 1,        
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_defbilladdr_uid",        
		"config" => Array (
			"type" => "input",	
			"size" => "10",	
			"max" => "10",	
			"eval" => "int",
			/*
			"type" => "select",    
			"items" => Array (
				Array("",0),
			),
			"foreign_table" => "tx_ptgsauserreg_gsansch",    
			"foreign_table_where" => "AND tx_ptgsauserreg_gsansch.pid=###CURRENT_PID### ORDER BY tx_ptgsauserreg_gsansch.uid",    
			"size" => 1,    
			"minitems" => 0,
			"maxitems" => 1,
			*/
		)
	),
	"tx_ptgsauserreg_defshipaddr_uid" => Array (        
		"exclude" => 1,        
		"label" => "LLL:EXT:pt_gsauserreg/locallang_db.xml:fe_users.tx_ptgsauserreg_defshipaddr_uid",        
		"config" => Array (
			"type" => "input",	
			"size" => "10",	
			"max" => "10",	
			"eval" => "int",
			/*
			"type" => "select",    
			"items" => Array (
				Array("",0),
			),
			"foreign_table" => "tx_ptgsauserreg_gsansch",    
			"foreign_table_where" => "AND tx_ptgsauserreg_gsansch.pid=###CURRENT_PID### ORDER BY tx_ptgsauserreg_gsansch.uid",    
			"size" => 1,    
			"minitems" => 0,
			"maxitems" => 1,
			*/
		)
	),
);


t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","tx_ptgsauserreg_gsa_adresse_id;;;;1-1-1, tx_ptgsauserreg_salutation, tx_ptgsauserreg_firstname, tx_ptgsauserreg_lastname, tx_ptgsauserreg_department, tx_ptgsauserreg_state, tx_ptgsauserreg_country, tx_ptgsauserreg_mobile, tx_ptgsauserreg_isprivileged, tx_ptgsauserreg_isrestricted, tx_ptgsauserreg_customergroups_uid, tx_ptgsauserreg_defbilladdr_uid, tx_ptgsauserreg_defshipaddr_uid");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsauserreg/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","GSA Userreg: Customer Data");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsauserreg/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi2/static/","GSA Userreg: User Data");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi3']='layout,select_key';

#pi3 not used yet
#t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsauserreg/locallang_db.xml:tt_content.list_type_pi3', $_EXTKEY.'_pi3'),'list_type');
#t3lib_extMgm::addStaticFile($_EXTKEY,"pi3/static/","GSA Userreg: Customer groups");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi4']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsauserreg/locallang_db.xml:tt_content.list_type_pi4', $_EXTKEY.'_pi4'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi4/static/","GSA Userreg: Postal Addresses");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi5']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsauserreg/locallang_db.xml:tt_content.list_type_pi5', $_EXTKEY.'_pi5'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi5/static/","GSA Userreg: Search and Switch User");


t3lib_extMgm::addStaticFile($_EXTKEY,"static/","GSA Userreg: General Config");
?>
