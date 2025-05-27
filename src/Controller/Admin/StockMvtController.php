<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StockMvtController extends FrameworkBundleAdminController
{
    // Pagina principale movimenti magazzino
    public function indexAction(Request $request): Response
    {
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/stock_mvt/index.html.twig');
    }

    // Endpoint AJAX per tabella movimenti
    public function ajaxListAction(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, (int) $request->query->get('perPage', 20));
        $search = trim($request->query->get('search', ''));

        $offset = ($page - 1) * $perPage;
        $conn = $this->getDoctrine()->getConnection();
        $params = [];
        $where = '';
        if ($search) {
            $where = 'WHERE sm.id_stock_mvt LIKE :search OR sm.id_product LIKE :search OR smr.name LIKE :search';
            $params['search'] = "%$search%";
        }

        $sql = "SELECT sm.*, smr.name AS reason_name FROM stock_mvt sm
                LEFT JOIN stock_mvt_reason smr ON sm.id_stock_mvt_reason = smr.id_stock_mvt_reason
                $where
                ORDER BY sm.date_add DESC
                LIMIT :offset, :limit";
        $params['offset'] = $offset;
        $params['limit'] = $perPage;

        $stmt = $conn->prepare($sql);
        foreach ($params as $k => $v) {
            $type = ('offset' === $k || 'limit' === $k) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($k, $v, $type);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Conta totale per paginazione
        $countSql = "SELECT COUNT(*) FROM stock_mvt sm LEFT JOIN stock_mvt_reason smr ON sm.id_stock_mvt_reason = smr.id_stock_mvt_reason $where";
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
}
