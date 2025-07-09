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

/**
 * MOVEMENT
 *
    $movement = [
        'id_warehouse' => $id_warehouse,
        'id_order' => $id_order,
        'id_supply_order' => $id_supply_order,
        'id_currency' => $this->id_currency,
        'id_stock_mvt_reason' => $id_stock_mvt_reason,
        'sign' => $sign,
        'id_employee => $employee->id,
        'employee_lastname' => $employee->lastname,
        'employee_firstname' => $employee_firstname,
        'id_product' => $id_product,
        'id_product_attribute' => $id_product_attribute,
        'reference' => $product->reference,
        'supplier_reference' => $product->supplier_reference,
        'name' => $product->name,
        'ean13' => $product->ean13,
        'isbn' => $product->isbn,
        'upc' => $product->upc,
        'mpn' => $product->mpn,
        'exchange_rate' => 1,
        'quantity_expected' => $quantity,
        'quantity_received' => $quantity,
        'price_te' => $price,
    ];
 */

namespace MpSoft\MpStockAdv\Helpers;

use Doctrine\DBAL\ParameterType;

class StockManagerHelper extends DependencyHelper
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addMovement(array $movement): bool
    {
        /*
        $movement = [
            'id_warehouse' => $id_warehouse,
            'id_order' => $id_order,
            'id_supply_order' => $id_supply_order,
            'id_currency' => $this->id_currency,
            'id_stock_mvt_reason' => $id_stock_mvt_reason,
            'sign' => $sign,
            'id_employee => $employee->id,
            'employee_lastname' => $employee->lastname,
            'employee_firstname' => $employee_firstname,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'reference' => $product->reference,
            'supplier_reference' => $product->supplier_reference,
            'name' => $product->name,
            'ean13' => $product->ean13,
            'isbn' => $product->isbn,
            'upc' => $product->upc,
            'mpn' => $product->mpn,
            'exchange_rate' => 1,
            'quantity_expected' => $quantity,
            'quantity_received' => $quantity,
            'price_te' => $price,
        ];
        */

        $stock = $this->prepareStock($movement);
        $id_stock_mvt = 0;

        if ($stock['id_stock']) {
            $id_stock = (int) $stock['id_stock'];
            $stock_before = (int) $stock['stock_before'];
            $result = $this->prepareStockMvt($id_stock, $stock_before, $movement);
            $id_stock_mvt = (int) $result['id_stock_mvt'];
            $stock_after = (int) $result['stock_after'];
        }

        if ($id_stock_mvt) {
            \StockAvailable::setQuantity($movement['id_product'], $movement['id_product_attribute'], $stock_after, null, false);
        }

        return true;
    }

    public function prepareStock($movement)
    {
        $id_stock = (int) $this->getIdStock($movement['id_product'], $movement['id_product_attribute']);
        $stock_before = $this->getStockUsableQuantity($movement['id_product'], $movement['id_product_attribute']);
        if (!$id_stock) {
            $query = "
                INSERT INTO {$this->pfx}stock
                (
                    `id_warehouse`,
                    `id_product`,
                    `id_product_attribute`,
                    `reference`,
                    `ean13`,
                    `isbn`,
                    `upc`,
                    `mpn`,
                    `physical_quantity`,
                    `usable_quantity`,
                    `price_te`
                ) VALUES (
                    :id_warehouse,
                    :id_product,
                    :id_product_attribute,
                    :reference,
                    :ean13,
                    :isbn,
                    :upc,
                    :mpn,
                    :physical_quantity,
                    :usable_quantity,
                    :price_te
                )
            ";
        } else {
            $query = "
                UPDATE
                    {$this->pfx}stock
                SET 
                    `id_warehouse` = :id_warehouse,
                    `id_product` = :id_product,
                    `id_product_attribute` = :id_product_attribute,
                    `reference` = :reference,
                    `ean13` = :ean13,
                    `isbn` = :isbn,
                    `upc` = :upc,
                    `mpn` = :mpn,
                    `physical_quantity` = :physical_quantity,
                    `usable_quantity` = :usable_quantity,
                    `price_te` =  :price_te
                WHERE
                    `id_stock` = :id_stock
            ";
        }

        /** @var \Doctrine\DBAL\Driver\Statement */
        $statement = $this->connection->prepare($query);

        // Binding dei parametri con tipi espliciti
        $statement->bindValue('id_warehouse', (int) $movement['id_warehouse'], ParameterType::INTEGER);
        $statement->bindValue('id_product', (int) $movement['id_product'], ParameterType::INTEGER);
        $statement->bindValue('id_product_attribute', (int) $movement['id_product_attribute'], ParameterType::INTEGER);
        $statement->bindValue('reference', $movement['reference'] ?? '', ParameterType::STRING);
        $statement->bindValue('ean13', $movement['ean13'] ?? '', ParameterType::STRING);
        $statement->bindValue('isbn', $movement['isbn'] ?? '', ParameterType::STRING);
        $statement->bindValue('upc', $movement['upc'] ?? '', ParameterType::STRING);
        $statement->bindValue('mpn', $movement['mpn'], ParameterType::STRING);
        $statement->bindValue('physical_quantity', (int) $movement['quantity_received'], ParameterType::INTEGER);
        $statement->bindValue('usable_quantity', (int) $movement['quantity_received'], ParameterType::INTEGER);
        $statement->bindValue('price_te', (float) $movement['price_te']);
        $statement->bindValue('id_stock', (int) $id_stock, ParameterType::INTEGER);

        $result = $statement->executeStatement(); // Usa executeStatement() invece di executeQuery() per INSERT

        if ($result && !$id_stock) {
            $id_stock = (int) $this->connection->lastInsertId();
        }

        if (!$result) {
            return [
                'id_stock' => false,
                'stock_before' => false,
            ];
        }

        return [
            'id_stock' => $id_stock,
            'stock_before' => $stock_before,
        ];
    }

    public function getIdStock($id_product, $id_product_attribute)
    {
        $query = "
            SELECT
                id_stock
            FROM
                {$this->pfx}stock
            WHERE
                id_product = :id_product
            AND 
                id_product_attribute = :id_product_attribute
        ";

        /** @var \Doctrine\DBAL\Driver\PDO\SQLSrv\Statement */
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue('id_product', (int) $id_product, ParameterType::INTEGER);
        $stmt->bindValue('id_product_attribute', (int) $id_product_attribute, ParameterType::INTEGER);
        $result = $stmt->execute();
        if ($result) {
            $id_stock = $stmt->fetchOne();
            if ($id_stock) {
                return (int) $id_stock;
            }
        }

        return false;
    }

    /**
     * Summary of prepareStockMvt
     * Add a movement in stock_mvt table
     * @param int $id_stock
     * @param int $stock_before
     * @param array $movement
     * @return array{id_stock_mvt: int, stock_after: int}
     */
    public function prepareStockMvt(int $id_stock, int $stock_before, array $movement): array
    {
        /*
        $movement = [
            'id_warehouse' => $id_warehouse,
            'id_order' => $id_order,
            'id_supply_order' => $id_supply_order,
            'id_currency' => $this->id_currency,
            'id_stock_mvt_reason' => $id_stock_mvt_reason,
            'sign' => $sign,
            'id_employee => $employee->id,
            'employee_lastname' => $employee->lastname,
            'employee_firstname' => $employee_firstname,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'reference' => $product->reference,
            'supplier_reference' => $product->supplier_reference,
            'name' => $product->name,
            'ean13' => $product->ean13,
            'isbn' => $product->isbn,
            'upc' => $product->upc,
            'mpn' => $product->mpn,
            'exchange_rate' => 1,
            'quantity_expected' => $quantity,
            'quantity_received' => $quantity,
            'price_te' => $price,
        ];
        */

        $stock_after = $stock_before + $movement['quantity_received'];

        $query = "
            INSERT INTO {$this->pfx}stock_mvt
            (
                `id_stock`,
                `id_order`,
                `id_supply_order`,
                `id_stock_mvt_reason`,
                `id_employee`,
                `employee_lastname`,
                `employee_firstname`,
                `stock_before`,
                `physical_quantity`,
                `stock_after`,
                `date_add`,
                `sign`,
                `price_te`,
                `last_wa`,
                `current_wa`,
                `referer`
            ) VALUES (
                :id_stock,
                :id_order,
                :id_supply_order,
                :id_stock_mvt_reason,
                :id_employee,
                :employee_lastname,
                :employee_firstname,
                :stock_before,
                :physical_quantity,
                :stock_after,
                :date_add,
                :sign,
                :price_te,
                :last_wa,
                :current_wa,
                :referer
            )
        ";

        /** @var \Doctrine\DBAL\Driver\Statement */
        $statement = $this->connection->prepare($query);

        // Binding dei parametri con tipi espliciti
        $statement->bindValue('id_stock', (int) $id_stock, ParameterType::INTEGER);
        $statement->bindValue('id_order', (int) $movement['id_order'], ParameterType::INTEGER);
        $statement->bindValue('id_supply_order', (int) $movement['id_supply_order'], ParameterType::INTEGER);
        $statement->bindValue('id_stock_mvt_reason', $movement['id_stock_mvt_reason'], ParameterType::INTEGER);
        $statement->bindValue('id_employee', $movement['id_employee'], ParameterType::INTEGER);
        $statement->bindValue('employee_lastname', $movement['employee_lastname'], ParameterType::STRING);
        $statement->bindValue('employee_firstname', $movement['employee_firstname'], ParameterType::STRING);
        $statement->bindValue('stock_before', (int) $stock_before, ParameterType::INTEGER);
        $statement->bindValue('physical_quantity', (int) $movement['quantity_received'], ParameterType::INTEGER);
        $statement->bindValue('stock_after', (int) $stock_after, ParameterType::INTEGER);
        $statement->bindValue('date_add', date('Y-m-d H:i:s'), ParameterType::STRING);
        $statement->bindValue('sign', (int) $movement['sign'], ParameterType::INTEGER);
        $statement->bindValue('price_te', (float) $movement['price_te']);
        $statement->bindValue('last_wa', 0, ParameterType::INTEGER);
        $statement->bindValue('current_wa', 0, ParameterType::INTEGER);
        $statement->bindValue('referer', 0, ParameterType::INTEGER);

        $result = $statement->executeStatement(); // Usa executeStatement() invece di executeQuery() per INSERT

        if ($result) {
            $id_stock_mvt = (int) $this->connection->lastInsertId();
        } else {
            return [
                'id_stock_mvt' => -1,
                'stock_after' => false,
            ];
        }

        return [
            'id_stock_mvt' => (int) $id_stock_mvt,
            'stock_after' => (int) $stock_after,
        ];
    }

    public static function getStockUsableQuantity($id_product, $id_product_attribute)
    {
        /** @var string */
        $pfx = _DB_PREFIX_;
        /** @var string */
        $query = "
            SELECT
                usable_quantity
            FROM
                {$pfx}stock
            WHERE
                id_product = :id_product
            AND
                id_product_attribute = :id_product_attribute
        ";
        /** @var  \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        /** @var  \Doctrine\DBAL\Connection */
        $connection = $container->get('doctrine.dbal.default_connection');
        /** @var \Doctrine\DBAL\Driver\Statement */
        $statement = $connection->prepare($query);
        /** @var \Doctrine\DBAL\Result */
        $result = $statement->executeQuery(
            [
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute
            ]
        );
        if ($result) {
            $quantity = $result->fetchOne();
        } else {
            $quantity = false;
        }

        return (int) $quantity;
    }

    public static function getStockPhysicalQuantity($id_product, $id_product_attribute)
    {
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                physical_quantity
            FROM
                {$pfx}stock
            WHERE
                id_product = {$id_product}
            AND
                id_product_attribute = {$id_product_attribute}
        ";
        $quantity = (int) \Db::getInstance()->getValue($query);

        return (int) $quantity;
    }
}