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
 * \file       movilstock/movilstockindex.php
 * \ingroup    movilstock
 * \brief      MovilStock main page: transfer stock and view stock per warehouse
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/movilstock/class/movilstocktransfer.class.php');
dol_include_once('/movilstock/class/movilstocktransferline.class.php');

$langs->loadLangs(array("movilstock@movilstock", "stocks", "products"));

$action = GETPOST('action', 'aZ09');
$tab = GETPOST('tab', 'aZ09');
if (empty($tab)) {
	$tab = 'transfer';
}
$fk_warehouse_source = GETPOSTINT('fk_warehouse_source');
$fk_warehouse_dest = GETPOSTINT('fk_warehouse_dest');

// Security check - must be internal user with read permission
if (empty($user->rights->movilstock->transfer->read)) {
	accessforbidden();
}
$cancreate = !empty($user->rights->movilstock->transfer->create);

$mainwarehouse = getDolGlobalInt('MOVILSTOCK_ID_WAREHOUSE_MAIN');

// Default source = main warehouse if set
if (empty($fk_warehouse_source) && $mainwarehouse > 0) {
	$fk_warehouse_source = $mainwarehouse;
}

// List of all open warehouses
$entrepot = new Entrepot($db);
$warehouses = $entrepot->list_array(1);

// Show options of warehouse destination (exclude source)
$destwarehouses = array();
if (is_array($warehouses)) {
	foreach ($warehouses as $id => $label) {
		if ($id != $fk_warehouse_source) {
			$destwarehouses[$id] = $label;
		}
	}
}

$form = new Form($db);
$error = 0;

/*
 * Actions
 */

if ($action == 'confirm' && !empty($user->rights->movilstock->transfer->create)) {
	if (empty($fk_warehouse_source) || empty($fk_warehouse_dest)) {
		setEventMessages($langs->trans("WarehouseRequired"), null, 'errors');
		$error++;
	} elseif ($fk_warehouse_source == $fk_warehouse_dest) {
		setEventMessages($langs->trans("WarehouseSameForbidden"), null, 'errors');
		$error++;
	}

	// Collect submitted lines: fk_product, qty
	$lines = array();
	if (!$error) {
		$quantities = GETPOST('qty_', 'array');
		if (!is_array($quantities)) {
			$quantities = array();
		}
		foreach ($quantities as $fkproduct => $qval) {
			$qty = price2num($qval);
			$fkproduct = (int) $fkproduct;
			if ($fkproduct > 0 && $qty > 0) {
				$lines[$fkproduct] = $qty;
			}
		}
	}

	if (!$error && empty($lines)) {
		setEventMessages($langs->trans("NoQtyToTransfer"), null, 'errors');
		$error++;
	}

	if (!$error) {
		$db->begin();

		$object = new MovilStockTransfer($db);
		$object->fk_warehouse_source = $fk_warehouse_source;
		$object->fk_warehouse_dest = $fk_warehouse_dest;
		$object->status = MovilStockTransfer::STATUS_DONE;
		$object->date_transfer = dol_now();
		$object->fk_user_author = $user->id;
		$object->ref = $object->getNextNumRef();

		$transferid = $object->create($user);
		if ($transferid <= 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			foreach ($lines as $fkproduct => $qty) {
				$product = new Product($db);
				$result = $product->fetch($fkproduct);
				if ($result < 0) {
					$error++;
					setEventMessages($product->error, $product->errors, 'errors');
					break;
				}

				$product->load_stock('novirtual');
				$avail = 0;
				if (isset($product->stock_warehouse[$fk_warehouse_source]->real)) {
					$avail = (float) $product->stock_warehouse[$fk_warehouse_source]->real;
				}
				if ($qty > $avail) {
					$error++;
					setEventMessages($langs->trans("NotEnoughStock", $product->ref, $avail, $qty), null, 'errors');
					break;
				}

				// Record line
				$line = new MovilStockTransferLine($db);
				$line->fk_transfer = $transferid;
				$line->fk_product = $fkproduct;
				$line->qty = $qty;
				$line->fk_warehouse_source = $fk_warehouse_source;
				$line->fk_warehouse_dest = $fk_warehouse_dest;
				$line->entity = getEntity('movilstocktransfer');
				$lineresult = $line->create($user);
				if ($lineresult <= 0) {
					$error++;
					setEventMessages($line->error, $line->errors, 'errors');
					break;
				}

				$stocklabel = $langs->trans("MovilStockTransfer").' '.$object->ref;

				// Decrease source warehouse
				$result1 = $product->correct_stock($user, $fk_warehouse_source, $qty, 1, $stocklabel, $product->pmp, '', 'movilstock', $transferid);
				if ($result1 < 0) {
					$error++;
					setEventMessages($product->error, $product->errors, 'errors');
					break;
				}

				// Increase destination warehouse
				$result2 = $product->correct_stock($user, $fk_warehouse_dest, $qty, 0, $stocklabel, $product->pmp, '', 'movilstock', $transferid);
				if ($result2 < 0) {
					$error++;
					setEventMessages($product->error, $product->errors, 'errors');
					break;
				}
			}
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("TransferDone"), null, 'mesgs');
			header("Location: ".dol_buildpath('/movilstock/movilstock_history.php', 1));
			exit;
		} else {
			$db->rollback();
		}
	}
}

/*
 * View
 */

llxHeader("", $langs->trans("ModuleMovilStockName"), '', '', 0, 0, '', '', '', 'mod-movilstock page-index');

print load_fiche_titre($langs->trans("ModuleMovilStockName"), '', 'stock.png');

// Build tabs header
$head = array();
$head[0][0] = $_SERVER["PHP_SELF"].'?tab=transfer';
$head[0][1] = $langs->trans("MoveStockTransfer");
$head[0][2] = 'transfer';
$head[1][0] = $_SERVER["PHP_SELF"].'?tab=stock';
$head[1][1] = $langs->trans("StockByWarehouse");
$head[1][2] = 'stock';

print dol_get_fiche_head($head, $tab, $langs->trans("ModuleMovilStockName"), -1, "movilstock@movilstock", -1, '');

if ($tab == 'stock') {
	// ------------------------------------------------------------
	// Tab: Stock per warehouse (matrix products x warehouses)
	// ------------------------------------------------------------
	$filter_warehouse = GETPOSTINT('filter_warehouse');
	$search = GETPOST('search_product', 'alpha');
	$search = dol_string_nospecial($search);

	print '<div class="fichecenter"><div class="fichethirdleft">';
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="tab" value="stock">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("Filters").'</th></tr>';
	print '<tr class="oddeven"><td class="fieldrequired width15">'.$langs->trans("Warehouse").'</td>';
	print '<td colspan="3"><select name="filter_warehouse" class="minwidth200 centpercent">';
	print '<option value="0">'.$langs->trans("All").'</option>';
	foreach ($warehouses as $id => $label) {
		$selected = ($id == $filter_warehouse ? ' selected' : '');
		print '<option value="'.$id.'"'.$selected.'>'.$label.'</option>';
	}
	print '</select></td></tr>';
	print '<tr class="oddeven"><td class="fieldrequired width15">'.$langs->trans("Product").'</td>';
	print '<td colspan="3"><div class="floatleft inline-block">';
	print '<input type="text" name="search_product" value="'.dol_escape_htmltag($search).'" class="minwidth200">&nbsp;<input type="submit" class="button" value="'.$langs->trans("Search").'">';
	print '</div></td></tr>';
	print '</table>';
	print '</form>';
	print '</div>';

	if (empty($warehouses) || !is_array($warehouses)) {
		print '<div class="warning">'.$langs->trans("NoWarehouse").'</div>';
	} else {
		// Determine which warehouses are shown as columns
		$shownwarehouses = array();
		if ($filter_warehouse > 0 && isset($warehouses[$filter_warehouse])) {
			$shownwarehouses[$filter_warehouse] = $warehouses[$filter_warehouse];
		} else {
			$shownwarehouses = $warehouses;
		}

		// Build pivot: products (rows) x warehouses (cols)
		$sql = "SELECT ps.fk_product, ps.fk_entrepot, ps.reel";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
		$sql .= " WHERE p.entity IN (".getEntity('product').") AND p.tosell = 1";
		if ($filter_warehouse > 0) {
			$sql .= " AND ps.fk_entrepot = ".((int) $filter_warehouse);
		}
		if ($search !== '') {
			$sql .= " AND (p.ref LIKE '".$db->escape($search)."%' OR p.label LIKE '%".$db->escape($search)."%')";
		}
		$sql .= " ORDER BY p.ref";
		$resql = $db->query($sql);

		$stock = array();
		$products = array();

		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$pid = (int) $obj->fk_product;
				$wid = (int) $obj->fk_entrepot;
				if (!isset($stock[$pid])) {
					$stock[$pid] = array();
				}
				if (!isset($stock[$pid][$wid])) {
					$stock[$pid][$wid] = 0;
				}
				$stock[$pid][$wid] += (float) $obj->reel;
			}
			$db->free($resql);
		}

		if (!empty($stock)) {
			$sqlp = "SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product";
			$sqlp .= " WHERE rowid IN (".implode(',', array_map('intval', array_keys($stock))).")";
			$resp = $db->query($sqlp);
			if ($resp) {
				while ($objp = $db->fetch_object($resp)) {
					$products[(int) $objp->rowid] = array('ref' => $objp->ref, 'label' => $objp->label);
				}
				$db->free($resp);
			}
		}

		$nbcols = 2 + count($shownwarehouses);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
		foreach ($shownwarehouses as $wid => $wlabel) {
			print '<th class="right">'.dol_escape_htmltag($wlabel).'</th>';
		}
		print '</tr>';

		if (empty($stock)) {
			print '<tr class="oddeven"><td colspan="'.$nbcols.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		} else {
			$i = 0;
			$var = true;
			foreach ($stock as $pid => $bywarehouse) {
				$var = !$var;
				$pref = isset($products[$pid]['ref']) ? $products[$pid]['ref'] : $pid;
				$plabel = isset($products[$pid]['label']) ? $products[$pid]['label'] : '';

				print '<tr class="oddeven'.($var ? '' : '1').'">';
				print '<td class="nowrap">'.$pref.'</td>';
				print '<td>'.dol_escape_htmltag($plabel).'</td>';
				foreach ($shownwarehouses as $wid => $wlabel) {
					$val = isset($bywarehouse[$wid]) ? $bywarehouse[$wid] : 0;
					$css = ($val > 0 ? '' : 'opacitymedium');
					print '<td class="right '.$css.'">'.price($val).'</td>';
				}
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
	}
} else {
	// ------------------------------------------------------------
	// Tab: Transfer between warehouses
	// ------------------------------------------------------------
	if (empty($warehouses) || !is_array($warehouses)) {
		print '<div class="warning">'.$langs->trans("NoWarehouse").'</div>';
	} else {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="confirm">';
		print '<input type="hidden" name="tab" value="transfer">';

		// Row 1: warehouse selectors
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("TransferParams").'</th></tr>';

		// Source warehouse
		print '<tr class="oddeven"><td class="fieldrequired width15">'.$langs->trans("SourceWarehouse").'</td>';
		print '<td colspan="3"><select name="fk_warehouse_source" id="fk_warehouse_source" class="centpercent">';
		print '<option value="0">--</option>';
		foreach ($warehouses as $id => $label) {
			$selected = ($id == $fk_warehouse_source ? ' selected' : '');
			print '<option value="'.$id.'"'.$selected.'>'.$label.'</option>';
		}
		print '</select></td></tr>';

		// Destination warehouse
		print '<tr class="oddeven"><td class="fieldrequired width15">'.$langs->trans("DestWarehouse").'</td>';
		print '<td colspan="3"><select name="fk_warehouse_dest" id="fk_warehouse_dest" class="centpercent">';
		print '<option value="0">--</option>';
		foreach ($destwarehouses as $id => $label) {
			$selected = ($id == $fk_warehouse_dest ? ' selected' : '');
			print '<option value="'.$id.'"'.$selected.'>'.$label.'</option>';
		}
		print '</select></td></tr>';

		print '</table>';
		print '<br>';

		// Row 2: products of the source
		if ($fk_warehouse_source > 0) {
			$sql = "SELECT p.rowid, p.ref, p.label, ps.reel";
			$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON ps.fk_product = p.rowid AND ps.fk_entrepot = ".((int) $fk_warehouse_source);
			$sql .= " WHERE p.entity IN (".getEntity('product').") AND p.tosell = 1 AND ps.reel > 0";
			$sql .= " ORDER BY p.ref";
			$resql = $db->query($sql);
		} else {
			$resql = false;
		}

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
		print '<th class="right">'.$langs->trans("AvailableStock").'</th>';
		print '<th class="center">'.$langs->trans("QtyToTransfer").'</th>';
		print '</tr>';

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$var = true;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$var = !$var;

				$stock = (float) $obj->reel;
				$max = floor($stock);

				print '<tr class="oddeven'.($var ? '' : '1').'">';
				print '<td class="nowrap">'.$obj->ref.'</td>';
				print '<td>'.dol_escape_htmltag($obj->label).'</td>';
				print '<td class="right">'.price($stock).'</td>';
				print '<td class="center"><input type="number" name="qty_['.$obj->rowid.']" min="0" max="'.$max.'" step="any" value="0" class="width50"></td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("SelectSourceFirst").'</td></tr>';
		}
		print '</table>';

		if ($cancreate) {
			print '<div class="center marginbottomonly">';
			print '<input type="submit" class="button" value="'.$langs->trans("Send").'">';
			print '</div>';
		} else {
			print '<div class="center marginbottomonly opacitymedium">'.$langs->trans("NoPermissionToCreate").'</div>';
		}
		print '</form>';
	}
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
