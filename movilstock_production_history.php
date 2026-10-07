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
 * \file       movilstock/movilstock_production_history.php
 * \ingroup    movilstock
 * \brief      History of production loadings into the main warehouse
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
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/movilstock/class/movilstockproduction.class.php');

$langs->loadLangs(array("movilstock@movilstock", "stocks", "products"));

$action = GETPOST('action', 'aZ09');
$showproduction = GETPOSTINT('id');

if (empty($user->rights->movilstock->production->read)) {
	accessforbidden();
}
$candelete = !empty($user->rights->movilstock->production->delete);

$form = new Form($db);

// Filters
$search_wh = GETPOSTINT('search_wh');
$from = GETPOST('date_start', 'alpha');
$to = GETPOST('date_end', 'alpha');

/*
 * Actions
 */
if ($action == 'delete' && $candelete && $showproduction > 0) {
	$object = new MovilStockProduction($db);
	$object->fetch($showproduction);
	if ($object->id > 0 && $object->delete($user) > 0) {
		setEventMessages($langs->trans("RecordDeletedProduction"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

llxHeader("", $langs->trans("ProductionHistory"), '', '', 0, 0, '', '', '', 'mod-movilstock page-production-history');

print load_fiche_titre($langs->trans("ProductionHistory"), '', 'stock.png');

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
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("Warehouse").'</td>';
print '<td colspan="3"><select name="search_wh" class="minwidth200">';
print '<option value="0">--</option>';
foreach ($warehouses as $id => $label) {
	$selected = ($id == $search_wh ? ' selected' : '');
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
$sql = "SELECT t.rowid, t.ref, t.fk_warehouse, t.date_production, t.status, t.fk_user_author";
$sql .= " FROM ".MAIN_DB_PREFIX."movilstock_production as t";
$sql .= " WHERE t.entity IN (".getEntity('movilstockproduction').")";
if ($search_wh > 0) {
	$sql .= " AND t.fk_warehouse = ".((int) $search_wh);
}
if ($from !== '' && $from !== null && $from != '0000-00-00') {
	$sql .= " AND t.date_production >= '".$db->idate(dol_mktime(0, 0, 0, (int)substr($from, 5, 2), (int)substr($from, 8, 2), (int)substr($from, 0, 4)))."'";
}
if ($to !== '' && $to !== null && $to != '0000-00-00') {
	$sql .= " AND t.date_production <= '".$db->idate(dol_mktime(23, 59, 59, (int)substr($to, 5, 2), (int)substr($to, 8, 2), (int)substr($to, 0, 4)))."'";
}
$sql .= " ORDER BY t.rowid DESC";
$resql = $db->query($sql);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("Warehouse").'</th>';
print '<th class="right">'.$langs->trans("Lines").'</th>';
print '<th>'.$langs->trans("AuthorUser").'</th>';
print '<th>'.$langs->trans("Status").'</th>';
if ($candelete) {
	print '<th class="center">'.$langs->trans("Actions").'</th>';
}
print '</tr>';

$showlines = ($showproduction > 0);
$usercache = array();

if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	$var = true;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$var = !$var;

		$wh = isset($warehouses[$obj->fk_warehouse]) ? $warehouses[$obj->fk_warehouse] : $obj->fk_warehouse;

		$isopen = ($showlines && $showproduction == $obj->rowid);
		$detailurl = $_SERVER["PHP_SELF"].'?id='.$obj->rowid.($search_wh ? '&search_wh='.$search_wh : '');

		// Ref is a link to view the detail of the movement
		print '<tr class="oddeven'.($var ? '' : '1').'">';
		print '<td class="nowrap"><a href="'.$detailurl.'">'.img_picto($langs->trans("Show").' '.$obj->ref, 'eye').' '.$obj->ref.'</a></td>';
		print '<td class="nowrap">'.dol_print_date($db->jdate($obj->date_production), 'dayhour').'</td>';
		print '<td>'.dol_escape_htmltag($wh).'</td>';
		print '<td class="right">';

		// Count lines for this production
		$sqlc = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."movilstock_production_line WHERE fk_production = ".((int) $obj->rowid);
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
		$authorid = (int) $obj->fk_user_author;
		if (!isset($usercache[$authorid])) {
			$usercache[$authorid] = null;
			if ($authorid > 0) {
				$usertmp = new User($db);
				if ($usertmp->fetch($authorid) > 0) {
					$usercache[$authorid] = $usertmp;
				}
			}
		}
		print '<td>';
		if (!empty($usercache[$authorid])) {
			print $usercache[$authorid]->getNomUrl(1);
		} else {
			print dol_escape_htmltag($obj->fk_user_author);
		}
		print '</td>';
		print '<td>';

		$tmp = new MovilStockProduction($db);
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
			dol_include_once('/movilstock/class/movilstockproductionline.class.php');
			$sqll = "SELECT l.rowid, l.fk_product, l.qty, l.price, l.batch, l.eatby, l.sellby, p.ref as pref, p.label as plabel";
			$sqll .= " FROM ".MAIN_DB_PREFIX."movilstock_production_line as l";
			$sqll .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
			$sqll .= " WHERE l.fk_production = ".((int) $obj->rowid);
			$sqll .= " ORDER BY l.rowid";
			$resl = $db->query($sqll);
			if ($resl) {
				$nbc = $db->num_rows($resl);
				$j = 0;
				print '<tr class="oddeven"><td colspan="'.($candelete ? 7 : 6).'">';
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre"><td class="width100">'.$langs->trans("Ref").'</td><td>'.$langs->trans("Label").'</td><th class="right width100">'.$langs->trans("Qty").'</th><td class="right width100">'.$langs->trans("UnitCost").'</td><td class="width100">'.$langs->trans("Lot").'</td><td class="width100">'.$langs->trans("Eatby").'</td><td class="width100">'.$langs->trans("Sellby").'</td></tr>';
				$total = 0;
				while ($j < $nbc) {
					$objl = $db->fetch_object($resl);
					$total += (float) $objl->qty;
					$eatby = (!empty($objl->eatby) && $objl->eatby != '0000-00-00') ? dol_print_date($db->jdate($objl->eatby), 'day') : '';
					$sellby = (!empty($objl->sellby) && $objl->sellby != '0000-00-00') ? dol_print_date($db->jdate($objl->sellby), 'day') : '';
					print '<tr class="oddeven"><td>'.$objl->pref.'</td><td>'.dol_escape_htmltag($objl->plabel).'</td><td class="right">'.price($objl->qty).'</td><td class="right">'.price($objl->price).'</td><td>'.dol_escape_htmltag($objl->batch).'</td><td>'.$eatby.'</td><td>'.$sellby.'</td></tr>';
					$j++;
				}
				print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td><td class="right">'.price($total).'</td><td colspan="4"></td></tr>';
				print '</table></td></tr>';
				$db->free($resl);
			}
		}

		$i++;
	}
	if ($num == 0) {
		print '<tr class="oddeven"><td colspan="'.($candelete ? 7 : 6).'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}
print '</table>';

llxFooter();
$db->close();
