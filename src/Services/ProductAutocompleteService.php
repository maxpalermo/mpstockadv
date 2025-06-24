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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductAutocompleteService
{
    private $connection;
    private $prefix;
    private $context;
    private $locale;
    private $stockManager;

    public function __construct(Connection $connection, LegacyContext $context, StockManagerService $stockManager)
    {
        $this->prefix = _DB_PREFIX_;
        $this->connection = $connection;
        $this->context = $context;
        $this->stockManager = $stockManager;
        $this->locale = \Tools::getContextLocale($this->context->getContext());
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

        (new JsonResponse(
            $response,
            $statusCode
        ))->send();

        exit;
    }

    public function searchAction(Request $request)
    {
        $result = $this->search($request);

        $this->setJsonResponse($result);
    }

    /**
     * Cerca prodotti e combinazioni per l'autocomplete.
     *
     * @return array
     */
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $idLang = $request->get('id_lang', $this->context->getContext()->language->id);
        $limit = $request->get('limit', 20);

        if (mb_strlen($q) < 3) {
            return [];
        }
        $results = $this->getProductsList($q, $idLang, $limit);
        $items = [];
        foreach ($results as $row) {
            $label = $row['product_name'];
            // Recupera l'immagine di default
            $image_url = null;
            $id_product = (int) $row['id_product'];

            // Costruisci URL immagine (usa link di PrestaShop)
            $cover = \Product::getCover($id_product);
            if ($cover) {
                $path = \Image::getImgFolderStatic($cover['id_image']);
                $image_url = _PS_BASE_URL_.__PS_BASE_URI__.'img/p/'.$path.'/'.$cover['id_image'].'-small_default.jpg';
            } else {
                $image_url = _PS_BASE_URL_.__PS_BASE_URI__.'img/404.gif';
            }

            $price_ti = $row['price_te'] * (1 + $row['tax_rate'] / 100);

            // Ottieni la valuta corrente
            $currency = $this->context->getContext()->currency;
            $isoCode = $currency->iso_code;

            // Formatta il prezzo
            $price_ti_currency = $this->locale->formatPrice($price_ti, $isoCode);

            $items[] = [
                'id' => "{$row['id_product']}-{$row['id_product_attribute']}",
                'text' => $label,
                'img_url' => $image_url,
                'id_product' => $row['id_product'],
                'id_product_attribute' => $row['id_product_attribute'],
                'name' => $row['product_name'],
                'combination' => $row['attribute_names'],
                'reference' => $row['attr_reference'] ?? '',
                'ean13' => $row['attr_ean13'] ?? '',
                'upc' => $row['attr_upc'] ?? '',
                'isbn' => $row['attr_isbn'] ?? '',
                'mpn' => $row['attr_mpn'] ?? '',
                'hasCombinations' => (int) $row['id_product_attribute'] > 0,
                'stock' => \StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute']),
                'price_te' => $row['price_te'],
                'tax_rate' => $row['tax_rate'],
                'price_ti' => $price_ti,
                'price_ti_currency' => $price_ti_currency,
                'last_wa' => $row['last_wa'],
                'current_wa' => '0.00',
            ];
        }

        return $items;
    }

    public function getProductsList($q, $idLang = 0, $limit = 20)
    {
        $table = _DB_PREFIX_.'product';
        $pfx = _DB_PREFIX_;
        $q = pSQL($q);
        if (!$idLang) {
            $idLang = (int) $this->context->getContext()->language->id;
        }

        $sql = <<<QUERY
            SELECT
                p.id_product, pl.name as product_name, p.reference, p.ean13, p.upc, p.isbn, p.price as price_te, '0' as last_wa,
                pa.id_product_attribute, GROUP_CONCAT(DISTINCT al.name SEPARATOR ', ') as attribute_names,
                pa.reference as attr_reference, pa.ean13 as attr_ean13, pa.upc as attr_upc, pa.isbn as attr_isbn,
                group_concat(distinct tax.rate) as tax_rate
            FROM $table p
            INNER JOIN {$pfx}product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = {$idLang}
            LEFT JOIN {$pfx}product_attribute pa ON pa.id_product = p.id_product
            LEFT JOIN {$pfx}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
            LEFT JOIN {$pfx}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idLang}
            LEFT JOIN {$pfx}tax_rule tr ON tr.id_tax_rules_group = p.id_tax_rules_group
            LEFT JOIN {$pfx}tax tax ON tax.id_tax = tr.id_tax
            WHERE (
                p.reference LIKE '%$q%' OR pl.name LIKE '%$q%' OR p.ean13 LIKE '%$q%' OR p.upc LIKE '%$q%' OR p.isbn LIKE '%$q%' OR
                pa.reference LIKE '%$q%' OR pa.ean13 LIKE '%$q%' OR pa.upc LIKE '%$q%' OR pa.isbn LIKE '%$q%'
            )
                AND p.active=1
            GROUP BY p.id_product, pa.id_product_attribute
            ORDER BY pl.name ASC
            LIMIT {$limit};
        QUERY;

        $list = \Db::getInstance()->executeS($sql);
        if ($list) {
            foreach ($list as &$row) {
                $id_product = (int) $row['id_product'];
                $id_product_attribute = (int) $row['id_product_attribute'];
                $wa = $this->getLastWeightedAveragePrice($id_product, $id_product_attribute);
                $row['last_wa'] = $wa;
            }
        }

        return $list;
    }

    public function getLastWeightedAveragePrice($id_product, $id_product_attribute = 0)
    {
        $table = _DB_PREFIX_.'stock';
        $sql =
        <<<QUERY
            SELECT price_te
            FROM $table
            WHERE id_product = $id_product
            AND id_product_attribute = $id_product_attribute
            ORDER BY id_stock DESC
        QUERY;

        return (float) \Db::getInstance()->getValue($sql);
    }

    public function saveAction(Request $request)
    {
        $data = $request->getContent();
        $data = json_decode($data, true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid data format', 'data' => $data], 400);
        }

        return new JsonResponse($this->stockManagerUpdate($data), 200);
    }

    public function stockManagerUpdate($data)
    {
        $id_warehouse = $data['warehouse_id'];
        $id_product = $data['product_id'];
        $id_product_attribute = $data['product_attribute_id'];
        $id_stock_mvt_reason = $data['stock_mvt_reason_id'];
        $quantity = $data['product_quantity'];
        $price_te = (float) ($data['product_price_te'] ?? 0);

        $stockMvtReason = new \StockMvtReason($id_stock_mvt_reason);
        $sign = (int) $stockMvtReason->sign;

        $stockManager = \StockManagerFactory::getManager();
        $stockExists = \StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);

        $result = $this->stockManager->updateStock($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity, $id_warehouse, $price_te);
        if ($result) {
            $movement = $this->stockManager->addMovement($id_product, $id_product_attribute, $id_stock_mvt_reason, $quantity, $price_te, $id_warehouse);
        }

        $this->setJsonResponse([
            'success' => $movement,
            'updated' => $result,
        ]);

        if ($stockExists) {
            // Aggiorna la quantità esistente
            \StockAvailable::updateQuantity($id_product, $id_product_attribute, $quantity * $sign);
        } else {
            // Crea una nuova riga in ps_stock_available (non in ps_stock)
            \StockAvailable::setQuantity($id_product, $id_product_attribute, $quantity * $sign, null, false);
        }

        $stock_after = \StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);
        $sign = (int) $stockMvtReason->sign;
        $movement = $quantity * $sign;

        if ($stock_after < 0) {
            $quantity = 0;
        }

        // Se Advanced Stock Management è attivo, aggiorna anche ps_stock
        if (\Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $warehouse = new \Warehouse($id_warehouse);
            if (-1 == $sign) {
                $result = $stockManager->removeProduct(
                    $id_product,
                    $id_product_attribute,
                    $warehouse,
                    $quantity,
                    $id_stock_mvt_reason,
                    true,
                    0
                );
            } else {
                $result = $stockManager->addProduct(
                    $id_product,
                    $id_product_attribute,
                    $warehouse,
                    $quantity,
                    $id_stock_mvt_reason,
                    $price_te,
                    true,
                    0
                );
            }

            $stock_after = (int) \StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);
            $pfx = _DB_PREFIX_;
            $query = "
                SELECT a.id_stock_mvt
                FROM {$pfx}stock_mvt a
                INNER JOIN {$pfx}stock b ON (a.id_stock=b.id_stock)
                WHERE a.id_stock_mvt_reason = {$id_stock_mvt_reason}
                AND b.id_product = {$id_product}
                AND b.id_product_attribute = {$id_product_attribute}
                AND b.id_warehouse = {$id_warehouse}
                AND a.sign = {$sign}
                ORDER BY a.id_stock_mvt DESC
                ";

            $id_stock_mvt = \Db::getInstance()->getValue($query);
            if ($id_stock_mvt) {
                \Db::getInstance()->update(
                    'stock_mvt',
                    [
                        'stock_before' => (int) $stockExists,
                        'stock_after' => (int) $stock_after,
                    ],
                    'id_stock_mvt = '.(int) $id_stock_mvt
                );
            }

            return [
                'success' => true,
                'result' => $result,
                'data' => $data,
            ];
        }

        return [
            'success' => true,
            'result' => true,
            'data' => $data,
        ];
    }
}
