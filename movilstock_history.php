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
 * \file       movilstock/movilstock_history.php
 * \ingroup    movilstock
 * \brief      History of stock transfers between warehouses
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

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/movilstock/class/movilstocktransfer.class.php');

$langs->loadLangs(array("movilstock@movilstock", "stocks", "products"));

$action = GETPOST('action', 'aZ09');
$showtransfer = GETPOSTINT('id');

if (empty($user->rights->movilstock->transfer->read)) {
	accessforbidden();
}
$candelete = !empty($user->rights->movilstock->transfer->delete);

$form = new Form($db);

// Filters
$search_src = GETPOSTINT('search_src');
$search_dst = GETPOSTINT('search_dst');
$from = GETPOST('date_start', 'alpha');
$to = GETPOST('date_end', 'alpha');

/*
 * Actions
 */
if ($action == 'delete' && $candelete && $showtransfer > 0) {
	$object = new MovilStockTransfer($db);
	$object->fetch($showtransfer);
	if ($object->id > 0 && $object->delete($user) > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

llxHeader("", $langs->trans("TransferHistory"), '', '', 0, 0, '', '', '', 'mod-movilstock page-history');

print load_fiche_titre($langs->trans("TransferHistory"), '', 'stock.png');

// Build warehouse label cache
$warehouses = array();
$warehouse = new Entrepot($db);
$wlist = $warehouse->list_array(1);
if (is_array($wlist)) {
	$warehouses = $wlist;
}

// Filters form
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("Filters").'</th></tr>';
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("SourceWarehouse").'</td>';
print '<td><select name="search_src" class="minwidth200">';
print '<option value="0">--</option>';
foreach ($warehouses as $id => $label) {
	$selected = ($id == $search_src ? ' selected' : '');
	print '<option value="'.$id.'"'.$selected.'>'.$label.'</option>';
}
print '</select></td>';
print '<td class="fieldrequired">'.$langs->trans("DestWarehouse").'</td>';
print '<td><select name="search_dst" class="minwidth200">';
print '<option value="0">--</option>';
foreach ($warehouses as $id => $label) {
	$selected = ($id == $search_dst ? ' selected' : '');
	print '<option value="'.$id.'"'.$selected.'>'.$label.'</option>';
}
print '</select></td></tr>';
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("DateFrom").'</td>';
print '<td><input type="date" name="date_start" value="'.dol_escape_htmltag($from).'" class="minwidth160"></td>';
print '<td>'.$langs->trans("DateTo").'</td>';
print '<td><input type="date" name="date_end" value="'.dol_escape_htmltag($to).'" class="minwidth160">&nbsp;<input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print '</table>';
print '</form>';
print '<br>';

// Main listing
$sql = "SELECT t.rowid, t.ref, t.fk_warehouse_source, t.fk_warehouse_dest, t.date_transfer, t.status, t.fk_user_author";
$sql .= " FROM ".MAIN_DB_PREFIX."movilstock_transfer as t";
$sql .= " WHERE t.entity IN (".getEntity('movilstocktransfer').")";
if ($search_src > 0) {
	$sql .= " AND t.fk_warehouse_source = ".((int) $search_src);
}
if ($search_dst > 0) {
	$sql .= " AND t.fk_warehouse_dest = ".((int) $search_dst);
}
if ($from !== '' && $from !== null && $from != '0000-00-00') {
	$sql .= " AND t.date_transfer >= '".$db->idate(dol_mktime(0, 0, 0, (int)substr($from, 5, 2), (int)substr($from, 8, 2), (int)substr($from, 0, 4)))."'";
}
if ($to !== '' && $to !== null && $to != '0000-00-00') {
	$sql .= " AND t.date_transfer <= '".$db->idate(dol_mktime(23, 59, 59, (int)substr($to, 5, 2), (int)substr($to, 8, 2), (int)substr($to, 0, 4)))."'";
}
$sql .= " ORDER BY t.rowid DESC";
$resql = $db->query($sql);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("SourceWarehouse").'</th>';
print '<th>'.$langs->trans("DestWarehouse").'</th>';
print '<th class="right">'.$langs->trans("Lines").'</th>';
print '<th>'.$langs->trans("AuthorUser").'</th>';
print '<th>'.$langs->trans("Status").'</th>';
if ($candelete) {
	print '<th class="center">'.$langs->trans("Actions").'</th>';
}
print '</tr>';

$showlines = ($showtransfer > 0);

if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	$var = true;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$var = !$var;

		$src = isset($warehouses[$obj->fk_warehouse_source]) ? $warehouses[$obj->fk_warehouse_source] : $obj->fk_warehouse_source;
		$dst = isset($warehouses[$obj->fk_warehouse_dest]) ? $warehouses[$obj->fk_warehouse_dest] : $obj->fk_warehouse_dest;

		$isopen = ($showlines && $showtransfer == $obj->rowid);
		$detailurl = $_SERVER["PHP_SELF"].'?id='.$obj->rowid.($search_src ? '&search_src='.$search_src : '').($search_dst ? '&search_dst='.$search_dst : '');

		// Ref is a link to view the detail of the movement
		print '<tr class="oddeven'.($var ? '' : '1').'">';
		print '<td class="nowrap"><a href="'.$detailurl.'">'.img_picto($langs->trans("Show").' '.$obj->ref, 'eye').' '.$obj->ref.'</a></td>';
		print '<td class="nowrap">'.dol_print_date($db->jdate($obj->date_transfer), 'dayhour').'</td>';
		print '<td>'.dol_escape_htmltag($src).'</td>';
		print '<td>'.dol_escape_htmltag($dst).'</td>';
		print '<td class="right">';

		// Count lines for this transfer
		$sqlc = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."movilstock_transfer_line WHERE fk_transfer = ".((int) $obj->rowid);
		$resc = $db->query($sqlc);
		$nb = 0;
		if ($resc) {
			$objc = $db->fetch_object($resc);
			$nb = (int) $objc->nb;
			$db->free($resc);
		}

		if ($nb > 0) {
			$toggle = '<a href="'.$detailurl.'">'.($isopen ? $langs->trans("Hide") : $langs->trans("Show")).'</a>';
		} else {
			$toggle = '';
		}
		print $nb.$toggle;
		print '</td>';
		print '<td>'.dol_escape_htmltag($obj->fk_user_author).'</td>';
		print '<td>';

		$tmp = new MovilStockTransfer($db);
		$tmp->status = $obj->status;
		print $tmp->getLibStatut(3);
		print '</td>';
		if ($candelete) {
			print '<td class="center" nowrap>';
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$obj->rowid.'&token='.newToken().'" class="delete">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
			print '</td>';
		}
		print '</tr>';

		// Detail lines
		if ($isopen && $nb > 0) {
			dol_include_once('/movilstock/class/movilstocktransferline.class.php');
			$sqll = "SELECT l.rowid, l.fk_product, l.qty, p.ref as pref, p.label as plabel";
			$sqll .= " FROM ".MAIN_DB_PREFIX."movilstock_transfer_line as l";
			$sqll .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
			$sqll .= " WHERE l.fk_transfer = ".((int) $obj->rowid);
			$sqll .= " ORDER BY l.rowid";
			$resl = $db->query($sqll);
			if ($resl) {
				$nbc = $db->num_rows($resl);
				$j = 0;
				print '<tr class="oddeven"><td colspan="'.($candelete ? 8 : 7).'">';
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre"><td class="width100">'.$langs->trans("Ref").'</td><td>'.$langs->trans("Label").'</td><th class="right width100">'.$langs->trans("SourceWarehouse").'</th><td class="right width100">'.$langs->trans("DestWarehouse").'</td><td class="right width100">'.$langs->trans("Qty").'</td></tr>';
				$total = 0;
				while ($j < $nbc) {
					$objl = $db->fetch_object($resl);
					$total += (float) $objl->qty;
					print '<tr class="oddeven"><td>'.$objl->pref.'</td><td>'.dol_escape_htmltag($objl->plabel).'</td><td class="right">'.dol_escape_htmltag($src).'</td><td class="right">'.dol_escape_htmltag($dst).'</td><td class="right">'.price($objl->qty).'</td></tr>';
					$j++;
				}
				print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Total").'</td><td class="right">'.price($total).'</td></tr>';
				print '</table></td></tr>';
				$db->free($resl);
			}
		}

		$i++;
	}
	if ($num == 0) {
		print '<tr class="oddeven"><td colspan="'.($candelete ? 8 : 7).'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}
print '</table>';

llxFooter();
$db->close();
