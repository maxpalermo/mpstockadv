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

class StockMvtReasonFormService
{
    private $conn;
    private $prefix;
    private $context;
    private $twig;

    public function __construct(Connection $conn, Environment $twig, LegacyContext $legacyContext)
    {
        $this->conn = $conn;
        $this->prefix = _DB_PREFIX_;
        $this->context = $legacyContext->getContext();
        $this->twig = $twig;
    }

    public function getStockMvtReasons()
    {
        $query = <<<QUERY
            SELECT a.id_stock_mvt_reason, b.name, a.sign, a.alias
            FROM {$this->prefix}stock_mvt_reason a
            LEFT JOIN {$this->prefix}stock_mvt_reason_lang b
                ON a.id_stock_mvt_reason = b.id_stock_mvt_reason
                AND b.id_lang = :id_lang
        QUERY;
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_lang', $this->context->language->id);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    public function getWarehouses()
    {
        $query = "SELECT * FROM {$this->prefix}warehouse";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    public function renderForm()
    {
        $template = '@Modules/mpstockadv/views/templates/admin/components/stockMovementForm.html.twig';
        $stockMvtReasons = $this->getStockMvtReasons();
        $warehouses = $this->getWarehouses();

        return $this->twig->render($template, [
            'APP_IMG_URL' => '/img/404.gif',
            'id_dialog' => $this->getIdDialog(),
            'stockMvtReasons' => $stockMvtReasons,
            'warehouses' => $warehouses,
            'current_warehouse' => (int) \Configuration::get('MPSTOCKADV_DEFAULT_WAREHOUSE'),
        ]);
    }

    public static function getIdDialog()
    {
        return 'stock-movement-dialog';
    }
}
