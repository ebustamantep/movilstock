<?php
/* Copyright (C) 2026		SuperAdmin
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * \file    movilstock/lib/movilstock.lib.php
 * \ingroup movilstock
 * \brief   Library files with common functions for MovilStock
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function movilstockAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("movilstock@movilstock");

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(dol_buildpath("/movilstock/admin/setup.php", 1));
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dolBuildUrl(dol_buildpath("/movilstock/admin/about.php", 1));
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'movilstock@movilstock');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'movilstock@movilstock', 'remove');

	return $head;
}

/**
 * Registra un traslado entre almacenes: cabecera + líneas en el historial y los dos
 * movimientos de stock por producto (salida del origen, entrada al destino). No abre ni
 * cierra transacción: lo hace quien llama.
 *
 * @param DoliDB               $db
 * @param User                 $user
 * @param int                  $src     Almacén origen
 * @param int                  $dst     Almacén destino
 * @param array<int,float>     $lines   [fk_product => qty] (qty > 0)
 * @param string               $label   Etiqueta de los movimientos ('' = "Traslado de stock")
 * @param array<int,string>    $errors  Mensajes de error (por referencia)
 * @return int                 id del traslado (>0) o <0 si error
 */
function movilstockCrearTraslado($db, $user, $src, $dst, $lines, $label, &$errors)
{
	global $langs;
	dol_include_once('/movilstock/class/movilstocktransfer.class.php');
	dol_include_once('/movilstock/class/movilstocktransferline.class.php');
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	$langs->load('movilstock@movilstock');

	$object = new MovilStockTransfer($db);
	$object->fk_warehouse_source = $src;
	$object->fk_warehouse_dest = $dst;
	$object->status = MovilStockTransfer::STATUS_DONE;
	$object->date_transfer = dol_now();
	$object->fk_user_author = $user->id;
	$object->ref = $object->getNextNumRef();

	$transferid = $object->create($user);
	if ($transferid <= 0) {
		$errors = array_merge($errors, array($object->error), (array) $object->errors);
		return -1;
	}

	$stocklabel = ($label !== '' ? $label : $langs->trans("MovilStockTransfer")).' '.$object->ref;
	foreach ($lines as $fkproduct => $qty) {
		$product = new Product($db);
		if ($product->fetch($fkproduct) <= 0) {
			$errors[] = $product->error ? $product->error : 'Product '.$fkproduct.' not found';
			return -1;
		}

		$product->load_stock('novirtual');
		$avail = 0;
		if (isset($product->stock_warehouse[$src]->real)) {
			$avail = (float) $product->stock_warehouse[$src]->real;
		}
		if ($qty > $avail) {
			$errors[] = $langs->trans("NotEnoughStock", $product->ref, $avail, $qty);
			return -1;
		}

		$line = new MovilStockTransferLine($db);
		$line->fk_transfer = $transferid;
		$line->fk_product = $fkproduct;
		$line->qty = $qty;
		$line->fk_warehouse_source = $src;
		$line->fk_warehouse_dest = $dst;
		$line->entity = getEntity('movilstocktransfer');
		if ($line->create($user) <= 0) {
			$errors = array_merge($errors, array($line->error), (array) $line->errors);
			return -1;
		}

		// Salida del origen y entrada al destino
		if ($product->correct_stock($user, $src, $qty, 1, $stocklabel, $product->pmp, '', 'movilstock', $transferid) < 0
			|| $product->correct_stock($user, $dst, $qty, 0, $stocklabel, $product->pmp, '', 'movilstock', $transferid) < 0) {
			$errors = array_merge($errors, array($product->error), (array) $product->errors);
			return -1;
		}
	}

	return (int) $transferid;
}
