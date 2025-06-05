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
use Twig\Environment;

final class StockMvtInitStockImporterService
{
    private string $prefix;
    private $context;
    private Connection $conn;
    private Environment $twig;

    public function __construct(
        Connection $connection,
        LegacyContext $legacyContext,
        Environment $twig,
    ) {
        $this->prefix = _DB_PREFIX_;
        $this->context = $legacyContext->getContext();
        $this->conn = $connection;
        $this->twig = $twig;
    }

    /**
     * Legge il file JSON delle giacenze iniziali dalla cartella upload e restituisce l'array.
     *
     * @param string $filename Nome file (default 'stock_available.json')
     */
    public function getInitStockJson(string $filename = 'stock_available.json'): ?array
    {
        $path = _PS_MODULE_DIR_.'mpstockadv/upload/'.$filename;
        if (!file_exists($path)) {
            return null;
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Tronca le tabelle stock, stock_mvt e stock_available (operazione distruttiva!).
     *
     * @return bool true se tutte le truncate sono andate a buon fine
     */
    public function truncateStockTables(): bool
    {
        try {
            $this->conn->executeStatement('TRUNCATE TABLE '.$this->prefix.'stock');
            $this->conn->executeStatement('TRUNCATE TABLE '.$this->prefix.'stock_mvt');
            $this->conn->executeStatement('TRUNCATE TABLE '.$this->prefix.'stock_available');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Inserisce le quantità in magazzino usando StockAvailable::updateQuantity per ogni elemento del chunk.
     *
     * @param array $chunk Array di prodotti da aggiornare
     *
     * @return array ['success' => bool, 'tot_read' => int, 'tot_updated' => int]
     */
    public function importInitStock(array $chunk): array
    {
        $defMvt = (int) \Configuration::get('PS_STOCK_MVT_REASON_DEFAULT');
        $tot_read = count($chunk);
        $tot_updated = 0;

        foreach ($chunk as $item) {
            $idProduct = $item['id_product'] ?? null;
            $idProductAttribute = $item['id_product_attribute'] ?? null;
            $deltaQuantity = $item['quantity'] ?? null;
            $id_shop = $item['id_shop'] ?? null;
            if (null === $idProduct || null === $idProductAttribute || null === $deltaQuantity) {
                continue;
            }
            $params = [
                'id_order' => 0,
                'id_supply_order' => 0,
                'id_stock_mvt_reason' => $defMvt,
            ];
            $result = \StockAvailable::updateQuantity(
                (int) $idProduct,
                (int) $idProductAttribute,
                (int) $deltaQuantity,
                $id_shop ? (int) $id_shop : null,
                true,
                $params
            );
            if ($result) {
                ++$tot_updated;
            }
        }

        return [
            'success' => $tot_updated === $tot_read,
            'tot_read' => $tot_read,
            'tot_updated' => $tot_updated,
        ];
    }
}
