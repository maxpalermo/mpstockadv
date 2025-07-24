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

namespace MpSoft\MpStockAdv\Handlers;

use MpSoft\MpStockAdv\Models\ModelStock;
use MpSoft\MpStockAdv\Models\ModelStockMvt;

class OrderDetailHandler
{
    private $db;
    private $context;
    private $mvt_reason_load_id;
    private $mvt_reason_unload_id;
    private $warehouse_id;

    private $error;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->context = \Context::getContext();

        $this->mvt_reason_load_id = \Configuration::get('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON_LOAD');
        $this->mvt_reason_unload_id = \Configuration::get('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON_UNLOAD');
        $this->warehouse_id = \Configuration::get('MPSTOCKADV_DEFAULT_WAREHOUSE');
    }

    public function getError()
    {
        return $this->error;
    }

    public function hookOrderDetailAddAfter($params)
    {
        /** @var \OrderDetail $orderDetail */
        $orderDetail = $params['object'];

        $this->addStockMovement($orderDetail);
    }

    public function hookOrderDetailUpdateAfter($params)
    {
        /** @var \OrderDetail $orderDetail */
        $orderDetail = $params['object'];

    }

    public function hookOrderDetailDeleteAfter($params)
    {
        /** @var \OrderDetail $orderDetail */
        $orderDetail = $params['object'];

    }

    public function addStockMovement(\OrderDetail $orderDetail)
    {
        $employee = $this->getEmployee($this->context->employee);
        $idStockMvtReason = $this->mvt_reason_unload_id;
        $orderId = (int) $orderDetail->id_order;
        $warehouseId = (int) $this->warehouse_id;
        $productId = (int) $orderDetail->product_id;
        $productAttributeId = (int) $orderDetail->product_attribute_id;
        $reference = $orderDetail->product_reference;
        $ean13 = $orderDetail->product_ean13;
        $isbn = $orderDetail->product_isbn;
        $upc = $orderDetail->product_upc;
        $mpn = $orderDetail->product_mpn;
        $physical_quantity = -$orderDetail->product_quantity;
        $usable_quantity = \StockAvailable::getQuantityAvailableByProduct($productId, $productAttributeId);

        $stock_before = $usable_quantity - $physical_quantity;
        $stock_after = $usable_quantity;

        if ($productAttributeId) {
            $combination = new \Combination($productAttributeId);
            if (\Validate::isLoadedObject($combination)) {
                $reference = $combination->reference;
                $ean13 = $combination->$ean13;
                $isbn = $combination->$isbn;
                $upc = $combination->upc;
                $mpn = $combination->$mpn;
            }
        }

        $mvtReason = new \StockMvtReason($idStockMvtReason);
        if (!\Validate::isLoadedObject($mvtReason)) {
            $this->error = "Tipo di movimento non trovato: {$idStockMvtReason}";
            return false;
        }

        $stock = new ModelStock();
        $stock->id_warehouse = (int) $this->warehouse_id;
        $stock->id_product = (int) $productId;
        $stock->id_product_attribute = (int) $productAttributeId;
        $stock->reference = $reference;
        $stock->ean13 = $ean13;
        $stock->isbn = $isbn;
        $stock->upc = $upc;
        $stock->mpn = $mpn;
        $stock->physical_quantity = (int) $physical_quantity;
        $stock->usable_quantity = (int) $usable_quantity;
        try {
            $saveStockResult = $stock->add();
            if ($saveStockResult) {
                $stockId = (int) $stock->id;
            } else {
                $this->error = "Errore durante il salvataggio dello stock: {$stock->id}";
                return false;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }


        $stockMvt = new ModelStockMvt();
        $stockMvt->id_stock = $stockId;
        $stockMvt->id_stock_mvt_reason = $idStockMvtReason;
        $stockMvt->id_order = $orderId;
        $stockMvt->id_warehouse = $warehouseId;
        $stockMvt->id_employee = $employee->id;
        $stockMvt->employee_lastname = \Tools::strtoupper($employee->lastname);
        $stockMvt->employee_firstname = \Tools::strtoupper($employee->firstname);
        $stockMvt->stock_before = $stock_before;
        $stockMvt->physical_quantity = (int) $physical_quantity;
        $stockMvt->stock_after = $stock_after;
        $stockMvt->date_add = date('Y-m-d H:i:s');
        $stockMvt->sign = $mvtReason->sign;
        $stockMvt->price_te = $orderDetail->product_price;

        try {
            $saveStockMvtResult = $stockMvt->add();
            if ($saveStockMvtResult) {
                $stockMvtId = (int) $stockMvt->id;
            } else {
                $this->error = "Errore durante il salvataggio dello stock movement: {$stockMvt->id}";
                return false;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

        return $stockMvtId;
    }

    public function getEmployee($employee)
    {
        if ($employee instanceof \Employee) {
            $employee = new \Employee((int) $employee->id);
            return $employee;
        }

        return false;
    }

}