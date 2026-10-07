--
-- Table structure for table llx_movilstock_production_line
--

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
