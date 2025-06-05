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

class ProductAutocompleteService
{
    private $conn;
    private $prefix;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
        $this->prefix = _DB_PREFIX_;
    }

    /**
     * Cerca prodotti e combinazioni per l'autocomplete.
     *
     * @return array
     */
    public function search($q, $idLang, $limit = 20)
    {
        if (mb_strlen($q) < 3) {
            return [];
        }
        $results = \MpSoft\MpStockAdv\Models\ModelProductSearch::search($q, $idLang, $limit);
        $items = [];
        foreach ($results as $row) {
            $label = $row['product_name'];
            if ($row['id_product_attribute']) {
                $label .= ' — '.$row['attribute_names'];
            }
            $label .= ' [Rif: '.($row['attr_reference'] ?: $row['reference']).']';
            if ($row['ean13'] || $row['attr_ean13']) {
                $label .= ' EAN: '.($row['attr_ean13'] ?: $row['ean13']);
            }
            if ($row['upc'] || $row['attr_upc']) {
                $label .= ' UPC: '.($row['attr_upc'] ?: $row['upc']);
            }
            if ($row['isbn'] || $row['attr_isbn']) {
                $label .= ' ISBN: '.($row['attr_isbn'] ?: $row['isbn']);
            }
            // Recupera l'immagine di default
            $image_url = null;
            $id_product = (int) $row['id_product'];
            $id_product_attribute = (int) $row['id_product_attribute'];

            // Costruisci URL immagine (usa link di PrestaShop)
            $cover = \Product::getCover($id_product);
            if ($cover) {
                $path = \Image::getImgFolderStatic($cover['id_image']);
                $image_url = _PS_BASE_URL_.__PS_BASE_URI__.'img/p/'.$path.'/'.$cover['id_image'].'-small_default.jpg';
            } else {
                $image_url = _PS_BASE_URL_.__PS_BASE_URI__.'img/404.gif';
            }

            $items[] = [
                'id' => json_encode([
                    'img_url' => $image_url,
                    'id_product' => $row['id_product'],
                    'id_product_attribute' => $row['id_product_attribute'],
                    'name' => $row['product_name'],
                    'attribute_names' => $row['attribute_names'],
                    'reference' => $row['attr_reference'] ?: $row['reference'],
                    'ean13' => $row['attr_ean13'] ?: $row['ean13'],
                    'upc' => $row['attr_upc'] ?: $row['upc'],
                    'isbn' => $row['attr_isbn'] ?: $row['isbn'],
                    'price_te' => $row['price_te'],
                    'tax_rate' => $row['tax_rate'],
                    'price_ti' => $row['price_te'] * (1 + $row['tax_rate'] / 100),
                ]),
                'text' => $label,
                'img' => $image_url,
                'name' => $row['product_name'],
                'combination' => $row['attribute_names'],
                'reference' => $row['attr_reference'] ?: $row['reference'],
                'ean13' => $row['attr_ean13'] ?: $row['ean13'],
                'upc' => $row['attr_upc'] ?: $row['upc'],
                'isbn' => $row['attr_isbn'] ?: $row['isbn'],
                'hasCombinations' => (int) $row['id_product_attribute'] > 0,
                'price_te' => $row['price_te'],
                'tax_rate' => $row['tax_rate'],
                'price_ti' => $row['price_te'] * (1 + $row['tax_rate'] / 100),
                'last_wa' => $row['last_wa'],
                'current_wa' => '0.00',
            ];
        }

        return $items;
    }
}
