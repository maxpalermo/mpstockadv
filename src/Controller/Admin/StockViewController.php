<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use Doctrine\DBAL\Connection;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockViewController extends FrameworkBundleAdminController
{
    /**
     * @Route("/admin/mpstockadv/stock-view/redirect", name="mpstockadv_admin_stockview_redirect", methods={"GET"})
     */
    public function redirectToStockView(): Response
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 20);
        $search = $request->query->get('search', '');

        // Genera l'URL corretto per la stockview, incluso il token
        $url = $this->generateUrl('mpstockadv_admin_stockview', [
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
        ]);

        return $this->redirect($url);
    }

    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Route("/admin/mpstockadv/stock-view", name="mpstockadv_admin_stockview", methods={"GET"})
     */
    public function index(): Response
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 20);
        if (!in_array($limit, [10, 20, 50, 100])) {
            $limit = 20;
        }
        $offset = ($page - 1) * $limit;

        $search = $request->query->get('search', '');
        $id_lang = (int) \Context::getContext()->language->id;

        // Costruzione della query con filtri
        $where = '';
        $params = ['id_lang' => $id_lang];
        if ($search) {
            $where = 'WHERE pl.name LIKE :search OR p.reference LIKE :search OR p.ean13 LIKE :search OR p.upc LIKE :search OR p.isbn LIKE :search';
            $params['search'] = '%'.$search.'%';
        }

        // Conta totale per paginazione
        $countSql = "SELECT COUNT(*) FROM ps_product p
            LEFT JOIN ps_product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = :id_lang)
            $where";
        $total = $this->connection->executeQuery($countSql, $params)->fetchOne();

        // Recupera parametri di ordinamento
        $allowedSorts = [
            'name' => 'pl.name',
            'reference' => 'p.reference',
            'ean13' => 'p.ean13',
            'upc' => 'p.upc',
            'isbn' => 'p.isbn',
            'quantity' => 'sa.quantity',
        ];
        $sort = $request->query->get('sort', 'name');
        $direction = 'desc' === strtolower($request->query->get('direction', 'asc')) ? 'DESC' : 'ASC';
        $orderBy = isset($allowedSorts[$sort]) ? $allowedSorts[$sort] : 'pl.name';

        // Recupera prodotti con giacenza
        $sql = "SELECT p.id_product, p.reference, p.ean13, p.upc, p.isbn, pl.name, sa.quantity
                FROM ps_product p
                LEFT JOIN ps_product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = :id_lang)
                LEFT JOIN ps_stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0)
                $where
                ORDER BY $orderBy $direction
                LIMIT $limit OFFSET $offset";
        $products = $this->connection->fetchAllAssociative($sql, $params);

        // Recupera combinazioni per i prodotti trovati
        foreach ($products as &$product) {
            $sqlComb = "SELECT pa.id_product_attribute, pa.ean13, pa.default_on, GROUP_CONCAT(agl.name, ': ', al.name SEPARATOR ', ') as attributes, sa.quantity
                        FROM ps_product_attribute pa
                        LEFT JOIN ps_product_attribute_combination pac ON (pa.id_product_attribute = pac.id_product_attribute)
                        LEFT JOIN ps_attribute a ON (pac.id_attribute = a.id_attribute)
                        LEFT JOIN ps_attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = 1)
                        LEFT JOIN ps_attribute_group_lang agl ON (a.id_attribute_group = agl.id_attribute_group AND agl.id_lang = 1)
                        LEFT JOIN ps_stock_available sa ON (pa.id_product_attribute = sa.id_product_attribute)
                        WHERE pa.id_product = :id_product
                        GROUP BY pa.id_product_attribute
                        ORDER BY pa.id_product_attribute ASC";
            $combinations = $this->connection->fetchAllAssociative($sqlComb, ['id_product' => $product['id_product']]);
            $product['combinations'] = $combinations;
        }

        // Recupera il token dalla query string (propagato da PrestaShop)
        $token = $request->query->get('token');
        $sort = $request->query->get('sort', '');
        $direction = $request->query->get('direction', 'asc');

        if ($request->isXmlHttpRequest() || '1' == $request->query->get('ajax')) {
            $totalPages = (int) ceil($total / $limit);
            $pageHtml = $this->renderView('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/partials/stockView/_products_table.html.twig', [
                'products' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ]);
            $paginationHtml = $this->renderView('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/partials/stockView/_products_pagination.html.twig', [
                'products' => count($products),
                'limit' => $limit,
                'page' => $page,
                'totalPages' => $totalPages,
                'search' => $search,
                'total' => $total
            ]);

            return $this->json([
                'page' => $pageHtml,
                'pagination' => $paginationHtml,
            ]);
        }

        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/stock_view.html.twig', [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
            'token' => $token,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function defaultQtyAction(): Response
    {
        // Questa procedura controlla tutte le giacenze di magazzino e imposta
        // default_on della tabella product_attribute alla quantità maggiore

        // 1. imposto il campo default_on a NULL per tutti
        $this->connection->executeStatement('UPDATE ps_product_attribute SET default_on = NULL');

        // 2. per ogni prodotto, trova la combinazione con la quantità massima
        $sql = 'SELECT pa.id_product, pa.id_product_attribute, sa.quantity
                FROM ps_product_attribute pa
                LEFT JOIN ps_stock_available sa ON (pa.id_product_attribute = sa.id_product_attribute)
                ORDER BY pa.id_product, sa.quantity DESC';
        $result = $this->connection->fetchAllAssociative($sql);

        $seen = [];
        foreach ($result as $row) {
            $id_product = $row['id_product'];
            if (!isset($seen[$id_product]) && null !== $row['quantity']) {
                // Imposta default_on = 1 SOLO sulla prima combinazione con quantità massima per ogni prodotto
                $this->connection->executeStatement(
                    'UPDATE ps_product_attribute SET default_on = 1 WHERE id_product_attribute = :id_product_attribute',
                    ['id_product_attribute' => $row['id_product_attribute']]
                );
                $seen[$id_product] = true;
            }
        }
        $this->addFlash('success', 'Quantità di default rigenerate con successo');

        return $this->redirect($this->generateUrl('mpstockadv_admin_stockview'));
    }

    public function setDefaultCombinationAction($id_product_attribute, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $isAjax = $request->isXmlHttpRequest() || 'application/json' === $request->headers->get('Accept');
        $sql = 'SELECT id_product FROM ps_product_attribute WHERE id_product_attribute = :id_product_attribute';
        $id_product = $this->connection->fetchOne($sql, ['id_product_attribute' => $id_product_attribute]);
        if ($id_product) {
            $this->connection->executeStatement('UPDATE ps_product_attribute SET default_on = NULL WHERE id_product = :id_product', ['id_product' => $id_product]);
            $this->connection->executeStatement('UPDATE ps_product_attribute SET default_on = 1 WHERE id_product_attribute = :id_product_attribute', ['id_product_attribute' => $id_product_attribute]);
            if ($isAjax) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => true,
                    'id_product_attribute' => $id_product_attribute,
                    'message' => 'Combinazione aggiornata come quantità di default.',
                ]);
            } else {
                $this->addFlash('success', 'Combinazione aggiornata come quantità di default.');
            }
        } else {
            if ($isAjax) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'id_product_attribute' => $id_product_attribute,
                    'message' => 'Combinazione non trovata.',
                ], 404);
            } else {
                $this->addFlash('danger', 'Combinazione non trovata.');
            }
        }

        return $this->redirect($this->generateUrl('mpstockadv_admin_stockview'));
    }
}
