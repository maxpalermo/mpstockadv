<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockEntryController extends FrameworkBundleAdminController
{
    /**
     * @Route("/admin/mpstockadv/stock-entry", name="mpstockadv_admin_stockentry", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/stock_entry.html.twig');
    }
}
