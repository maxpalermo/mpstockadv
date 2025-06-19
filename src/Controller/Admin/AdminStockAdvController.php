<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use MpSoft\MpStockAdv\Services\MenuDataService;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller principale per menu MP Magazzino Avanzato.
 */
class AdminStockAdvController extends FrameworkBundleAdminController
{
    private LegacyContext $context;
    private int $id_lang;
    private MenuDataService $menuDataService;

    public function __construct(LegacyContext $context, MenuDataService $menuDataService)
    {
        /* @var LegacyContext $context */
        $this->context = $context;
        $this->id_lang = (int) $this->context->getContext()->language->id;
        $this->menuDataService = $menuDataService;
    }

    /**
     * @Route("/mpstockadv", name="mpstockadv_admin_stockadv")
     */
    public function index(): Response
    {
        // Mostra la dashboard/menu del modulo
        $menuData = $this->menuDataService->getMenuData();

        return $this->render('@Modules/mpstockadv/views/twig/Controllers/AdminStockAdvController.index.html.twig', [
            'menu_data' => $menuData,
        ]);
    }
}
