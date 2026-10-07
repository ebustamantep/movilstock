--
-- Table structure for table llx_movilstock_transfer_line
--

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
