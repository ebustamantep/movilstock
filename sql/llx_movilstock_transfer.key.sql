--
-- Keys/indexes for table llx_movilstock_transfer
--

ALTER TABLE llx_movilstock_transfer ADD INDEX idx_movilstock_transfer_ref (ref);
ALTER TABLE llx_movilstock_transfer ADD INDEX idx_movilstock_transfer_fk_warehouse_source (fk_warehouse_source);
ALTER TABLE llx_movilstock_transfer ADD INDEX idx_movilstock_transfer_fk_warehouse_dest (fk_warehouse_dest);
ALTER TABLE llx_movilstock_transfer ADD INDEX idx_movilstock_transfer_fk_user_author (fk_user_author);
