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
                            'label' => 'Importa',
                            'href' => 'javascript:void(0);',
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

    public function refreshTableDataAction(): Response
    {
        $tableContent = [];
        for ($i = 2; $i <= 51; ++$i) {
            $movement = rand(1, 50);
            $sign = rand(0, 1) ? '+' : '-';
            $stock_before = rand(5, 300);
            if ('+' === $sign) {
                $stock_after = $stock_before + $movement;
                $type = 'Entrata';
            } else {
                $stock_after = max(0, $stock_before - $movement);
                $type = 'Uscita';
            }
            $tableContent[] = [
                'id' => $i,
                'date' => date('Y-m-d', strtotime('2025-06-18 -'.rand(0, 100).' days')),
                'type' => $type,
                'sign' => $sign,
                'reference' => 'AB'.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'ean13' => (string) rand(1000000000000, 9999999999999),
                'product' => 'Prodotto '.$i,
                'combination' => 'Comb '.rand(1, 10),
                'warehouse' => 'Magazzino '.rand(1, 5),
                'movement' => $movement,
                'stock_before' => $stock_before,
                'stock_after' => $stock_after,
                'employee' => rand(0, 1) ? 'Massimiliano Palermo' : 'Mario Rossi',
                'actions' => '...',
            ];
        }

        return $this->json([
            'data' => $tableContent,
            'total' => count($tableContent),
        ]);
    }

    public function loadModalMovementsAction(): Response
    {
        $this->context = $this->get('PrestaShop\PrestaShop\Adapter\LegacyContext');
        $conf = $this->get('MpSoft\MpStockAdv\Services\MpStockAdvConfiguration');

        $html = $this->render(
            '@Modules/mpstockadv/views/twig/Components/StockMvt.dialog.movements.html.twig',
            [
                'APP_IMG_URL' => 'https://picsum.photos/100',
                'id_dialog' => 'stock-mvt-dialog',
                'stockMvtReasons' => $conf->getStockMvtReasons(),
                'default_stock_mvt_reason' => $conf->get('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON'),
                'warehouses' => $conf->getWarehouses(),
                'current_warehouse' => $conf->get('MPSTOCKADV_CURRENT_WAREHOUSE'),
            ]
        );

        return $this->json([
            'success' => true,
            'html' => $html->getContent(),
        ]);
    }

    // Endpoint AJAX per ricerca prodotto (autocomplete)
    public function productSearchAction(Request $request): Response
    {
        $q = $request->query->get('q', '');
        $idLang = (int) $this->context->getContext()->language->id;
        // Usa il servizio centralizzato per la ricerca autocomplete
        $service = new ProductAutocompleteService(
            $this->getDoctrine()->getConnection()
        );
        $items = $service->search($q, $idLang, 20);

        return $this->json(['results' => $items]);
    }

    // Endpoint AJAX per tabella movimenti
    public function ajaxListAction(Request $request): Response
    {
        $prefix = _DB_PREFIX_;
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, (int) $request->query->get('perPage', 20));
        $search = trim($request->query->get('search', ''));

        $offset = ($page - 1) * $perPage;
        $conn = $this->getDoctrine()->getConnection();
        $params = [];
        $where = '';
        if ($search) {
            $where = 'WHERE sm.id_stock_mvt LIKE :search OR sm.id_product LIKE :search OR smr_lang.name LIKE :search';
            $params['search'] = "%$search%";
        }
        // Recupera la lingua corrente
        $idLang = (int) $this->context->getContext()->language->id;
        $sql = "SELECT sm.*, smr_lang.name AS reason_name
                FROM {$prefix}stock_mvt sm
                LEFT JOIN {$prefix}stock_mvt_reason smr ON sm.id_stock_mvt_reason = smr.id_stock_mvt_reason
                LEFT JOIN {$prefix}stock_mvt_reason_lang smr_lang ON smr_lang.id_stock_mvt_reason = smr.id_stock_mvt_reason AND smr_lang.id_lang = :id_lang
                $where
                ORDER BY sm.date_add DESC
                LIMIT :offset, :limit";
        $params['offset'] = $offset;
        $params['limit'] = $perPage;
        $params['id_lang'] = $idLang;

        $stmt = $conn->prepare($sql);
        foreach ($params as $k => $v) {
            $type = ('offset' === $k || 'limit' === $k || 'id_lang' === $k) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($k, $v, $type);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Conta totale per paginazione (non serve join su _lang)
        $countSql = "SELECT COUNT(*) FROM {$prefix}stock_mvt sm LEFT JOIN {$prefix}stock_mvt_reason smr ON sm.id_stock_mvt_reason = smr.id_stock_mvt_reason $where";
        $countStmt = $conn->prepare($countSql);
        if ($search) {
            $countStmt->bindValue('search', "%$search%", \PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return $this->json([
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
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

    public function productAutocompleteAction(Request $request)
    {
        $this->productAutocompleteService = new ProductAutocompleteService($this->getDoctrine()->getConnection());

        $data = $request->request->all();
        $search = $data['search'] ?? '';
        $limit = $data['limit'] ?? 10;
        $offset = $data['offset'] ?? 0;
        $order = $data['order'] ?? 'ASC';
        $order_by = $data['order_by'] ?? 'name';
        $id_lang = (int) $this->context->getContext()->language->id;

        return $this->json(
            $this->productAutocompleteService->search($search, $id_lang, 20)
        );
    }

    public function injectMenuData()
    {
        $menuDataService = new MenuDataService($this->router, $this->context);
        $menuDataService->injectMenuData([
            'icon' => 'home',
            'label' => 'Movimenti',
            'children' => [
                [
                    'icon' => 'add',
                    'label' => 'Nuovo Movimento',
                    'href' => 'javascript:void(0);',
                    'action' => 'showNewMovement',
                    'dialogId' => $idDialog ?? '',
                ],
                [
                    'icon' => 'download',
                    'label' => 'Importa',
                    'href' => 'javascript:void(0);',
                ],
            ],
        ]);
    }
}
