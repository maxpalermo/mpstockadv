<?php
namespace MpSoft\MpStockAdv\Helpers;

class StockHelper
{
    /**
     * Esempio di funzione helper per recuperare la giacenza di un prodotto in un magazzino
     */
    public static function getProductStockInWarehouse($idProduct, $idWarehouse)
    {
        // Usa le classi native di PrestaShop
        // Esempio: StockAvailable::getQuantityAvailableByProduct($idProduct, 0, $idWarehouse);
        return \StockAvailable::getQuantityAvailableByProduct($idProduct, 0, $idWarehouse);
    }
}
