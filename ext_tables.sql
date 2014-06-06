#
# Table structure for table 'tt_news'
#
CREATE TABLE tt_news (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  fe_group int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  datetime int(11) unsigned DEFAULT '0' NOT NULL,
  image tinyblob NOT NULL,
  imagecaption text NOT NULL,
  related int(11) DEFAULT '0' NOT NULL,
  short text NOT NULL,
  bodytext mediumtext NOT NULL,
  author tinytext NOT NULL,
  author_email tinytext NOT NULL,
  category int(11) DEFAULT '0' NOT NULL,
  links text NOT NULL,
  type tinyint(4) DEFAULT '0' NOT NULL,
  page int(11) DEFAULT '0' NOT NULL,
  keywords text NOT NULL,
  archivedate int(11) DEFAULT '0' NOT NULL,
  ext_url tinytext NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tt_news_cat'
#
CREATE TABLE tt_news_cat (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tt_news_related_mm'
#
CREATE TABLE tt_news_related_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);
