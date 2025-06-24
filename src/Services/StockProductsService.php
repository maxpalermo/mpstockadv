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

namespace MpSoft\MpStockAdv\Services;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Adapter\LegacyContext;

class StockProductsService
{
    private $connection;
    private $prefix;
    private $context;
    private $locale;

    public function __construct(Connection $connection, LegacyContext $context)
    {
        $this->prefix = _DB_PREFIX_;
        $this->connection = $connection;
        $this->context = $context;
        $this->locale = \Tools::getContextLocale($this->context->getContext());
    }

    public function getStockProducts($term = '', $limit = 20, $page = 1)
    {
        $id_lang = (int) $this->context->getContext()->language->id;
        $conn = $this->connection;
        $pfx = $this->prefix;
        $query = "
            SELECT 
                stkmvt.id_stock_mvt as `id`,
                stkmvt.date_add as `date`,
                IF (stkmvtrsn.sign = 1, '+', '-') as `sign`,
                stkmvtrsn.sign as `sign_value`,
                stkmvtrsnlang.name as `type`,
                stk.id_product as `product_id`,
                stk.id_product_attribute as `product_attribute_id`,
                pl.name as `product_name`,
                stk.reference as `reference`,
                stk.ean13 as `ean13`,
                stk.isbn as `isbn`,
                stk.upc as `upc`,
                stk.mpn as `mpn`,
                stk.physical_quantity as `stock_after`,
                stkmvt.physical_quantity as `movement`,
                (stk.physical_quantity - (stkmvt.physical_quantity * stkmvtrsn.sign)) as `stock_before`,
                COALESCE(CONCAT(e.firstname, ' ', e.lastname), '--') as `employee`
            FROM
                {$pfx}stock_mvt as stkmvt
            INNER JOIN {$pfx}stock as stk ON (stkmvt.id_stock=stk.id_stock)
            INNER JOIN {$pfx}stock_mvt_reason as stkmvtrsn ON (stkmvt.id_stock_mvt_reason=stkmvtrsn.id_stock_mvt_reason)
            INNER JOIN {$pfx}stock_mvt_reason_lang as stkmvtrsnlang ON (stkmvtrsn.id_stock_mvt_reason=stkmvtrsnlang.id_stock_mvt_reason AND stkmvtrsnlang.id_lang={$id_lang})
            INNER JOIN {$pfx}product_lang as pl ON (stk.id_product=pl.id_product AND pl.id_lang={$id_lang})
            LEFT JOIN {$pfx}employee as e ON (stkmvt.id_employee=e.id_employee)
        ";

        if ($term) {
            $term = '%'.pSQL($term).'%';
            $query .= "
                WHERE 
                    stk.reference LIKE '{$term}'
                    OR stk.ean13 LIKE '{$term}'
                    OR stk.upc LIKE '{$term}'
                    OR stk.isbn LIKE '{$term}'
                    OR stk.mpn LIKE '{$term}'
                    OR stk.name LIKE '{$term}'
            ";
        }

        $query .= '
            ORDER BY stkmvt.date_add DESC
        ';

        $list = $conn->executeQuery($query)->fetchAllAssociative();
        if ($list) {
            $totalRows = count($list);
            $offset_start = ($page - 1) * $limit;
            $list = array_slice($list, $offset_start, $limit);
            $totalPages = ceil($totalRows / $limit);
            $currentPage = $page;

            foreach ($list as &$item) {
                $product = new \Product($item['product_id']);
                $comb = $product->getAttributeCombinationsById($item['product_attribute_id'], $id_lang);
                if ($comb) {
                    $item['img_url'] = $this->getProductImageUrl($item['product_id']);
                    $item['combination'] = implode(' ', array_map(fn ($attr) => $attr['attribute_name'], $comb));
                    $item['actions'] = '';
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
            'data' => $list,
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
        $image_default = '/img/p/'.$dir_path.$id_image.'-small_default.'.$format;
        if (!file_exists(_PS_ROOT_DIR_.$image_default)) {
            $image_default = $no_image;
        }

        return $image_default;
    }
}
