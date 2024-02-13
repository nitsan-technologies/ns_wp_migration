#
# Table structure for table 'pages'
#
CREATE TABLE pages (
  postid varchar(255) DEFAULT ''
);

#
# Table structure for table 'tx_nswpmigration_domain_model_logmanage'
#
CREATE TABLE tx_nswpmigration_domain_model_logmanage (
  pid int(11) DEFAULT '0' NOT NULL,
  number_of_records int(11) DEFAULT '0' NOT NULL,
  total_success int(11) DEFAULT '0' NOT NULL,
  total_fails int(11) DEFAULT '0' NOT NULL,
  total_update int(11) DEFAULT '0' NOT NULL,
  added_by int(11) DEFAULT '0' NOT NULL,
  redirect_json text DEFAULT NULL,
  records_log text DEFAULT NULL,
  created_date bigint(20) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_mdnewsauthorx_news_newsauthor_mm'
#
CREATE TABLE tx_nswpmigration_domain_model_logmanage_mm (

    uid_local int(11) unsigned DEFAULT '0' NOT NULL,
    uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)

);