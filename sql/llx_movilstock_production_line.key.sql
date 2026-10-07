--
-- Keys/indexes for table llx_movilstock_production_line
--

ALTER TABLE llx_movilstock_production_line ADD INDEX idx_movilstock_production_line_fk_production (fk_production);
ALTER TABLE llx_movilstock_production_line ADD INDEX idx_movilstock_production_line_fk_product (fk_product);
