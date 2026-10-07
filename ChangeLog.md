# CHANGELOG MODULE MOVILSTOCK FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.1

- Add "Production load" tab and history to register produced quantities of finished goods into the main warehouse
- New production reference mask, permissions (read/create/delete) and menu entries
- Lot and expiry dates are optional when recording a production
- Select the destination warehouse when recording a production (main warehouse preselected), so the tab works even if no main warehouse is configured
- Show the author name linked to the user card (instead of the raw user id) in the transfer and production histories
- Preserve configured settings (main warehouse and reference masks) when the module is disabled and re-enabled
- Add properly named llx_*.sql files so module tables are created on install
- Fix transfer history detail not showing
- Use native date pickers for history filters

## 1.0

Initial version
