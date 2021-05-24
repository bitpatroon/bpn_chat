#
# Table structure for table 'tx_bpnchat_domain_model_message'
#
CREATE TABLE tx_bpnchat_domain_model_message (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT 0 NOT NULL,
    tstamp int(11) DEFAULT 0 NOT NULL,
    crdate int(11) DEFAULT 0 NOT NULL,
    cruser_id int(11) DEFAULT 0 NOT NULL,
    deleted tinyint(4) DEFAULT 0 NOT NULL,
    hidden tinyint(4) DEFAULT 0 NOT NULL,

    sender    int(11) default '0' NOT NULL,
    receivers int(11) default '0' NOT NULL,
    message   text,
    delivered int(11) default '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);
