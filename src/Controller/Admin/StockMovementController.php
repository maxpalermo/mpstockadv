<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockMovementController extends FrameworkBundleAdminController
{
    /**
     * @Route("/admin/mpstockadv/stock-movement", name="mpstockadv_admin_stockmovement", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/stock_movement.html.twig');
    }
}
