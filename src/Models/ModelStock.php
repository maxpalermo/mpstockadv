<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpStockAdv\Models;

class ModelStock extends \Stock
{
    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
        parent::$definition['fields']['physical_quantity'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isInt',
            'required' => true,
            'default' => 0,
        ];
    }

    protected function dblApc($string)
    {
        $string = pSQL($string);
        $dblApc = str_replace("'", "''", $string);
        return "'$dblApc'";
    }

    public function add($autodate = true, $null_values = false)
    {
        $table = _DB_PREFIX_ . 'stock';
        if ($autodate) {
            $this->date_add = date('Y-m-d H:i:s');
        }

        $id_warehouse = (int) $this->id_warehouse;
        $id_product = (int) $this->id_product;
        $id_product_attribute = (int) $this->id_product_attribute;
        $reference = $this->dblApc(pSQL($this->reference));
        $ean13 = $this->dblApc($this->ean13);
        $isbn = $this->dblApc($this->isbn);
        $upc = $this->dblApc($this->upc);
        $mpn = $this->dblApc($this->mpn);
        $physical_quantity = (int) $this->physical_quantity;
        $usable_quantity = (int) $this->usable_quantity;
        $price_te = (float) $this->price_te;

        $QUERY = "
            INSERT INTO
                {$table}
            (
                id_warehouse,
                id_product,
                id_product_attribute,
                reference,
                ean13,
                isbn,
                upc,
                mpn,
                physical_quantity,
                usable_quantity,
                price_te
            )
            VALUES
            (
                {$id_warehouse},
                {$id_product},
                {$id_product_attribute},
                {$reference},
                {$ean13},
                {$isbn},
                {$upc},
                {$mpn},
                {$physical_quantity},
                {$usable_quantity},
                {$price_te}
            )
        ";

        $id = 0;

        try {
            $result = \Db::getInstance()->execute($QUERY);
            if ($result) {
                $id = \Db::getInstance()->Insert_ID();
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->id = $id;
        return $id;
    }
}