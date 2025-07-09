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
if (!defined('_PS_VERSION_')) {
    exit;
}

use MpSoft\MpStockAdv\Helpers\TwigManager;
use MpSoft\MpStockAdv\Helpers\UpdateTablesHelper;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class MpStockAdv extends Module
{
    /** @var \Twig\Environment */
    private $twig;

    public function __construct()
    {
        $this->name = 'mpstockadv';
        $this->tab = 'administration';
        $this->version = '0.1.6.25';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('MP Magazzino Avanzato');
        $this->description = $this->l('Gestione avanzata del magazzino, documenti di carico/scarico, multi-magazzino, import/export.');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        // Attiva la gestione avanzata del magazzino
        Configuration::updateValue('PS_ADVANCED_STOCK_MANAGEMENT', 1);

        // Crea la voce di menu AdminStockAdv sotto AdminStock
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $parentTabId = $tabRepository->findOneIdByClassName('AdminStock');

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminStockAdv';
        $tab->module = $this->name;
        $tab->id_parent = $parentTabId;
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'MP Magazzino Avanzato';
        }
        if (!$tab->add()) {
            return false;
        }

        // Aggiornamento tabelle
        $this->updateTables();

        $hooks = [
            'actionAdminControllerSetMedia',
            'actionOrderStatusPostUpdate',
            'actionOrderStatusUpdate',
            'actionOrderAddBefore',
            'actionOrderAddAfter',
            'actionOrderUpdateBefore',
            'actionOrderUpdateAfter',
            'actionOrderDeleteBefore',
            'actionOrderDeleteAfter',
            'actionOrderDetailAddBefore',
            'actionOrderDetailAddAfter',
            'actionOrderDetailUpdateBefore',
            'actionOrderDetailUpdateAfter',
            'actionOrderDetailDeleteBefore',
            'actionOrderDetailDeleteAfter',
            'actionOrderReturn',
            'actionOrderReturnAddBefore',
            'actionOrderReturnAddAfter',
            'actionOrderReturnUpdateBefore',
            'actionOrderReturnUpdateAfter',
            'actionOrderReturnDeleteBefore',
            'actionOrderReturnDeleteAfter',
            'displayAdminOrder',
            'displayAdminOrderCreateExtraButtons',
            'displayAdminOrderMain',
            'displayAdminOrderMainBottom',
            'displayAdminOrderSide',
            'displayAdminOrderSideBottom',
            'displayAdminOrderTabContent',
            'displayAdminOrderTabLink',
            'displayAdminOrderTop',
        ];
        // Registrazione hook
        return $this->registerHook($hooks);
    }

    public function uninstall()
    {
        // Rimuovi la voce di menu AdminStockAdv
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $idTab = $tabRepository->findOneIdByClassName('AdminStockAdv');
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        return parent::uninstall();
    }

    protected function updateTables()
    {
        $updateTablesHelper = new UpdateTablesHelper();
        $updateTablesHelper->updateTableStock();
        $updateTablesHelper->updateTableStockMvt();
    }

    public function getContent()
    {
        $twig = SymfonyContainer::getInstance()->get('twig');
        $updateTablesHelper = new UpdateTablesHelper();
        $struct = [
            'stock' => [
                'name' => 'stock',
                'fields' => $updateTablesHelper->getTableStructure('stock')
            ],
            'stock_mvt' => [
                'name' => 'stock_mvt',
                'fields' => $updateTablesHelper->getTableStructure('stock_mvt'),
            ]
        ];

        //exit(Tools::dieObject($tableStructures));
        return $twig->render('@Modules/mpstockadv/views/twig/Module/GetContent.html.twig', [
            'tables' => $struct
        ]);
    }

    public function getAdminLink($controller)
    {
        return SymfonyContainer::getInstance()->get('router')->generate('mpstockadv_admin_' . $controller);
    }

    // --- HOOKS ---

    public function hookDisplayAdminOrder($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order</div>";
    }

    public function hookDisplayAdminOrderCreateExtraButtons($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Create Extra Buttons</div>";
    }
    public function hookDisplayAdminOrderMain($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Main</div>";
    }
    public function hookDisplayAdminOrderMainBottom($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Main Bottom</div>";
    }
    public function hookDisplayAdminOrderSide($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Side</div>";
    }
    public function hookDisplayAdminOrderSideBottom($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Side Bottom</div>";
    }
    public function hookDisplayAdminOrderTabContent($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Tab Content</div>";
    }
    public function hookDisplayAdminOrderTabLink($params)
    {
        return "<div class='box-danger'>Hook Display Admin Order Tab Link</div>";
    }
    public function hookDisplayAdminOrderTop($params)
    {
        $twig = SymfonyContainer::getInstance()->get('twig');
        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }
        $hasDelivery = $order->delivery_number ?: false;
        $hasInvoice = $order->invoice_number ?: false;

        return $twig->render(
            '@Modules/mpstockadv/views/twig/Module/DisplayAdminOrderTop.html.twig',
            [
                'id_order' => $id_order,
                'has_delivery' => $hasDelivery,
                'has_invoice' => $hasInvoice,
            ]
        );
    }

    /**
     * Caricamento media backend.
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $hideMenu = $this->getLocalPath() . 'views/assets/css/hideMenu.css';
        $this->context->controller->addCSS($hideMenu);
    }

    /**
     * Cambio stato ordine.
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
    }

    public function hookActionOrderStatusUpdate($params)
    {
    }

    // --- Order hooks ---
    public function hookActionOrderAddBefore($params)
    {
    }

    public function hookActionOrderAddAfter($params)
    {
    }

    public function hookActionOrderUpdateBefore($params)
    {
    }

    public function hookActionOrderUpdateAfter($params)
    {
    }

    public function hookActionOrderDeleteBefore($params)
    {
    }

    public function hookActionOrderDeleteAfter($params)
    {
    }

    // --- OrderDetail hooks ---
    public function hookActionOrderDetailAddBefore($params)
    {
    }

    public function hookActionOrderDetailAddAfter($params)
    {
        /** @var OrderDetail */
        $orderDetail = $params['object'];

        $id_order = $orderDetail->id_order;
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }



    }

    public function hookActionOrderDetailUpdateBefore($params)
    {
    }

    public function hookActionOrderDetailUpdateAfter($params)
    {
    }

    public function hookActionOrderDetailDeleteBefore($params)
    {
    }

    public function hookActionOrderDetailDeleteAfter($params)
    {
    }

    // --- OrderReturn hooks ---
    public function hookActionOrderReturn($params)
    {
    }

    public function hookActionOrderReturnAddBefore($params)
    {
    }

    public function hookActionOrderReturnAddAfter($params)
    {
    }

    public function hookActionOrderReturnUpdateBefore($params)
    {
    }

    public function hookActionOrderReturnUpdateAfter($params)
    {
    }

    public function hookActionOrderReturnDeleteBefore($params)
    {
    }

    public function hookActionOrderReturnDeleteAfter($params)
    {
    }
}
