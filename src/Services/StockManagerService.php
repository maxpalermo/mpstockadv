<?php

namespace MpSoft\MpStockAdv\Services;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\HttpFoundation\JsonResponse;

class StockManagerService
{
    /** @var Connection */
    private $connection;

    /** @var LegacyContext */
    private $context;

    /** @var string */
    private $pfx;

    /** @var int */
    private $id_lang;

    /** @var int */
    private $last_inserted_stock_id;

    /** @var int */
    private $last_inserted_stock_mvt_id;

    public function __construct(Connection $connection, LegacyContext $context)
    {
        $this->connection = $connection;
        $this->context = $context;
        $this->pfx = _DB_PREFIX_;
        $this->id_lang = (int) $context->getContext()->language->id;
    }

    private function setJsonResponse(array $response, $statusCode = 200)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'charset' => 'utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ];
        $result = new JsonResponse(
            $response,
            $statusCode
        );

        exit($result->send());
    }

    public function getDefaultIdWarehouse()
    {
        return (int) \Configuration::get('MPSTOCKADV_DEFAULT_WAREHOUSE');
    }

    public function getStockRecord(int $id_stock = 0)
    {
        if (!$id_stock) {
            $id_stock = $this->last_inserted_stock_id;
        }
        $query = "
            SELECT *
            FROM {$this->pfx}stock
            WHERE id_stock = :id_stock
        ";
        $stmt = $this->connection->prepare($query);
        $result = $stmt->executeQuery([
            'id_stock' => $id_stock,
        ]);

        return $result->fetchOne();
    }

    public function getStockMvtRecord(int $id_stock_mvt = 0)
    {
        if (!$id_stock_mvt) {
            $id_stock_mvt = $this->last_inserted_stock_mvt_id;
        }
        $query = "
            SELECT *
            FROM {$this->pfx}stock_mvt
            WHERE id_stock = :id_stock_mvt
        ";
        $stmt = $this->connection->prepare($query);
        $result = $stmt->executeQuery([
            'id_stock_mvt' => $id_stock_mvt,
        ]);

        return $result->fetchOne();
    }

    public function getEmployee(int $id_employee)
    {
        if (!$id_employee) {
            $id_employee = (int) $this->context->getContext()->employee->id;
        }

        $employee = new \Employee($id_employee);

        return [
            'id_employee' => $id_employee,
            'firstname' => strtoupper($employee->firstname),
            'lastname' => strtoupper($employee->lastname),
        ];
    }

    public function getIdStock($id_product, $id_product_attribute, $id_warehouse)
    {
        $query = "
            SELECT id_stock
            FROM {$this->pfx}stock
            WHERE id_product = :id_product
                AND id_product_attribute = :id_product_attribute
                AND id_warehouse = :id_warehouse
        ";

        $stmt = $this->connection->prepare($query);
        $result = $stmt->executeQuery([
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'id_warehouse' => $id_warehouse,
        ]);

        return (int) $result->fetchOne();
    }

    public function addStock($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity, $id_warehouse, $price_te)
    {
        $product = new \Product($id_product);
        if ($id_product_attribute) {
            $productAttribute = new \Combination($id_product_attribute, $this->id_lang);
            $reference = $productAttribute->reference;
            $ean13 = $productAttribute->ean13;
            $isbn = $productAttribute->isbn;
            $upc = $productAttribute->upc;
            $mpn = $productAttribute->mpn;
        } else {
            $reference = $product->reference;
            $ean13 = $product->ean13;
            $isbn = $product->isbn;
            $upc = $product->upc;
            $mpn = $product->mpn;
        }
        $mvtReason = new \StockMvtReason($id_stock_mvt_reason);
        $sign = $mvtReason->sign;
        $movement = $quantity * $sign;

        $query = "
            INSERT INTO {$this->pfx}stock
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
            VALUES (
                :id_warehouse,
                :id_product,
                :id_product_attribute,
                :reference,
                :ean13,
                :isbn,
                :upc,
                :mpn,
                :quantity,
                :quantity,
                :price_te
            )
        ";

        $stmt = $this->connection->prepare($query);

        $result = $stmt->executeQuery([
            'id_warehouse' => $id_warehouse,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'reference' => $reference,
            'ean13' => $ean13,
            'isbn' => $isbn,
            'upc' => $upc,
            'mpn' => $mpn,
            'quantity' => $movement,
            'price_te' => $price_te,
        ]);

        $this->last_inserted_stock_id = $this->connection->lastInsertId();
        $this->updateStockAvailable($id_product, $id_product_attribute, $movement);

        return $result->rowCount();
    }

    public function updateStockAvailable($id_product, $id_product_attribute, $quantity)
    {
        $stock = $this->getStockQuantity($id_product, $id_product_attribute);
        if ($stock) {
            return \StockAvailable::updateQuantity($id_product, $id_product_attribute, $quantity, 0, false);
        }

        return \StockAvailable::setQuantity($id_product, $id_product_attribute, $quantity, 0, false);
    }

    public function updateStock($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity, $id_warehouse = 0, $price_te = 0)
    {
        if (!$id_warehouse) {
            $id_warehouse = $this->getDefaultIdWarehouse();
        }
        $id_stock = $this->getIdStock($id_product, $id_product_attribute, $id_warehouse);
        if (!$id_stock) {
            return $this->addStock($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity, $id_warehouse, $price_te);
        }

        $stock = $this->getStockQuantity($id_product, $id_product_attribute, $id_warehouse);
        $combination = new \Combination($id_product_attribute, $this->id_lang);
        $mvtReason = new \StockMvtReason($id_stock_mvt_reason);
        $sign = $mvtReason->sign;
        $movement = $quantity * $sign;
        $newStock = $stock + $movement;
        $newUsable = $newStock;

        $query = "
            UPDATE {$this->pfx}stock
            SET
                id_warehouse = :id_warehouse,
                id_product = :id_product,
                id_product_attribute = :id_product_attribute,
                reference = :reference,
                ean13 = :ean13,
                isbn = :isbn,
                upc = :upc,
                mpn = :mpn,
                physical_quantity = :newStock,
                usable_quantity = :newUsable,
                price_te = :price_te
            WHERE id_stock = :id_stock
        ";

        $stmt = $this->connection->prepare($query);

        $result = $stmt->executeQuery([
            'id_warehouse' => $id_warehouse,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'reference' => $combination->reference,
            'ean13' => $combination->ean13,
            'isbn' => $combination->isbn,
            'upc' => $combination->upc,
            'mpn' => $combination->mpn,
            'newStock' => $newStock,
            'newUsable' => $newUsable,
            'price_te' => $price_te,
            'id_stock' => $id_stock,
        ]);

        $this->last_inserted_stock_id = $id_stock;
        $this->updateStockAvailable($id_product, $id_product_attribute, $newStock);

        return $result->rowCount();
    }

    public function getStockQuantity($id_product, $id_product_attribute, $id_warehouse = 0)
    {
        if (!$id_warehouse) {
            $id_warehouse = $this->getDefaultIdWarehouse();
        }
        $query = "
            SELECT physical_quantity
            FROM {$this->pfx}stock
            WHERE id_product = :id_product
            AND id_product_attribute = :id_product_attribute
            AND id_warehouse = :id_warehouse
        ";
        $stmt = $this->connection->prepare($query);
        $result = $stmt->executeQuery([
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'id_warehouse' => $id_warehouse,
        ]);

        return (int) $result->fetchOne();
    }

    public function getStockAvailableQuantity($id_product, $id_product_attribute, $id_shop = 0)
    {
        if (!$id_shop) {
            $id_shop = (int) $this->context->getContext()->shop->id;
        }
        $query = "
            SELECT physical_quantity
            FROM {$this->pfx}stock_available
            WHERE id_product = :id_product
            AND id_product_attribute = :id_product_attribute
            AND id_shop = :id_shop
        ";
        $stmt = $this->connection->prepare($query);
        $result = $stmt->executeQuery([
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'id_shop' => $id_shop,
        ]);

        return (int) $result->fetchOne();
    }

    public function addMovement($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity, $price_te = 0, $id_warehouse = 0, $id_employee = 0, $id_order = 0, $id_supply_order = 0, $id_stock = 0)
    {
        $query = "
            INSERT INTO {$this->pfx}stock_mvt
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
            VALUES (
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

        if (!$id_warehouse) {
            $id_warehouse = $this->getDefaultIdWarehouse();
        }

        if (!$id_stock) {
            $id_stock = $this->last_inserted_stock_id;
        }

        $employee = $this->getEmployee($id_employee);
        $stockMvtReason = new \StockMvtReason($id_stock_mvt_reason);
        $stock_before = $this->getStockQuantity($id_product, $id_product_attribute, $id_warehouse);
        if (-1 == $stockMvtReason->sign && $quantity > 0) {
            $movement = $quantity * $stockMvtReason->sign;
        } else {
            $movement = $quantity;
        }
        $stock_after = $stock_before + $movement;
        $referer = 0;

        $stmt = $this->connection->prepare($query);

        $result = $stmt->executeQuery([
            'id_stock' => $id_stock,
            'id_order' => $id_order,
            'id_supply_order' => $id_supply_order,
            'id_stock_mvt_reason' => $id_stock_mvt_reason,
            'id_employee' => $id_employee,
            'employee_lastname' => $employee['lastname'],
            'employee_firstname' => $employee['firstname'],
            'stock_before' => $stock_before,
            'physical_quantity' => $movement,
            'stock_after' => $stock_after,
            'date_add' => date('Y-m-d H:i:s'),
            'sign' => $stockMvtReason->sign,
            'price_te' => $price_te,
            'last_wa' => $this->getStockAvailableQuantity($id_product, $id_product_attribute, $id_warehouse),
            'current_wa' => $this->getStockAvailableQuantity($id_product, $id_product_attribute, $id_warehouse),
            'referer' => $referer,
        ]);

        $this->last_inserted_stock_mvt_id = $this->connection->lastInsertId();

        return $result->rowCount();
    }

    public function getMovement($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity)
    {
        $query = "
            SELECT *
            FROM {$this->pfx}stock_mvt
            WHERE id_product = :id_product
            AND id_product_attribute = :id_product_attribute
            AND id_stock_mvt_reason = :id_stock_mvt_reason
            AND physical_quantity = :quantity
            ORDER BY id_stock_mvt DESC
        ";
        $stmt = $this->connection->prepare($query);
        $result = $stmt->executeQuery([
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'id_stock_mvt_reason' => $id_stock_mvt_reason,
            'quantity' => $quantity,
        ]);

        return $result->fetchOne();
    }
}
