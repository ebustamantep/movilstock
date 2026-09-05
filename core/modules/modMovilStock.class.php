<?php
/* Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019	Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2026		SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   movilstock     Module MovilStock
 *  \brief      MovilStock module descriptor.
 *
 *  \file       htdocs/movilstock/core/modules/modMovilStock.class.php
 *  \ingroup    movilstock
 *  \brief      Description and activation file for module MovilStock
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module MovilStock
 */
class modMovilStock extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		$this->numero = 958000;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'movilstock';

		// Family can be 'base', 'crm', 'financial', 'hr', 'projects', 'products', 'ecm', 'technic', 'interface', 'other', ...
		$this->family = "other";
		$this->module_position = '90';

		// Module label (no space allowed)
		$this->name = preg_replace('/^mod/i', '', get_class($this)); // = MovilStock

		// Module description
		$this->description = "MovilStockDescription";
		$this->descriptionlong = "MovilStockDescription";

		// Author
		$this->editor_name = 'Edgar Bustamante';
		$this->editor_url = '';
		$this->editor_squarred_logo = '';

		$this->version = '1.0';

		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name); // MAIN_MODULE_MOVILSTOCK

		// Name of image file used for this module.
		$this->picto = 'stock';

		// Define some features supported by module
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0
		);

		// Data directories to create when module is enabled.
		$this->dirs = array("/movilstock/temp");

		// Config pages.
		$this->config_page_url = array("setup.php@movilstock");

		// Dependencies
		$this->hidden = getDolGlobalInt('MODULE_MOVILSTOCK_DISABLED');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("movilstock@movilstock");

		// Prerequisites
		$this->phpmin = array(7, 2);
		$this->need_dolibarr_version = array(19, -3);
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		// Constants
		$this->const = array(
			1 => array('MOVILSTOCK_ID_WAREHOUSE_MAIN', 'chaine', '', 'Main warehouse that supplies POS terminals', 0, 'current', 1),
			2 => array('MOVILSTOCK_TRANSFER_NUMBER', 'chaine', '/TS-{y}{m}{d}-{0000}', 'Mask for transfer references', 0, 'current', 1),
		);

		if (!isModEnabled("movilstock")) {
			$conf->movilstock = new stdClass();
			$conf->movilstock->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		// Permissions provided by this module
		// 9580001 = read, 9580002 = create, 9580003 = delete
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", (1 * 10) + 1); // 9580001
		$this->rights[$r][1] = 'Read transfers of MovilStock';
		$this->rights[$r][4] = 'transfer';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", (1 * 10) + 2); // 9580002
		$this->rights[$r][1] = 'Create transfers of MovilStock';
		$this->rights[$r][4] = 'transfer';
		$this->rights[$r][5] = 'create';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", (1 * 10) + 3); // 9580003
		$this->rights[$r][1] = 'Delete transfers of MovilStock';
		$this->rights[$r][4] = 'transfer';
		$this->rights[$r][5] = 'delete';
		$r++;

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		$this->menu[$r++] = array(
			'fk_menu' => '',
			'type' => 'top',
			'titre' => 'ModuleMovilStockName',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'movilstock',
			'leftmenu' => '',
			'url' => '/movilstock/movilstockindex.php',
			'langs' => 'movilstock@movilstock',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('movilstock')",
			'perms' => '1',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=movilstock',
			'type' => 'left',
			'titre' => 'MoveStockTransfer',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'movilstock',
			'leftmenu' => 'movilstock_transfer',
			'url' => '/movilstock/movilstockindex.php',
			'langs' => 'movilstock@movilstock',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('movilstock')",
			'perms' => '$user->hasRight("movilstock", "transfer", "read")',
			'target' => '',
			'user' => 2,
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=movilstock',
			'type' => 'left',
			'titre' => 'TransferHistory',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'movilstock',
			'leftmenu' => 'movilstock_history',
			'url' => '/movilstock/movilstock_history.php',
			'langs' => 'movilstock@movilstock',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('movilstock')",
			'perms' => '$user->hasRight("movilstock", "transfer", "read")',
			'target' => '',
			'user' => 2,
		);
	}

	/**
	 *  Function called when module is enabled.
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          	1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Create tables of module at module activation
		$result = $this->_load_tables('/movilstock/sql/');
		if ($result < 0) {
			return -1;
		}

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
