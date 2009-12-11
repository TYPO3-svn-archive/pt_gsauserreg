#
# Table structure for table 'tx_ptgsauserreg_customergroups'
#
CREATE TABLE tx_ptgsauserreg_customergroups (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	gsa_adresse_id int(11) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY gsauid (gsa_adresse_id)
);



#
# Table structure for table 'tx_ptgsauserreg_gsansch'
#
CREATE TABLE tx_ptgsauserreg_gsansch (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	gsa_adresse_id int(11) DEFAULT '0' NOT NULL,
	gsa_ansch_id int(11) DEFAULT '0' NOT NULL,
	deprecated tinyint(4) DEFAULT '0' NOT NULL,
	company varchar(60) DEFAULT '' NOT NULL,
	salutation varchar(40) DEFAULT '' NOT NULL,
	title varchar(40) DEFAULT '' NOT NULL,
	firstname varchar(60) DEFAULT '' NOT NULL,
	lastname varchar(60) DEFAULT '' NOT NULL,
	street varchar(40) DEFAULT '' NOT NULL,
	supplement varchar(60) DEFAULT '' NOT NULL,
	zip varchar(8) DEFAULT '' NOT NULL,
	city varchar(40) DEFAULT '' NOT NULL,
	pobox varchar(9) DEFAULT '' NOT NULL,
	poboxzip varchar(8) DEFAULT '' NOT NULL,
	poboxcity varchar(40) DEFAULT '' NOT NULL,
	state varchar(60) DEFAULT '' NOT NULL,
	country char(2) DEFAULT '' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY gsauid (gsa_adresse_id)
);



#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_ptgsauserreg_gsa_adresse_id int(11) DEFAULT '0' NOT NULL,
	tx_ptgsauserreg_salutation varchar(30) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_firstname varchar(30) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_lastname varchar(40) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_department varchar(80) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_state varchar(60) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_country char(2) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_mobile varchar(30) DEFAULT '' NOT NULL,
	tx_ptgsauserreg_isprivileged tinyint(3) DEFAULT '0' NOT NULL,
	tx_ptgsauserreg_isrestricted tinyint(3) DEFAULT '0' NOT NULL,
	tx_ptgsauserreg_customergroups_uid blob NOT NULL,
	tx_ptgsauserreg_defbilladdr_uid int(11) DEFAULT '0' NOT NULL,
	tx_ptgsauserreg_defshipaddr_uid int(11) DEFAULT '0' NOT NULL,

	KEY gsauid (tx_ptgsauserreg_gsa_adresse_id)
);
