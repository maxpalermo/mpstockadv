<?php

namespace MpSoft\MpStockAdv\Models;

class ModelProductSearch
{
    /**
     * Cerca prodotti e combinazioni per nome, riferimento, ean13, upc, isbn.
     */
    public static function search($q, $idLang = 0, $limit = 20)
    {
        $table = _DB_PREFIX_.'product';
        $pfx = _DB_PREFIX_;
        $q = pSQL($q);
        if (!$idLang) {
            $idLang = (int) \Context::getContext()->language->id;
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
            GROUP BY p.id_product, pa.id_product_attribute
            ORDER BY pl.name ASC
            LIMIT {$limit};
        QUERY;

        $list = \Db::getInstance()->executeS($sql);
        if ($list) {
            foreach ($list as &$row) {
                $id_product = (int) $row['id_product'];
                $id_product_attribute = (int) $row['id_product_attribute'];
                $wa = self::getLastWeightedAveragePrice($id_product, $id_product_attribute);
                $row['last_wa'] = $wa;
            }
        }

        return $list;
    }

    public static function getLastWeightedAveragePrice($id_product, $id_product_attribute = 0)
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
}
