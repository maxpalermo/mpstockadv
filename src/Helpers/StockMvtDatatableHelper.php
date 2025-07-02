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

class StockMvtDatatableHelper extends DependencyHelper
{
    /** @var JsonResponse */
    private $response;

    public function __construct()
    {
        parent::__construct();
        $this->response = new JsonResponse();
    }

    public function getStockMvtDataAction(Request $request)
    {
        $term = $request->get('search', []);
        $draw = $request->get('draw', 1);
        $orderBy = $request->get('order', []);
        $start = $request->get('start', 0);
        $length = $request->get('length', 20);

        $stockMvt = $this->getStockMvt($term, $start, $length, $orderBy);

        $result = json_encode([
            'draw' => ++$draw,
            'recordsTotal' => $stockMvt['recordsTotal'],
            'recordsFiltered' => $stockMvt['recordsFiltered'],
            'data' => $stockMvt['data'],
        ]);

        return $this->response
            ->setJson($result)
            ->setStatusCode(200);
    }

    public function getStockMvt($term = [], $start = 0, $length = 20, $orderBy = [])
    {
        $list = $this->getList($term, $start, $length, $orderBy);

        return [
            'recordsTotal' => $list['totalRows'],
            'recordsFiltered' => $list['totalFiltered'],
            'data' => $list['data'],
        ];
    }

    protected function getList($term, $start, $length, $orderBy)
    {
        $id_lang = (int) $this->context->getContext()->language->id;
        $pfx = _DB_PREFIX_;
        $conn = $this->connection;
        $query = "
            SELECT 
                stkmvt.id_stock_mvt as `id`,
                stkmvt.date_add as `date`,
                stkmvtrsn.sign,
                stkmvtrsnlang.name as `type`,
                stkmvt.id_order,
                stkmvt.id_supply_order,
                stk.id_product as `product_id`,
                stk.id_product_attribute as `product_attribute_id`,
                pl.name as `product_name`,
                stk.reference as `reference`,
                stk.ean13 as `ean13`,
                stk.isbn as `isbn`,
                stk.upc as `upc`,
                stk.mpn as `mpn`,
                stkmvt.stock_before as `stock_before`,
                stkmvt.physical_quantity as `movement`,
                stkmvt.stock_after as `stock_after`,
                w.name as warehouse,
                COALESCE(CONCAT(e.firstname, ' ', e.lastname), '--') as `employee`
            FROM
                {$pfx}stock_mvt as stkmvt
            LEFT JOIN
                {$pfx}stock as stk ON (stkmvt.id_stock=stk.id_stock)
            LEFT JOIN
                {$pfx}stock_mvt_reason as stkmvtrsn ON (stkmvt.id_stock_mvt_reason=stkmvtrsn.id_stock_mvt_reason)
            LEFT JOIN
                {$pfx}stock_mvt_reason_lang as stkmvtrsnlang ON (stkmvtrsn.id_stock_mvt_reason=stkmvtrsnlang.id_stock_mvt_reason AND stkmvtrsnlang.id_lang={$id_lang})
            LEFT JOIN
                {$pfx}product_lang as pl ON (stk.id_product=pl.id_product AND pl.id_lang={$id_lang})
            LEFT JOIN
                {$pfx}warehouse w ON (stk.id_warehouse = w.id_warehouse)
            LEFT JOIN
                {$pfx}employee as e ON (stkmvt.id_employee=e.id_employee)
            LEFT JOIN
                {$pfx}orders o ON (stkmvt.id_order=o.id_order)
            LEFT JOIN
                {$pfx}supply_order so ON (stkmvt.id_supply_order=so.id_supply_order)
        ";

        if ($term && $term['value']) {
            $term = '%' . pSQL($term['value']) . '%';
            $query .= "
                WHERE 
                    stk.reference LIKE '{$term}'
                    OR stk.ean13 LIKE '{$term}'
                    OR stk.upc LIKE '{$term}'
                    OR stk.isbn LIKE '{$term}'
                    OR stk.mpn LIKE '{$term}'
                    OR pl.name LIKE '{$term}'
            ";
        }

        if ($orderBy) {
            $orderColumns = [];
            foreach ($orderBy as $orderStat) {
                $orderColumn = $orderStat['name'] ?? 'id';
                $orderType = $orderStat['dir'] ?? 'desc';

                $orderColumns[] = "{$orderColumn} {$orderType}";
            }
            $query .= "ORDER BY " . implode(',', $orderColumns);
        } else {
            $query .= '
                ORDER BY stkmvt.date_add DESC
            ';
        }

        $list = $conn->executeQuery($query)->fetchAllAssociative();

        if ($list) {
            $totalRows = count($list);
            $list = array_slice($list, $start, $length);
            $totalPages = ceil($totalRows / $length);
            $currentPage = 0 == $start ? 1 : ceil($length / $start);

            foreach ($list as &$item) {
                $product = new \Product($item['product_id']);
                $comb = $product->getAttributeCombinationsById($item['product_attribute_id'], $id_lang);
                $document = $this->getDocument($item);
                $item['ref_document'] = $document['ref_document'];
                $item['date_document'] = $document['date_document'];
                $item['img_url'] = $this->getProductImageUrl($item['product_id']);
                $item['actions'] = '';
                if ($comb) {
                    $item['combination'] = implode(' ', array_map(fn($attr) => $attr['attribute_name'], $comb));
                } else {
                    $item['combination'] = '--';
                }
            }
        } else {
            $totalRows = 0;
            $totalPages = 0;
            $currentPage = 1;
            $list = [];
        }

        return [
            'currentPage' => $currentPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'totalFiltered' => $totalRows,
            'data' => $list,
        ];
    }

    public function getDocument($item)
    {
        if ($item['id_order']) {
            return $this->getOrder($item['id_order']);
        } elseif ($item['id_supply_order']) {
            return $this->getSupplyOrder($item['id_supply_order']);
        }

        return [
            'ref_document' => '--',
            'date_document' => '--',
        ];
    }

    public function getSupplyOrder($id_supply_order)
    {
        $supplyOrder = new \SupplyOrder($id_supply_order);

        if (!\Validate::isLoadedObject($supplyOrder)) {
            return [
                'ref_document' => '--',
                'date_document' => '--',
            ];
        }

        $date = (new \DateTime($supplyOrder->date_add))->format('dd/mm/YY');
        return [
            'ref_document' => $supplyOrder->reference,
            'date_document' => $supplyOrder->date_add,
            'date_document_it' => $date,
        ];
    }

    public function getOrder($id_order)
    {
        $order = new \Order($id_order);
        if (!\Validate::isLoadedObject($order)) {
            return [
                'ref_document' => '--',
                'date_document' => '--',
            ];
        }

        $date = (new \DateTime($order->date_add))->format('dd/mm/YY');
        return [
            'ref_document' => $order->reference,
            'date_document' => $order->date_add,
            'date_document_it' => $date,
        ];
    }

    public function getProductImageUrl($id_product)
    {
        $no_image = '/img/404.gif';
        $product = new \Product($id_product, false, $this->context->getContext()->language->id);
        if (!\Validate::isLoadedObject($product)) {
            return $no_image;
        }

        $cover = \Product::getCover($id_product);
        if (!$cover) {
            return $no_image;
        }
        $id_image = $cover['id_image'];
        $image = new \Image($id_image);
        if (!\Validate::isLoadedObject($image)) {
            return $no_image;
        }
        $dir_path = \Image::getImgFolderStatic($id_image);
        $format = $image->image_format;
        $image_default = '/img/p/' . $dir_path . $id_image . '-small_default.' . $format;
        if (!file_exists(_PS_ROOT_DIR_ . $image_default)) {
            $image_default = $no_image;
        }

        return $image_default;
    }
}