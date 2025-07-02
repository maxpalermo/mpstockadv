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

namespace MpSoft\MpStockAdv\Helpers;
class MovementHelper extends DependencyHelper
{
    public function __construct()
    {
        parent::__construct();
    }
    public $id_warehouse;
    public $id_order;
    public $id_supply_order;
    public $id_currency;
    public $id_stock_mvt_reason;
    public $sign;
    public $id_employee;
    public $employee_lastname;
    public $employee_firstname;
    public $id_product;
    public $id_product_attribute;
    public $reference;
    public $supplier_reference;
    public $name;
    public $ean13;
    public $isbn;
    public $upc;
    public $mpn;
    public $exchange_rate;
    public $quantity_expected;
    public $quantity_received;
    public $price_te;

    public function getSignByIdStockMvtReason($idStockMovementReason)
    {
        $conn = $this->connection;
        $table = _DB_PREFIX_ . 'stock_mvt_reason';
        $query = "
            SELECT
                sign
            FROM
                {$table}
            WHERE
                `id_stock_mvt_reason` = :id_stock_mvt_reason
        ";
        $statement = $conn->executeQuery($query, ['id_stock_mvt_reason' => (int) $idStockMovementReason]);
        if ($statement) {
            return $statement->fetchOne();
        }

        return false;
    }

    public function getEmployeeData($idEmployee)
    {
        //TODO
    }

    public function hydrate($data)
    {
        $this->id_warehouse = (int) $data['id_warehouse'];
        $this->id_order = (int) ($data['id_order'] ?? 0);
        $this->id_supply_order = (int) ($data['id_supply_order'] ?? 0);
        $this->id_currency = (int) $data['id_currency'];
        $this->id_stock_mvt_reason = (int) $data['id_stock_mvt_reason'];
        $this->sign = (int) $data['sign'];
        $this->id_employee = (int) $data['id_employee'];
        $this->employee_lastname = $data['employee_lastname'];
        $this->employee_firstname = $data['employee_firstname'];
        $this->id_product = (int) $data['id_product'];
        $this->id_product_attribute = (int) $data['id_product_attribute'];
        $this->reference = $data['reference'];
        $this->supplier_reference = $data['supplier_reference'];
        $this->name = $data['name'];
        $this->ean13 = $data['ean13'];
        $this->isbn = $data['isbn'];
        $this->upc = $data['upc'];
        $this->mpn = $data['mpn'];
        $this->exchange_rate = (float) $data['exchange_rate'];
        $this->quantity_expected = (int) $data['quantity_expected'];
        $this->quantity_received = (int) $data['quantity_received'];
        $this->price_te = (float) $data['price_te'];
    }

    public function toArray()
    {
        return [
            'id_warehouse' => $this->id_warehouse,
            'id_order' => $this->id_order,
            'id_supply_order' => $this->id_supply_order,
            'id_currency' => $this->id_currency,
            'id_stock_mvt_reason' => $this->id_stock_mvt_reason,
            'sign' => $this->sign,
            'id_employee' => $this->id_employee,
            'employee_lastname' => $this->employee_lastname,
            'employee_firstname' => $this->employee_firstname,
            'id_product' => $this->id_product,
            'id_product_attribute' => $this->id_product_attribute,
            'reference' => $this->reference,
            'supplier_reference' => $this->supplier_reference,
            'name' => $this->name,
            'ean13' => $this->ean13,
            'isbn' => $this->isbn,
            'upc' => $this->upc,
            'mpn' => $this->mpn,
            'exchange_rate' => $this->exchange_rate,
            'quantity_expected' => $this->quantity_expected,
            'quantity_received' => $this->quantity_received,
            'price_te' => $this->price_te,
        ];
    }
}