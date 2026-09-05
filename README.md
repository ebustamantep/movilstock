# MOVILSTOCK FOR [DOLIBARR ERP & CRM](https://www.dolibarr.org)

## Features

Transfer merchandise between warehouses. The operator selects a source warehouse and a destination warehouse, enters the quantities of the products to move and confirms; the stock is decreased in the source warehouse and increased in the destination warehouse, and each transfer is recorded in the history.

## Installation

Prerequisites: You must have Dolibarr ERP & CRM software installed. You can download it from [Dolistore.org](https://www.dolibarr.org).

### From the ZIP file and GUI interface

If the module is a ready-to-deploy zip file, go to menu `Home> Setup> Modules> Deploy external module` and upload the zip file.

### Final steps

Using your browser:

- Log into Dolibarr as a super-administrator
- Go to "Setup"> "Modules"
- You should now be able to find and enable the module
- Go to "Setup"> "Modules"> "MovilStock" and configure the main warehouse (used by default as the source of transfers) and the reference mask
- Assign the `MovilStock -> transfer` permission to the users who should manage transfers

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readme's are licensed under [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).
