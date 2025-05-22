<?php

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

/*
 * Bridge legacy per la voce di menu AdminStockAdv: reindirizza alla dashboard Symfony.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminStockAdvController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        // Reindirizza alla dashboard Symfony
        Tools::redirectAdmin($this->getAdminLink('stockadv'));
    }

    public function getAdminLink($controller)
    {
        return SymfonyContainer::getInstance()->get('router')->generate('mpstockadv_admin_'.$controller);
    }
}
