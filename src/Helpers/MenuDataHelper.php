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
 * @author Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpStockAdv\Helpers;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\Routing\RouterInterface;

class MenuDataHelper extends DependencyHelper
{
    private $menuData;
    private $title;
    private $icon;

    public function __construct()
    {
        parent::__construct();
        $this->title = 'Movimenti';
        $this->icon = 'apps';
    }

    public function getDefaultMenuData()
    {
        $this->menuData = [
            [
                'icon' => 'home',
                'label' => 'Home',
                'href' => $this->generateUrl('mpstockadv_admin_stockadv'),
            ],
            [
                'icon' => 'list',
                'label' => 'Menu',
                'children' => [
                    [
                        'label' => 'Magazzini',
                        'href' => $this->generateUrl('mpstockadv_admin_warehouse'),
                        'icon' => 'store',
                    ],
                    [
                        'label' => 'Tipi di movimento',
                        'href' => $this->generateUrl('mpstockadv_admin_mvtreasons_index'),
                        'icon' => 'category',
                    ],
                    [
                        'label' => 'Documenti di carico',
                        'href' => $this->generateUrl('mpstockadv_admin_supplyorders'),
                        'icon' => 'file_upload',
                    ],
                    [
                        'label' => 'Documenti di scarico',
                        'href' => $this->generateUrl('mpstockadv_admin_stockexit'),
                        'icon' => 'file_download',
                    ],
                    [
                        'label' => 'Movimenti',
                        'href' => $this->generateUrl('mpstockadv_controller_stockmvt_index'),
                        'icon' => 'swap_horiz',
                    ],
                    [
                        'label' => 'Giacenze',
                        'href' => $this->generateUrl('mpstockadv_admin_stockview'),
                        'icon' => 'visibility',
                    ],
                    [
                        'label' => 'Import/Export',
                        'href' => $this->generateUrl('mpstockadv_admin_stockimportexport'),
                        'icon' => 'import_export',
                    ],
                    [
                        'label' => 'Impostazioni',
                        'href' => $this->generateUrl('mpstockadv_admin_stocksettings'),
                        'icon' => 'settings',
                    ],
                ],
            ],
        ];

        return $this->menuData;
    }

    public function initMenu()
    {
        $this->getDefaultMenuData();
    }

    public function injectMenuData(array $values)
    {
        $this->getDefaultMenuData();
        foreach ($values as $menu) {
            $this->menuData[] = $menu;
        }
    }

    public function getMenuData()
    {
        return $this->menuData;
    }

    public function generateUrl(string $route, array $parameters = [], int $referenceType = \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    public function renderMenu()
    {
        return $this->twig->render('@Modules/mpstockadv/views/twig/Components/menu.index.html.twig', [
            'title' => $this->title,
            'icon' => $this->icon,
            'menuData' => $this->menuData,
        ]);
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setIcon(string $icon)
    {
        $this->icon = $icon;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
