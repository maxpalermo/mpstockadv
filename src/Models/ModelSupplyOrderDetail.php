<?php
namespace MpSoft\MpStockAdv\Models;

use Db;

class ModelSupplyOrderDetail
{
    public static function getBySupplyOrderId($id_supply_order)
    {
        $sql = 'SELECT sod.*, p.reference, p.name FROM '._DB_PREFIX_.'supply_order_detail sod
                LEFT JOIN '._DB_PREFIX_.'product_lang p ON p.id_product = sod.id_product AND p.id_lang = 1
                WHERE sod.id_supply_order = '.(int)$id_supply_order;
        return Db::getInstance()->executeS($sql);
    }
}
