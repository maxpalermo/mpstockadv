<?php
namespace MpSoft\MpStockAdv\Models;

use Db;

class ModelSupplyOrderReceipt
{
    public static function addReceipt($id_supply_order_detail, $received_qty, $employee_id)
    {
        $sql = 'INSERT INTO '._DB_PREFIX_.'supply_order_receipt_history (id_supply_order_detail, id_employee, id_supply_order_state, quantity, date_add)
                VALUES ('.(int)$id_supply_order_detail.', '.(int)$employee_id.', 4, '.(float)$received_qty.', NOW())';
        return Db::getInstance()->execute($sql);
    }
}
