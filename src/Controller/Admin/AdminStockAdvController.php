<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller principale per menu MP Magazzino Avanzato.
 */
class AdminStockAdvController extends FrameworkBundleAdminController
{
    /**
     * @Route("/mpstockadv", name="mpstockadv_admin_stockadv")
     */
    public function index(): Response
    {
        // Mostra la dashboard/menu del modulo
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/stockadv_menu.html.twig', [
    'menu_links' => [
        [
            'label' => 'Documenti di Carico',
            'url' => $this->generateUrl('mpstockadv_admin_supplyorders')
        ]
    ]
]);
    }
}
