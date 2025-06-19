<?php
namespace MpSoft\MpStockAdv\Models;

use Db;

class ModelSupplyOrder
{
    public static function getAll()
    {
        $sql = 'SELECT so.id_supply_order, so.date_add, so.supplier_name, so.id_supply_order_state, sol.name AS state_name
        FROM '._DB_PREFIX_.'supply_order so
        LEFT JOIN '._DB_PREFIX_.'supply_order_state_lang sol ON sol.id_supply_order_state = so.id_supply_order_state AND sol.id_lang = 1
        ORDER BY so.date_add DESC';
        return Db::getInstance()->executeS($sql);
    }

    public static function getById($id)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'supply_order WHERE id_supply_order = '.(int)$id;
        return Db::getInstance()->getRow($sql);
    }

    public static function getPaginated($page = 1, $limit = 20, $search = '')
    {
        $offset = ($page - 1) * $limit;
        $where = '';
        if ($search) {
            $searchSql = pSQL($search);
            $where = "WHERE (so.id_supply_order LIKE '%$searchSql%' OR so.supplier_name LIKE '%$searchSql%' OR sol.name LIKE '%$searchSql%')";
        }
        $sql = 'SELECT so.id_supply_order, so.date_add, so.supplier_name, so.id_supply_order_state, sol.name AS state_name
            FROM '._DB_PREFIX_.'supply_order so
            LEFT JOIN '._DB_PREFIX_.'supply_order_state_lang sol ON sol.id_supply_order_state = so.id_supply_order_state AND sol.id_lang = 1
            ' . $where . '
            ORDER BY so.date_add DESC
            LIMIT '.(int)$limit.' OFFSET '.(int)$offset;
        $data = Db::getInstance()->executeS($sql);
        $sqlCount = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'supply_order so LEFT JOIN '._DB_PREFIX_.'supply_order_state_lang sol ON sol.id_supply_order_state = so.id_supply_order_state AND sol.id_lang = 1 '. $where;
        $total = (int)Db::getInstance()->getValue($sqlCount);
        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    // Altri metodi per CRUD, dettagli, ricezione ...
}
