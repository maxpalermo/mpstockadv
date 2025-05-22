<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockExitController extends FrameworkBundleAdminController
{
    /**
     * @Route("/admin/mpstockadv/stock-exit", name="mpstockadv_admin_stockexit", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/stock_exit.html.twig');
    }
}
