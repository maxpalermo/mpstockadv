<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class SupplierAjaxController extends FrameworkBundleAdminController
{
    /**
     * @Route("/mpstockadv/supply-orders/supplier-search", name="mpstockadv_supply_orders_supplier_search", methods={"GET"})
     */
    public function supplierSearch(Request $request)
    {
        $q = $request->query->get('q', '');
        $all = \MpSoft\MpStockAdv\Models\ModelSupplier::getAll();
        $results = [];
        foreach ($all as $row) {
            if ($q && stripos($row['name'], $q) === false) {
                continue;
            }
            // Costruisci URL logo
            $logoPath = _PS_BASE_URL_._PS_BASE_URI_.'img/supliers/'.basename($row['logo']);
            // Se il file non esiste, usa default
            if (!file_exists(_PS_ROOT_DIR_.'/img/supliers/'.basename($row['logo']))) {
                $logoPath = _PS_BASE_URL_._PS_BASE_URI_.'img/supliers/default.jpg';
            }
            $results[] = [
                'id' => $row['id_supplier'],
                'text' => $row['name'],
                'logo' => $logoPath,
            ];
        }
        return new JsonResponse(['results' => $results]);
    }
}
