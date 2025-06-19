<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use MpSoft\MpStockAdv\Models\ModelStockMvtReason;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockMvtReasonsController extends FrameworkBundleAdminController
{
    /**
     * @Route("/admin/mpstockadv/mvt-reasons", name="mpstockadv_admin_mvtreasons_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/mvt_reasons.html.twig');
    }

    /**
     * @Route("/admin/mpstockadv/mvt-reasons/list", name="mpstockadv_admin_mvtreasons_list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->request->get('page', $request->query->get('page', 1));
        $limit = (int) $request->request->get('limit', $request->query->get('limit', 10));
        $search = $request->request->get('search', $request->query->get('search', ''));

        $result = ModelStockMvtReason::getPaginated($page, $limit, $search);

        return new JsonResponse([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * @Route("/admin/mpstockadv/mvt-reasons/save", name="mpstockadv_admin_mvtreasons_save", methods={"POST"})
     */
    public function save(Request $request): JsonResponse
    {
        $id = $request->request->get('id');
        $alias = $request->request->get('alias');
        $sign = $request->request->get('sign');
        $deleted = $request->request->get('deleted', 0);
        $name = $request->request->get('name');

        if ($id) {
            $reason = new ModelStockMvtReason($id);
        } else {
            $reason = new ModelStockMvtReason();
        }
        $reason->alias = $alias;
        $reason->sign = $sign;
        $reason->deleted = $deleted;
        $reason->name = $name;
        $reason->save();

        return new JsonResponse(['success' => true, 'message' => 'Tipo di movimento salvato']);
    }

    /**
     * @Route("/admin/mpstockadv/mvt-reasons/delete", name="mpstockadv_admin_mvtreasons_delete", methods={"POST"})
     */
    public function delete(Request $request): JsonResponse
    {
        $id = $request->request->get('id');
        if ($id) {
            $reason = new ModelStockMvtReason($id);
            $reason->delete();

            return new JsonResponse(['success' => true, 'message' => 'Tipo di movimento eliminato']);
        }

        return new JsonResponse(['success' => false, 'message' => 'ID non trovato']);
    }
}
