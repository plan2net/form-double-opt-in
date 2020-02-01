#
# Table structure for table 'tx_formdoubleoptin_domain_model_optin'
#
CREATE TABLE tx_formdoubleoptin_domain_model_formdoubleoptin (
    email VARCHAR(255) DEFAULT '' NOT NULL,
    form_values MEDIUMTEXT,
    receiver_information TEXT,
    mailing_date INT(11) UNSIGNED DEFAULT '0' NOT NULL,
    confirmed TINYINT(4) unsigned DEFAULT '0' NOT NULL,
    confirmation_hash VARCHAR(32) DEFAULT '' NOT NULL,
    confirmation_date INT(11) UNSIGNED DEFAULT '0' NOT NULL,
    KEY hash (confirmation_hash)
);