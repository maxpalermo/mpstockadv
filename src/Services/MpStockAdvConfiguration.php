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

namespace MpSoft\MpStockAdv\Services;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class MpStockAdvConfiguration
{
    private $router;
    private $context;
    private $id_lang;
    private $id_shop;
    private $id_currency;
    private $connection;

    public function __construct(RouterInterface $router, LegacyContext $context, \Doctrine\DBAL\Connection $connection)
    {
        $this->router = $router;
        $this->context = $context;
        $this->id_lang = (int) $context->getContext()->language->id;
        $this->id_shop = (int) $context->getContext()->shop->id;
        $this->id_currency = (int) $context->getContext()->currency->id;
        $this->connection = $connection;
    }

    public function isJson($value): bool
    {
        try {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function get($value)
    {
        $value = \Configuration::get($value);
        if ($this->isJson($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    public function set($key, $value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        \Configuration::updateValue($key, $value);
    }

    public function delete($key)
    {
        if (is_int($key)) {
            return \Configuration::deleteById($key);
        }

        return \Configuration::deleteByName($key);
    }

    public function getDefaultWarehouse()
    {
        return $this->get('MPSTOCKADV_DEFAULT_WAREHOUSE');
    }

    public function getWarehouses()
    {
        $warehouses = \Warehouse::getWarehouses();
        $ids = array_column($warehouses, 'id_warehouse');

        $ids_list = implode(',', $ids);
        $prefix = _DB_PREFIX_;
        $conn = $this->connection;
        $sql = "
            SELECT *
                FROM {$prefix}address
                WHERE id_warehouse IN ({$ids_list})
                ORDER BY id_warehouse;
            ";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $addresses = $result->fetchAllAssociative();

        if ($addresses) {
            foreach ($warehouses as &$warehouse) {
                foreach ($addresses as $address) {
                    if ($address['id_warehouse'] == $warehouse['id_warehouse']) {
                        $warehouse = array_merge($warehouse, $address);
                    }
                }
            }
        }

        return $warehouses;
    }

    public function getDefaultStockMvtReasonLoad()
    {
        return $this->get('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON_LOAD');
    }

    public function getDefaultStockMvtReasonUnload()
    {
        return $this->get('MPSTOCKADV_DEFAULT_STOCK_MVT_REASON_UNLOAD');
    }

    public function getStockMvtReasons()
    {
        return \StockMvtReason::getStockMvtReasons($this->id_lang);
    }

    public function getIdLang()
    {
        return $this->id_lang;
    }

    public function getIdShop()
    {
        return $this->id_shop;
    }

    public function getIdCurrency()
    {
        return $this->id_currency;
    }

    public function getStatesAction(Request $request)
    {
        $prefix = _DB_PREFIX_;
        $countryId = $request->query->get('countryId');
        $conn = $this->connection;
        $stmt = $conn->prepare("SELECT * FROM {$prefix}state WHERE id_country = :countryId ORDER BY name ASC");
        $result = $stmt->executeQuery(['countryId' => $countryId]);
        if ($result) {
            $items = $result->fetchAllAssociative();
        }
        $response = (new JsonResponse(['states' => $items]))->setStatusCode(200);

        return $response;
    }

    public function getCurrentEmployee()
    {
        $employee = $this->context->getContext()->employee;

        return [
            'id_employee' => $employee->id,
            'firstname' => $employee->firstname,
            'lastname' => $employee->lastname,
            'email' => $employee->email,
        ];
    }
}
