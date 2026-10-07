--
-- Keys/indexes for table llx_movilstock_production
--

ALTER TABLE llx_movilstock_production ADD INDEX idx_movilstock_production_ref (ref);
ALTER TABLE llx_movilstock_production ADD INDEX idx_movilstock_production_fk_warehouse (fk_warehouse);
ALTER TABLE llx_movilstock_production ADD INDEX idx_movilstock_production_fk_user_author (fk_user_author);
