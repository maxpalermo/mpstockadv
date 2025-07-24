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

class ModelStockMvt extends \StockMvt
{
    public $stock_before;
    public $stock_after;

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
        parent::$definition['fields']['physical_quantity'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isInt',
            'required' => true,
            'default' => 0,
        ];
        parent::$definition['fields']['stock_before'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isInt',
            'required' => true,
            'default' => 0,
        ];
        parent::$definition['fields']['stock_after'] = [
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
        $table = _DB_PREFIX_ . 'stock_mvt';
        if ($autodate) {
            $this->date_add = date('Y-m-d H:i:s');
        }

        $id_stock = (int) $this->id_stock;
        $id_order = (int) $this->id_order;
        $id_supply_order = (int) $this->id_supply_order;
        $id_stock_mvt_reason = (int) $this->id_stock_mvt_reason;
        $id_employee = (int) $this->id_employee;
        $employee_lastname = $this->dblApc($this->employee_lastname);
        $employee_firstname = $this->dblApc($this->employee_firstname);
        $stock_before = (int) $this->stock_before;
        $physical_quantity = (int) $this->physical_quantity;
        $stock_after = (int) $this->stock_after;
        $date_add = $this->dblApc($this->date_add);
        $sign = (int) $this->sign;
        $price_te = (float) $this->price_te;
        $last_wa = (float) $this->last_wa;
        $current_wa = (float) $this->current_wa;
        $referer = (int) $this->referer;

        $QUERY = "
            INSERT INTO
                {$table}
            (
                id_stock,
                id_order,
                id_supply_order,
                id_stock_mvt_reason,
                id_employee,
                employee_lastname,
                employee_firstname,
                stock_before,
                physical_quantity,
                stock_after,
                date_add,
                sign,
                price_te,
                last_wa,
                current_wa,
                referer
            )
            VALUES
            (
                {$id_stock},
                {$id_order},
                {$id_supply_order},
                {$id_stock_mvt_reason},
                {$id_employee},
                {$employee_lastname},
                {$employee_firstname},
                {$stock_before},
                {$physical_quantity},
                {$stock_after},
                {$date_add},
                {$sign},
                {$price_te},
                {$last_wa},
                {$current_wa},
                {$referer}
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