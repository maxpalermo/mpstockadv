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

namespace MpSoft\MpStockAdv\Controller\Admin;

use MpSoft\MpStockAdv\Models\ModelStockMvtReason;
use MpSoft\MpStockAdv\Services\ProductAutocompleteService;
use MpSoft\MpStockAdv\Services\StockMvtInitStockImporterService;
use MpSoft\MpStockAdv\Services\StockMvtReasonFormService;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StockMvtController extends FrameworkBundleAdminController
{
    private LegacyContext $context;
    private StockMvtInitStockImporterService $stockMvtInitStockImporterService;
    private ProductAutocompleteService $productAutocompleteService;

    public function __construct(
        LegacyContext $context,
        StockMvtInitStockImporterService $stockMvtInitStockImporterService,
    ) {
        $this->context = $context;
        $this->stockMvtInitStockImporterService = $stockMvtInitStockImporterService;
    }

    // Pagina principale movimenti magazzino
    public function indexAction(Request $request): Response
    {
        return $this->render(
            '@Modules/mpstockadv/views/templates/admin/stockmvt/stockmvt.index.html.twig',
            [
                'id_dialog' => StockMvtReasonFormService::getIdDialog(),
                'base_uri_site' => $this->context->getContext()->shop->getBaseUri(),
                'logo_src' => \Configuration::get('PS_LOGO'),
                'current_warehouse' => (int) \Configuration::get('MPSTOCKADV_DEFAULT_WAREHOUSE'),
            ]
        );
    }

    public function renderFormAction()
    {
        $service = new StockMvtReasonFormService(
            $this->getDoctrine()->getConnection(),
            $this->get('twig'),
            $this->context
        );
        $form = $service->renderForm();

        return $this->json(['formHtml' => $form, 'idDialog' => $service->getIdDialog()]);
    }

    // Endpoint AJAX per salvataggio movimento
    public function ajaxSaveAction(Request $request): Response
    {
        $data = $request->request->all();
        $this->stockMvtInitStockImporterService->importInitStock($data);

        return $this->json(['success' => true, 'message' => 'Movimento salvato (stub)']);
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

    // Endpoint AJAX per motivi movimento
    public function ajaxReasonsAction(Request $request): Response
    {
        // TODO: implementa la logica per restituire i motivi
        return $this->json([
            ['id' => 1, 'name' => 'Carico'],
            ['id' => 2, 'name' => 'Scarico'],
        ]);
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

    public function importOrdersMvtAction(Request $request): Response
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->getDoctrine()->getConnection();

        $orderIds = $request->request->get('order_ids');
        $orderIds = explode(',', $orderIds);
        $orderIds = array_map('intval', $orderIds);

        $prefix = _DB_PREFIX_;
        $table = $prefix.'order_detail';

        $sql = <<<QUERY
            SELECT * 
                FROM {$table} 
                WHERE id_order IN (:orderIds)
                ORDER BY id_order ASC;
        QUERY;
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('orderIds', $orderIds, \PDO::PARAM_STR);
        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();
        $orderList = [];
        foreach ($rows as $row) {
            $orderList[] = $row['id_order'];
        }

        return $this->json($orderList);
    }

    public function saveMvtAction(Request $request): Response
    {
        $data = $request->request->all();
        $product = json_decode($data['id_product'], true);
        $mvtReason = new ModelStockMvtReason($data['id_stock_mvt_reason']);
        if (!\Validate::isLoadedObject($mvtReason)) {
            return $this->json(['error' => 'Tipo di movimento non valido']);
        }

        $id_shop = (int) $this->context->getContext()->shop->id;
        $id_employee = (int) $this->context->getContext()->employee->id;
        $employee = new \Employee($id_employee);
        if (!\Validate::isLoadedObject($employee)) {
            return $this->json([
                'success' => false,
                'errorCode' => 404,
                'errorMessage' => 'Employee not found',
            ]);
        }

        $sign = $mvtReason->sign;
        $physical_quantity = (int) $data['physical_quantity'];
        $id_warehouse = (int) $data['id_warehouse'];
        $idProduct = $product['id_product'];
        $idProductAttribute = $product['id_product_attribute'];
        $idStockMvtReason = (int) $data['id_stock_mvt_reason'];
        $reference = $product['reference'] ?? '';
        $ean13 = $product['ean13'] ?? '';
        $upc = $product['upc'] ?? '';
        $isbn = $product['isbn'] ?? '';
        $mpn = $product['mpn'] ?? '';
        $price_te = $product['price_te'] ?? 0;
        $last_wa = $data['last_wa'] ?? 0;
        $current_wa = $data['current_wa'] ?? 0;
        $referer = $data['referer'] ?? 0;
        $deltaQuantity = $physical_quantity * $sign;

        $params = [
            'id_order' => 0,
            'id_supply_order' => 0,
            'id_stock_mvt_reason' => $idStockMvtReason,
            'id_employee' => $id_employee,
            'employee_firstname' => $employee->firstname,
            'employee_lastname' => $employee->lastname,
            'physical_quantity' => $physical_quantity,
            'date_add' => date('Y-m-d H:i:s'),
            'sign' => $sign,
            'price_te' => $price_te,
            'last_wa' => $last_wa,
            'current_wa' => $current_wa,
            'referer' => $referer,
        ];

        $result = \StockAvailable::updateQuantity(
            $idProduct,
            $idProductAttribute,
            $deltaQuantity,
            $id_shop,
            true,
            $params
        );

        return $this->json([
            'success' => $result,
        ]);
    }

    public function getStockId($id_warehouse, $idProduct, $idProductAttribute)
    {
        $table = _DB_PREFIX_.\Stock::$definition['table'];
        $identifier = \Stock::$definition['primary'];
        $sql = "SELECT {$identifier} FROM {$table} WHERE id_warehouse = :id_warehouse AND id_product = :id_product AND id_product_attribute = :id_product_attribute";
        $stmt = $this->getDoctrine()->getConnection()->prepare($sql);
        $stmt->bindValue('id_warehouse', $id_warehouse);
        $stmt->bindValue('id_product', $idProduct);
        $stmt->bindValue('id_product_attribute', $idProductAttribute);
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();
        if ($row) {
            return (int) $row[$identifier];
        }

        return 0;
    }

    public function truncateTablesAction()
    {
        return $this->json([
            'result' => $this->stockMvtInitStockImporterService->truncateStockTables(),
        ]);
    }

    public function getJsonInitStockAction()
    {
        return $this->json([
            'data' => $this->stockMvtInitStockImporterService->getInitStockJson(),
        ]);
    }

    public function importInitStockAction(Request $request)
    {
        $data = $request->request->all();
        $chunk = $data['chunk'] ?? [];

        return $this->json(
            $this->stockMvtInitStockImporterService->importInitStock($chunk),
        );
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
}
