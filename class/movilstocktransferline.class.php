<?php
/* Copyright (C) 2026		SuperAdmin
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
 * \file        movilstock/class/movilstocktransferline.class.php
 * \ingroup     movilstock
 * \brief       CRUD class file for MovilStockTransferLine (Create/Read)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class for MovilStockTransferLine
 */
class MovilStockTransferLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'movilstocktransferline';

	/**
	 * @var string Name of table without prefix where object is stored.
	 */
	public $table_element = 'movilstock_transfer_line';

	/**
	 * @var string String with name of icon for movilstocktransferline.
	 */
	public $picto = 'stock';

	/**
	 * @var array<string,array{type:string,label:string,enabled:int|string,position:int,notnull?:int,visible:int|string,index?:int,default?:string}>
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'index' => 1),
		'fk_transfer' => array('type' => 'integer:MovilStockTransfer:movilstock/class/movilstocktransfer.class.php', 'label' => 'MovilStockTransfer', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 0, 'index' => 1),
		'fk_product' => array('type' => 'integer:Product:product/class/product.class.php', 'label' => 'Product', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'qty' => array('type' => 'double(24,8)', 'label' => 'Qty', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 1, 'default' => '0'),
		'fk_warehouse_source' => array('type' => 'integer', 'label' => 'SourceWarehouse', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => 1),
		'fk_warehouse_dest' => array('type' => 'integer', 'label' => 'DestWarehouse', 'enabled' => 1, 'position' => 41, 'notnull' => 0, 'visible' => 1),
		'pmp' => array('type' => 'double(24,8)', 'label' => 'PMP', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 2, 'default' => '1', 'notnull' => 1, 'visible' => 0, 'index' => 1),
	);

	public $rowid;
	public $fk_transfer;
	public $fk_product;
	public $qty;
	public $fk_warehouse_source;
	public $fk_warehouse_dest;
	public $pmp;
	public $entity;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;
		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}
	}

	/**
	 * Create line into database
	 *
	 * @param  User $user      User that creates
	 * @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		if (empty($this->entity)) {
			$this->entity = $conf->entity;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}
}
