<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpStockAdv\Controllers;

use MpSoft\MpStockAdv\Helpers\ConfigurationHelper;
use MpSoft\MpStockAdv\Services\MenuDataService;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StockMvtPreferences extends FrameworkBundleAdminController
{
    private LegacyContext $context;
    private MenuDataService $menuDataService;
    private ConfigurationHelper $configurationHelper;

    public function indexAction(Request $request, LegacyContext $context, MenuDataService $menuDataService): Response
    {
        $this->configurationHelper = new ConfigurationHelper();
        $this->menuDataService = $menuDataService;
        $this->context = $context;
        $this->menuDataService->setTitle('Movimenti di magazzino');
        $this->menuDataService->setIcon('inventory');
        $this->menuDataService->injectMenuData(
            [
                [
                    'icon' => 'settings',
                    'label' => 'Impostazioni',
                    'children' => [
                        [
                            'icon' => 'tune',
                            'label' => 'Preferenze',
                            'href' => 'javascript:loadModalPreferences();',
                        ],
                        [
                            'icon' => 'lock_open',
                            'label' => 'Permessi',
                            'href' => 'javascript:loadModalPermissions();',
                        ],
                    ],
                ],
            ]
        );

        return $this->render(
            '@Modules/mpstockadv/views/twig/Controllers/StockMvtPreferences.index.html.twig',
            [
                'toolbarMenuHtml' => $this->menuDataService->renderMenu(),
                'warehouses' => $this->configurationHelper->getWarehouses(),
                'default_warehouse' => $this->configurationHelper->getDefaultWarehouse(),
                'stock_mvt_reasons' => $this->configurationHelper->getStockMvtReasons(),
                'default_stock_mvt_reason_load' => $this->configurationHelper->getDefaultStockMvtReasonLoad(),
                'default_stock_mvt_reason_unload' => $this->configurationHelper->getDefaultStockMvtReasonUnload(),
            ]
        );
    }

    /**
     * Carica la modale delle preferenze (dependency injection via argomenti).
     */
    public function loadModalPreferencesAction(LegacyContext $context, MenuDataService $menuDataService)
    {
        $this->configurationHelper = new ConfigurationHelper();
        $this->context = $context;
        $this->menuDataService = $menuDataService;

        $html = $this->renderView(
            '@Modules/mpstockadv/views/twig/Components/StockMvt.dialog.preferences.html.twig',
            [
                'toolbarMenuHtml' => $this->menuDataService->renderMenu(),
                'warehouses' => $this->configurationHelper->getWarehouses(),
                'stockMvtReasons' => $this->configurationHelper->getStockMvtReasons(),
                'default_warehouse' => $this->configurationHelper->getDefaultWarehouse(),
                'default_stock_mvt_reason_load' => $this->configurationHelper->getDefaultStockMvtReasonLoad(),
                'default_stock_mvt_reason_unload' => $this->configurationHelper->getDefaultStockMvtReasonUnload(),
            ]
        );

        return $this->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function savePreferencesAction(Request $request)
    {
        $this->configurationHelper = new ConfigurationHelper();

        $default_warehouse = (int) $request->get('default_warehouse', 0);
        $default_stock_mvt_reason_load = (int) $request->get('default_stock_mvt_reason_load', 0);
        $default_stock_mvt_reason_unload = (int) $request->get('default_stock_mvt_reason_unload', 0);

        $this->configurationHelper->set('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON_LOAD', $default_stock_mvt_reason_load);
        $this->configurationHelper->set('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON_UNLOAD', $default_stock_mvt_reason_unload);
        $this->configurationHelper->set('MPSTOCKADV_DEFAULT_WAREHOUSE', $default_warehouse);

        return $this->json([
            'success' => true,
        ]);
    }

    public function loadStockMvtPreferencesAction()
    {
        $this->configurationHelper = new ConfigurationHelper();

        return $this->json([
            'success' => true,
            'preferences' => $this->loadStockMvtPreferences(),
        ]);
    }

    public function loadStockMvtPreferences()
    {
        $preferences = [
            'id_warehouse' => $this->configurationHelper->getDefaultWarehouse(),
            'id_stock_mvt_reason_load' => $this->configurationHelper->getDefaultStockMvtReasonLoad(),
            'id_stock_mvt_reason_unload' => $this->configurationHelper->getDefaultStockMvtReasonUnload(),
            'warehouses' => $this->configurationHelper->getWarehouses(),
            'stock_mvt_reasons' => $this->configurationHelper->getStockMvtReasons(),
        ];

        return $preferences;
    }
}
