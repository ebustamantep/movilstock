--
-- Table structure for table llx_movilstock_transfer
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
