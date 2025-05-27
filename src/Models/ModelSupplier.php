<?php

namespace MpSoft\MpStockAdv\Models;

class ModelSupplier
{
    public static function getAll()
    {
        $sql = 'SELECT id_supplier, name FROM '._DB_PREFIX_.'supplier ORDER BY name ASC';
        $rows = \Db::getInstance()->executeS($sql);
        // Se la colonna logo non esiste, fallback su id_supplier.jpg
        foreach ($rows as &$row) {
            $image_url = _PS_SUPP_IMG_DIR_.$row['id_supplier'].'.jpg';

            // Verifica se l'immagine esiste
            if (file_exists($image_url)) {
                $image_path = \Context::getContext()->link->getSupplierImageLink(
                    $row['id_supplier'],
                    'supplier_default' // Tipo di immagine (vedi /img/su/)
                );
                $row['logo'] = $image_path;
            } else {
                $row['logo'] = _PS_BASE_URL_.__PS_BASE_URI__.'img/404.gif';
            }
        }

        return $rows;
    }
}
