--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--

CREATE TABLE llx_movilstock_transfer (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  ref varchar(128),
  fk_warehouse_source integer NOT NULL,
  fk_warehouse_dest integer NOT NULL,
  date_transfer datetime,
  fk_user_author integer DEFAULT 0,
  status smallint DEFAULT 1,
  entity integer DEFAULT 1 NOT NULL,
  note text,
  tms timestamp,
  import_key varchar(14)
) ENGINE=innodb;

CREATE TABLE llx_movilstock_transfer_line (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_transfer integer NOT NULL,
  fk_product integer NOT NULL,
  qty double(24,8) DEFAULT 0,
  fk_warehouse_source integer,
  fk_warehouse_dest integer,
  pmp double(24,8) DEFAULT NULL,
  entity integer DEFAULT 1 NOT NULL
) ENGINE=innodb;

CREATE TABLE llx_movilstock_production (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  ref varchar(128),
  fk_warehouse integer NOT NULL,
  date_production datetime,
  fk_user_author integer DEFAULT 0,
  status smallint DEFAULT 1,
  entity integer DEFAULT 1 NOT NULL,
  note text,
  tms timestamp,
  import_key varchar(14)
) ENGINE=innodb;

CREATE TABLE llx_movilstock_production_line (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_production integer NOT NULL,
  fk_product integer NOT NULL,
  qty double(24,8) DEFAULT 0,
  fk_warehouse integer,
  price double(24,8) DEFAULT 0,
  batch varchar(128) DEFAULT NULL,
  eatby date DEFAULT NULL,
  sellby date DEFAULT NULL,
  entity integer DEFAULT 1 NOT NULL
) ENGINE=innodb;
