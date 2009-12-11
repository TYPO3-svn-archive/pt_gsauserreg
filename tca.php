<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_ptgsauserreg_customergroups'] = array(
	'ctrl' => $TCA['tx_ptgsauserreg_customergroups']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,endtime,title,gsa_adresse_id'
	),
	'feInterface' => $TCA['tx_ptgsauserreg_customergroups']['feInterface'],
	'columns' => array(
		'hidden' => array(		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => array(		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array(		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'title' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_customergroups.title',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',
			)
		),
		'gsa_adresse_id' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_customergroups.gsa_adresse_id',		
            'config' => array(
                'type' => 'none',    
                'size' => '6',    
            )
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;1;;1-1-1, title;;;;2-2-2, gsa_adresse_id;;;;3-3-3')
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime')
	)
);



$TCA['tx_ptgsauserreg_gsansch'] = array(
	'ctrl' => $TCA['tx_ptgsauserreg_gsansch']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,deprecated,gsa_adresse_id,gsa_ansch_id,company,salutation,title,firstname,lastname,street,supplement,zip,city,pobox,poboxzip,poboxcity,state,country'
	),
	'feInterface' => $TCA['tx_ptgsauserreg_gsansch']['feInterface'],
	'columns' => array(
		'hidden' => array(		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'deprecated' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.deprecated',		
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'gsa_adresse_id' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.gsa_adresse_id',		 
            'config' => array(
                'type' => 'none',    
                'size' => '6',    
            )
		),
		'gsa_ansch_id' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.gsa_ansch_id',		
            'config' => array(
                'type' => 'none',    
                'size' => '6',    
            )
		),
		'company' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.company',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'salutation' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.salutation',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'title' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.title',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'firstname' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.firstname',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'lastname' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.lastname',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'street' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.street',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'supplement' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.supplement',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'zip' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.zip',		
			'config' => array(
				'type' => 'none',	
				'size' => '8',	
			)
		),
		'city' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.city',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'pobox' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.pobox',		
			'config' => array(
				'type' => 'none',	
				'size' => '9',	
			)
		),
		'poboxzip' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.poboxzip',		
			'config' => array(
				'type' => 'none',	
				'size' => '8',	
			)
		),
		'poboxcity' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.poboxcity',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'state' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.state',		
			'config' => array(
				'type' => 'none',	
				'size' => '40',	
			)
		),
		'country' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:pt_gsauserreg/locallang_db.xml:tx_ptgsauserreg_gsansch.country',		
			'config' => array(
				'type' => 'none',	
				'size' => '5',	
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;1;;1-1-1, deprecated, gsa_adresse_id, gsa_ansch_id, company, salutation, title;;;;2-2-2, firstname;;;;3-3-3, lastname, street, supplement, zip, city, pobox, poboxzip, poboxcity, state, country')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>
