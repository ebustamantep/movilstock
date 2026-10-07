--
-- Keys/indexes for table llx_movilstock_transfer_line
--

ALTER TABLE llx_movilstock_transfer_line ADD INDEX idx_movilstock_transfer_line_fk_transfer (fk_transfer);
ALTER TABLE llx_movilstock_transfer_line ADD INDEX idx_movilstock_transfer_line_fk_product (fk_product);
