<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use MpSoft\MpStockAdv\Services\ProductAutocompleteService;
use MpSoft\MpStockAdv\Services\StockMvtReasonService;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller principale per menu MP Magazzino Avanzato.
 */
class AdminStockSettingsController extends FrameworkBundleAdminController
{
    private LegacyContext $context;
    private int $id_lang;

    public function __construct(LegacyContext $context)
    {
        /* @var LegacyContext $context */
        $this->context = $context;
        $this->id_lang = (int) $this->context->getContext()->language->id;
    }

    /**
     * @Route("/mpstockadv", name="mpstockadv_admin_stockadv")
     */
    public function index(): Response
    {
        $id_lang = (int) $this->context->getContext()->language->id;
        $defaultWarehouse = (int) \Configuration::get('MPSTOCKADV_DEFAULT_WAREHOUSE');

        // Controllo se è stato premuto il submit del form
        $request = Request::createFromGlobals();
        if ($request->isMethod('POST')) {
            $defaultWarehouse = $request->request->get('default_warehouse');
            $defaultStockMvt = $request->request->get('default_stock_mvt');
            \Configuration::updateValue('MPSTOCKADV_DEFAULT_WAREHOUSE', $defaultWarehouse);
            \Configuration::updateValue('MPSTOCKADV_DEFAULT_STOCK_MVT', $defaultStockMvt);
            $this->addFlash('success', 'Impostazioni salvate con successo');
        }

        // Mostra la dashboard/menu del modulo
        return $this->render('@Modules/mpstockadv/views/templates/admin/settings/settings.html.twig', [
            'menu_links' => [
                [
                    'label' => 'Impostazioni',
                    'url' => $this->generateUrl('mpstockadv_admin_stocksettings'),
                ],
            ],
            'warehouses' => \Warehouse::getWarehouses(false, $this->context->getContext()->shop->id),
            'stockMvtReasons' => \StockMvtReason::getStockMvtReasons($id_lang),
            'current_default_warehouse' => (int) \Configuration::get('MPSTOCKADV_DEFAULT_WAREHOUSE'),
            'current_default_stock_mvt' => (int) \Configuration::get('MPSTOCKADV_DEFAULT_STOCK_MVT'),
        ]);
    }

    public function productSearchAction(Request $request): Response
    {
        $idLang = $this->id_lang;
        $q = $request->query->get('q', '');
        $limit = $request->query->get('limit', 20);

        // Usa il servizio centralizzato per la ricerca autocomplete
        $service = new ProductAutocompleteService(
            $this->getDoctrine()->getConnection()
        );
        $items = $service->search($q, $idLang, $limit);

        return $this->json(['results' => $items]);
    }

    public function getMvtReasonsAction()
    {
        $service = new StockMvtReasonService(
            $this->getDoctrine()->getConnection()
        );
        $items = $service->getMvtReasons();

        return $this->json(['result' => $items]);
    }
}
