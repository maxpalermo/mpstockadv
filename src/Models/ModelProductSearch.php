<?php
namespace MpSoft\MpStockAdv\Models;

use Db;

class ModelProductSearch
{
    /**
     * Cerca prodotti e combinazioni per nome, riferimento, ean13, upc, isbn.
     */
    public static function search($q)
    {
        $q = pSQL($q);
        $sql = "
            SELECT p.id_product, pl.name as product_name, p.reference, p.ean13, p.upc, p.isbn,
                   pa.id_product_attribute, GROUP_CONCAT(DISTINCT al.name SEPARATOR ', ') as attribute_names,
                   pa.reference as attr_reference, pa.ean13 as attr_ean13, pa.upc as attr_upc, pa.isbn as attr_isbn
            FROM "._DB_PREFIX_."product p
            INNER JOIN "._DB_PREFIX_."product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = 1
            LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product = p.id_product
            LEFT JOIN "._DB_PREFIX_."product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
            LEFT JOIN "._DB_PREFIX_."attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = 1
            WHERE (
                p.reference LIKE '%$q%' OR pl.name LIKE '%$q%' OR p.ean13 LIKE '%$q%' OR p.upc LIKE '%$q%' OR p.isbn LIKE '%$q%' OR
                pa.reference LIKE '%$q%' OR pa.ean13 LIKE '%$q%' OR pa.upc LIKE '%$q%' OR pa.isbn LIKE '%$q%'
            )
            GROUP BY p.id_product, pa.id_product_attribute
            ORDER BY pl.name ASC
            LIMIT 30
        ";
        return Db::getInstance()->executeS($sql);
    }
}
