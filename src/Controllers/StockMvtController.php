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

namespace MpSoft\MpStockAdv\Controllers;

use MpSoft\MpStockAdv\Services\MenuDataService;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StockMvtController extends FrameworkBundleAdminController
{
    private LegacyContext $context;
    private MenuDataService $menuDataService;
    private Request $request;

    // Pagina principale movimenti magazzino
    public function indexAction(Request $request): Response
    {
        $this->request = $request;

        $data = $request->getContent();
        $data = json_decode($data, true);

        $this->menuDataService = $this->get('MpSoft\MpStockAdv\Services\MenuDataService');
        $this->context = $this->get('PrestaShop\PrestaShop\Adapter\LegacyContext');
        $this->menuDataService->setTitle('Movimenti di magazzino');
        $this->menuDataService->setIcon('inventory');
        $this->menuDataService->injectMenuData(
            [
                [
                    'icon' => 'inventory',
                    'label' => 'Movimenti',
                    'children' => [
                        [
                            'icon' => 'add',
                            'label' => 'Nuovo Movimento',
                            'href' => 'javascript:newMovement();',
                        ],
                        [
                            'icon' => 'download',
                            'label' => 'Importa Giacenze',
                            'href' => 'javascript:importStockAvailable();',
                        ],
                    ],
                ],
                [
                    'icon' => 'settings',
                    'label' => 'Impostazioni',
                    'children' => [
                        [
                            'icon' => 'tune',
                            'label' => 'Preferenze',
                            'href' => 'javascript:showModalPreferences();',
                        ],
                        [
                            'icon' => 'lock_open',
                            'label' => 'Permessi',
                            'href' => 'javascript:showModalPermissions();',
                        ],
                    ],
                ],
            ]
        );

        return $this->render(
            '@Modules/mpstockadv/views/twig/Controllers/StockMvtController.index.html.twig',
            [
                'toolbarMenuHtml' => $this->menuDataService->renderMenu(),
            ]
        );
    }

    public function loadModalMovementsAction(): Response
    {
        $this->context = $this->get('PrestaShop\PrestaShop\Adapter\LegacyContext');
        $conf = $this->get('MpSoft\MpStockAdv\Services\MpStockAdvConfiguration');

        $html = $this->render(
            '@Modules/mpstockadv/views/twig/Components/StockMvt.dialog.movements.html.twig',
            [
                'IMG_URL_404' => '/img/404.gif',
                'id_dialog' => 'stock-mvt-dialog',
                'stockMvtReasons' => $conf->getStockMvtReasons(),
                'current_mvt_reason' => $conf->get('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON'),
                'warehouses' => $conf->getWarehouses(),
                'current_warehouse' => $conf->get('MPSTOCKADV_CURRENT_WAREHOUSE'),
            ]
        );

        return $this->json([
            'success' => true,
            'html' => $html->getContent(),
        ]);
    }

    public function getOrdersAction(Request $request): Response
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->getDoctrine()->getConnection();
        $prefix = _DB_PREFIX_;
        $table = $prefix.'orders';
        $validIdOrderStates = implode(',', $this->getValidIdOrdersStates());
        $sql = <<<QUERY
            SELECT * 
                FROM {$table} 
                WHERE current_state IN ($validIdOrderStates)
                ORDER BY id_order ASC;
        QUERY;
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();
        $orderList = [];
        foreach ($rows as $row) {
            $orderList[] = $row['id_order'];
        }

        return $this->json($orderList);
    }

    protected function getValidIdOrdersStates(): array
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->getDoctrine()->getConnection();
        $id_lang = (int) $this->context->getContext()->language->id;
        $table = _DB_PREFIX_.'order_state_lang';
        $sql = <<<QUERY
            SELECT 
                id_order_state
            FROM {$table}
            WHERE name NOT LIKE :null1
            AND name NOT LIKE :null2
            AND id_lang = :id_lang
            ORDER BY id_order_state ASC
        QUERY;
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('null1', '%annullato%', \PDO::PARAM_STR);
        $stmt->bindValue('null2', '%cancellato%', \PDO::PARAM_STR);
        $stmt->bindValue('id_lang', $id_lang, \PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();
        $validIdOrderStates = [];
        foreach ($rows as $row) {
            $validIdOrderStates[] = $row['id_order_state'];
        }

        return $validIdOrderStates;
    }

    public function getValidOrderStatesAction(): Response
    {
        $validIdOrderStates = $this->getValidIdOrdersStates();

        return $this->json($validIdOrderStates);
    }
}
