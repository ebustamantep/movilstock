<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
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
 * \file    movilstock/admin/setup.php
 * \ingroup movilstock
 * \brief   MovilStock setup page.
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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/movilstock.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "movilstock@movilstock"));

// Initialize a technical object to manage hooks of page.
$hookmanager->initHooks(array('movilstocksetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);


// Enter here all parameters in your setup page

// List of warehouses for main warehouse selector
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
$entrepot = new Entrepot($db);
$listwarehouses = $entrepot->list_array(1);
$warehousesoptions = array();
if (is_array($listwarehouses)) {
	foreach ($listwarehouses as $id => $label) {
		$warehousesoptions[$id] = $label;
	}
}

// Setup conf for main warehouse (default source of transfers)
$item = $formSetup->newItem('MOVILSTOCK_ID_WAREHOUSE_MAIN');
$item->nameText = $langs->trans("MainWarehouse");
$item->helpText = $langs->trans("MainWarehouseHelp");
if (empty($warehousesoptions)) {
	$item->setAsString();
	$item->fieldAttr['placeholder'] = '0';
} else {
	$item->setAsSelect($warehousesoptions);
}

// Setup conf for transfer reference mask
$item = $formSetup->newItem('MOVILSTOCK_TRANSFER_NUMBER');
$item->nameText = $langs->trans("TransferRefMask");
$item->helpText = $langs->trans("TransferRefMaskHelp");
$item->fieldAttr['placeholder'] = '/TS-{y}{m}{d}-{0000}';

// End of definition of parameters

$setupnotempty += count($formSetup->items);


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$action = 'edit';


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "MovilStockSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-movilstock page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = movilstockAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "movilstock@movilstock");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("MovilStockSetupPage").'</span><br><br>';

if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true);
	print '<br>';
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
