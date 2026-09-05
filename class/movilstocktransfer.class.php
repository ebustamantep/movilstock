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
 * \file        movilstock/class/movilstocktransfer.class.php
 * \ingroup     movilstock
 * \brief       CRUD class file for MovilStockTransfer (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for MovilStockTransfer
 */
class MovilStockTransfer extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'movilstocktransfer';

	/**
	 * @var string Name of table without prefix where object is stored.
	 */
	public $table_element = 'movilstock_transfer';

	/**
	 * @var string Name of subtable line
	 */
	public $table_element_line = 'movilstock_transfer_line';

	/**
	 * @var string Field name which stores ID of parent key if this object has a parent
	 */
	public $fk_element = 'fk_transfer';

	/**
	 * @var string[] List of child tables to delete on cascade.
	 */
	protected $childtablesoncascade = array('movilstock_transfer_line');

	/**
	 * @var string String with name of icon for movilstocktransfer.
	 */
	public $picto = 'stock';

	const STATUS_DONE = 1;
	const STATUS_ERROR = 0;

	/**
	 * @var array<string,array{type:string,label:string,enabled:int|string,position:int,notnull?:int,visible:int|string,index?:int,default?:string,help?:string}>
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'index' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 2, 'default' => '1', 'notnull' => 1, 'visible' => 0, 'index' => 1),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 0, 'visible' => 4, 'default' => '(PROV)', 'index' => 1),
		'fk_warehouse_source' => array('type' => 'integer', 'label' => 'SourceWarehouse', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'fk_warehouse_dest' => array('type' => 'integer', 'label' => 'DestWarehouse', 'enabled' => 1, 'position' => 21, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'date_transfer' => array('type' => 'datetime', 'label' => 'DateTransfer', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 1),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => 1, 'index' => 1),
		'status' => array('type' => 'smallint', 'label' => 'Status', 'enabled' => 1, 'position' => 50, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array('0' => 'Error', '1' => 'Done')),
		'note' => array('type' => 'text', 'label' => 'Note', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 3),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 500, 'notnull' => 0, 'visible' => -2),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 1000, 'notnull' => -1, 'visible' => -2),
	);

	/**
	 * @var int
	 */
	public $rowid;
	public $ref;
	public $fk_warehouse_source;
	public $fk_warehouse_dest;
	public $date_transfer;
	public $fk_user_author;
	public $status;
	public $note;
	public $lines;

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
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		$this->status = (int) $this->status;
		if (!$this->status) {
			$this->status = self::STATUS_DONE;
		}
		if (empty($this->date_transfer)) {
			$this->date_transfer = dol_now();
		}
		// date_transfer is a 'datetime' field so it needs an SQL datetime string
		if (!preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $this->date_transfer)) {
			$this->date_transfer = $this->db->idate($this->date_transfer);
		}
		if (empty($this->fk_user_author)) {
			$this->fk_user_author = $user->id;
		}
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
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		dol_include_once('/movilstock/class/movilstocktransferline.class.php');
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}

	/**
	 * Get next reference using the mask MOVILSTOCK_TRANSFER_NUMBER
	 * Supports placeholders {y}, {m}, {d}, {h}, {i}, {s}, {0000} (increment per day)
	 *
	 * @return string Object free reference
	 */
	public function getNextNumRef()
	{
		global $conf, $db;

		$mask = getDolGlobalString('MOVILSTOCK_TRANSFER_NUMBER', '/TS-{y}{m}{d}-{0000}');
		$now = dol_now();

		$seq = 1;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref, LOCATE('-', ref) + 1, 4) AS UNSIGNED)) as maxseq";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE entity IN (".getEntity('movilstocktransfer').")";
		$sql .= " AND ref LIKE '".$db->escape(date('Ymd', $now))."%'";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj && $obj->maxseq > 0) {
				$seq = ((int) $obj->maxseq) + 1;
			}
			$db->free($resql);
		}

		$seqstr = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

		$mask = str_replace('{y}', date('Y', $now), $mask);
		$mask = str_replace('{m}', date('m', $now), $mask);
		$mask = str_replace('{d}', date('d', $now), $mask);
		$mask = str_replace('{h}', date('H', $now), $mask);
		$mask = str_replace('{i}', date('i', $now), $mask);
		$mask = str_replace('{s}', date('s', $now), $mask);
		$mask = str_replace('{0000}', $seqstr, $mask);

		return $mask;
	}

	/**
	 * Return a link to the object card
	 *
	 * @param int     $withpicto Include picto in link
	 * @param string  $option    On what the link point to
	 * @param int     $notooltip 1=Disable tooltip
	 * @return string String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0)
	{
		global $langs;

		$result = '';
		$label = $langs->trans("MovilStockTransfer").' '.$this->ref;
		$url = dol_buildpath('/movilstock/movilstock_history.php', 1).'?id='.$this->id;

		$linkstart = '<a href="'.$url.'" title="'.dolPrintHTMLForAttribute($label).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object($label, $this->picto);
		}
		$result .= $this->ref;
		$result .= $linkend;

		return $result;
	}

	/**
	 * Return label of the status
	 *
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label
	 * @return string Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the status
	 *
	 * @param int $status Id status
	 * @param int $mode   0=long label, 1=short label, 2=Picto + short label
	 * @return string Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$this->labelStatus[self::STATUS_DONE] = $langs->trans('Done');
			$this->labelStatus[self::STATUS_ERROR] = $langs->trans('Error');
			$this->labelStatusShort[self::STATUS_DONE] = $langs->trans('Done');
			$this->labelStatusShort[self::STATUS_ERROR] = $langs->trans('Error');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_ERROR) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 * Initialise object with example values
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		return $this->initAsSpecimenCommon();
	}
}
