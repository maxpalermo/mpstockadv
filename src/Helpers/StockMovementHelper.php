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

use Symfony\Component\HttpFoundation\JsonResponse;

class StockMovementHelper extends DependencyHelper
{
    /** @var StockManagerHelper */
    private $stockManagerHelper;

    public function __construct()
    {
        parent::__construct();
        $this->stockManagerHelper = new StockManagerHelper();

    }
    public function stockManagerUpdate($data)
    {
        $movementHelper = new MovementHelper();

        $id_warehouse = (int) $this->id_warehouse;
        $id_order = (int) ($data['id_order'] ?? 0);
        $id_order_supply = (int) ($data['id_order_supply'] ?? 0);
        $id_currency = (int) $this->id_currency;
        $id_product = (int) $data['product_id'];
        $id_product_attribute = (int) $data['product_attribute_id'];
        $product = new \Product($id_product, false, $this->id_lang);
        $id_stock_mvt_reason = (int) $data['stock_mvt_reason_id'];
        $stockMvtReason = new \StockMvtReason($id_stock_mvt_reason);
        $sign = (int) $stockMvtReason->sign;
        $employee = $this->context->getContext()->employee;
        $ean13 = $data['ean13'] ?? '';
        $reference = $data['product_reference'] ?? '';
        $isbn = $data['isbn'] ?? '';
        $mpn = $data['mpn'] ?? '';
        $upc = $data['upc'] ?? '';
        $quantity = (int) $data['product_quantity'];
        $price_te = (float) ($data['product_price_te'] ?? 0);

        $movement = [
            'id_warehouse' => $id_warehouse,
            'id_order' => $id_order,
            'id_supply_order' => $id_order_supply,
            'id_currency' => $id_currency,
            'id_stock_mvt_reason' => $id_stock_mvt_reason,
            'sign' => $sign,
            'id_employee' => $employee->id,
            'employee_lastname' => $employee->lastname,
            'employee_firstname' => $employee->firstname,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'reference' => $reference,
            'supplier_reference' => $product->supplier_reference,
            'name' => $product->name,
            'ean13' => $ean13,
            'isbn' => $isbn,
            'upc' => $upc,
            'mpn' => $mpn,
            'exchange_rate' => 1,
            'quantity_expected' => (int) $quantity * (int) $sign,
            'quantity_received' => (int) $quantity * (int) $sign,
            'price_te' => $price_te,
        ];

        $movementHelper->hydrate($movement);


        $result = $this->stockManagerHelper->addMovement($movementHelper->toArray());

        return [
            'success' => $result,
            'movement' => $movementHelper->toArray(),
        ];
    }
    public function getProductStockInWarehouse($idProduct, $idProductAttribute = 0, $idWarehouse = 0)
    {
        if (!$idWarehouse) {
            $idWarehouse = $this->id_warehouse;
        }

        return \StockManagerFactory::getManager()->getProductRealQuantities($idProduct, $idProductAttribute, $idWarehouse);
    }
}
