<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA.
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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ImportFromStockAvailableHelper extends DependencyHelper
{
    /** @var array */
    private $stockAvailableRows;

    /** @var ConfigurationHelper */
    private $configurationHelper;

    public function __construct()
    {
        parent::__construct();
        $this->configurationHelper = new ConfigurationHelper();
    }

    public function truncateStockTables()
    {
        $query = "
            TRUNCATE TABLE {$this->pfx}stock
        ";
        $stmtStock = $this->connection->prepare($query);
        $resultStock = $stmtStock->executeQuery();

        $query = "
            TRUNCATE TABLE {$this->pfx}stock_mvt
        ";
        $stmtStockMvt = $this->connection->prepare($query);
        $resultStockMvt = $stmtStockMvt->executeQuery();

        return [
            'stock' => $resultStock->rowCount(),
            'stock_mvt' => $resultStockMvt->rowCount(),
        ];
    }

    public function getStockAvailableRows()
    {
        $query = "
            SELECT 
                *
            FROM
                {$this->pfx}stock_available
            ORDER BY
                id_stock_available ASC
        ";

        $stmtStockAvailable = $this->connection->prepare($query);
        $resultStockAvailable = $stmtStockAvailable->executeQuery();
        $rows = $resultStockAvailable->fetchAllAssociative();

        $this->stockAvailableRows = $rows;

        return [
            'rows' => $rows,
        ];
    }

    public function getDefaultLoadMvtReason()
    {
        return $this->configurationHelper->getDefaultStockMvtReasonLoad();
    }

    public function truncateStockTablesAction()
    {
        return new JsonResponse($this->truncateStockTables());
    }

    public function InsertStock($rows)
    {
        $id_warehouse = $this->configurationHelper->getDefaultWarehouse();
        $id_stock_mvt_reason = $this->configurationHelper->getDefaultStockMvtReasonLoad();
        $employee = $this->configurationHelper->getCurrentEmployee();

        $stockMvtReason = new \StockMvtReason($id_stock_mvt_reason, $this->id_lang);
        if (!\Validate::isLoadedObject($stockMvtReason)) {
            return [
                'stockInserted' => -1,
                'stockMvtInserted' => -1,
                'lastStockInsertId' => -1,
                'lastStockMvtInsertId' => -1,
                'error' => 'Tipo di movimento non valido',
            ];
        }

        $rowStockInserted = 0;
        $rowStockMvtInserted = 0;
        foreach ($rows as $stockAvailableRow) {
            $lastStockInsertedId = 0;
            $lastStockMvtInsertedId = 0;

            $id_product = (int) $stockAvailableRow['id_product'];
            $id_product_attribute = (int) $stockAvailableRow['id_product_attribute'];
            $quantity = (int) $stockAvailableRow['quantity'];

            $product = new \Product($id_product);
            if (!\Validate::isLoadedObject($product)) {
                continue;
            }
            $price_te = $product->price;

            $combination = new \Combination($id_product_attribute);
            if (!$combination) {
                $id_product_attribute = 0;
                $reference = $product->reference;
                $ean13 = $product->ean13;
                $isbn = $product->isbn;
                $upc = $product->upc;
                $mpn = $product->mpn;
            } else {
                $reference = $combination->reference;
                $ean13 = $combination->ean13;
                $isbn = $combination->isbn;
                $upc = $combination->upc;
                $mpn = $combination->mpn;
            }

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
                VALUES
                (
                    :id_warehouse,
                    :id_product,
                    :id_product_attribute,
                    ':reference',
                    ':ean13',
                    ':isbn',
                    ':upc',
                    ':mpn',
                    :physical_quantity,
                    :usable_quantity,
                    :price_te
                )
            ";

            $stmtStock = $this->connection->prepare($query);
            $resultStock = $stmtStock->executeQuery(
                [
                    'id_warehouse' => $id_warehouse,
                    'id_product' => $id_product,
                    'id_product_attribute' => $id_product_attribute,
                    'reference' => $reference,
                    'ean13' => $ean13,
                    'isbn' => $isbn,
                    'upc' => $upc,
                    'mpn' => $mpn,
                    'physical_quantity' => $quantity,
                    'usable_quantity' => $quantity,
                    'price_te' => $price_te,
                ]
            );

            if ($resultStock->rowCount()) {
                ++$rowStockInserted;
                $id_stock = (int) $this->connection->lastInsertId();
                $lastStockInsertedId = $id_stock;
            }

            $id_order = 0;
            $id_supply_order = 0;
            $stock_before = 0;
            $stock_after = $stock_before + $quantity;
            $date_add = date('Y-m-d H:i:s');
            $sign = (int) $stockMvtReason->sign;
            $price_te = $product->price;
            $last_wa = 0;
            $current_wa = $price_te;
            $referer = 0;

            $queryStockMvt = "
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
                VALUES
                (
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

            $stmtStockMvt = $this->connection->prepare($queryStockMvt);
            $resultStockMvt = $stmtStockMvt->executeQuery(
                [
                    'id_stock' => $id_stock,
                    'id_order' => $id_order,
                    'id_supply_order' => $id_supply_order,
                    'id_stock_mvt_reason' => $id_stock_mvt_reason,
                    'id_employee' => $employee->id,
                    'employee_lastname' => $employee->lastname,
                    'employee_firstname' => $employee->firstname,
                    'stock_before' => $stock_before,
                    'physical_quantity' => $quantity,
                    'stock_after' => $stock_after,
                    'date_add' => $date_add,
                    'sign' => $sign,
                    'price_te' => $price_te,
                    'last_wa' => $last_wa,
                    'current_wa' => $current_wa,
                    'referer' => $referer
                ]
            );

            if ($resultStockMvt->rowCount()) {
                ++$rowStockMvtInserted;
                $lastStockMvtInsertedId = $this->connection->lastInsertId();
            }
        }

        return [
            'stockInserted' => $rowStockInserted,
            'stockMvtInserted' => $rowStockMvtInserted,
            'lastStockInsertId' => $lastStockInsertedId,
            'lastStockMvtInsertId' => $lastStockMvtInsertedId,
            'error' => false,
        ];
    }

    public function insertStockMvt()
    {
    }

    public function getStockAvailableRowsAction()
    {
        return new JsonResponse($this->getStockAvailableRows(), 200);
    }

    public function importChunkAction(Request $request)
    {
        $data = $request->getContent();
        $json = json_decode($data, true);
        $chunk = $json['chunk'] ?? [];

        return new JsonResponse($this->insertStock($chunk), 200);
    }
}
