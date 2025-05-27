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

namespace MpSoft\MpStockAdv\Models;

use MpSoft\MpStockAdv\Helpers\ParseCsv;

class ModelStockMvtReason extends \StockMvtReason
{
    public $alias;

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        self::$definition['fields']['alias'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => true,
        ];

        parent::__construct($id, $id_lang, $id_shop, $translator);
    }

    public static function getByAliasId(int $aliasId, int $idLang = 0)
    {
        $db = \Db::getInstance();
        $table = _DB_PREFIX_.self::$definition['table'];
        $primary = (string) self::$definition['primary'];
        $result = $db->getValue(
            "SELECT {$primary} FROM   `{$table}` WHERE alias = ".(int) $aliasId
        );
        if ($result) {
            if ($idLang) {
                return new self((int) $result, $idLang);
            }

            return new self((int) $result);
        }

        return false;
    }

    public static function importFromCsv(string $csv, $type = 'reasons')
    {
        $list = [];

        switch ($type) {
            case 'reasons':
                $parseCsv = new ParseCsv($csv);
                $list = $parseCsv->parse();

                break;
            case 'lang':
                $parseCsv = new ParseCsv($csv);
                $list = $parseCsv->parse();

                break;
            default:
                return [
                    'success' => false,
                    'message' => 'Tipo non valido',
                    'type' => $type,
                ];
        }

        if ($list) {
            $languages = \Language::getLanguages();
            if ('lang' === $type) {
                foreach ($list as $item) {
                    $alias = $item['id_mpstock_mvt_reason'];
                    $id_lang = $item['id_lang'];

                    $table = (string) self::$definition['table'];
                    $primary = (string) self::$definition['primary'];
                    $tableLang = $table.'_lang';
                    $name = $item['name'] ?? '--';

                    $db = \Db::getInstance();
                    $sql = new \DbQuery();
                    $sql->select($primary)
                        ->from($table)
                        ->where('alias = '.(int) $alias);
                    $id = $db->getValue($sql);
                    if ($id) {
                        $db->update(
                            $tableLang,
                            [
                                'name' => $name,
                            ],
                            'id_lang = '.(int) $id_lang.' AND '.$primary.' = '.(int) $id
                        );
                    }
                }
            } elseif ('reasons' === $type) {
                foreach ($list as $item) {
                    $alias = $item['id_mpstock_mvt_reason'];
                    $model = self::getByAliasId($alias);

                    if (!\Validate::isLoadedObject($model)) {
                        $model = new self();
                    }

                    $model->alias = $alias;
                    $model->sign = 0 == $item['sign'] ? 1 : -1;
                    $model->deleted = $item['deleted'];
                    $model->date_add = $item['date_add'];
                    $model->date_upd = $item['date_upd'];

                    foreach ($languages as $language) {
                        $model->name[$language['id_lang']] = '--';
                    }

                    $model->add();
                }
            }
        }

        return [
            'success' => true,
            'list' => $list,
            'type' => $type,
        ];
    }

    public static function ImportFromV16(string $jsonData)
    {
        try {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
                'type' => 'v16',
            ];
        }

        $languages = \Language::getLanguages();
        $module = \Module::getInstanceByName('mpstockadv');

        if ($data && is_array($data)) {
            $alias = $data['alias'] ?? '';
            $model = self::getByAliasId($alias);
            if (!\Validate::isLoadedObject($model)) {
                $model = new self();
            }
            foreach ($data as $item) {
                $model->id = $item['id_stock_mvt_reason'];
                $model->sign = 0 == $item['sign'] ? 1 : -1;
                $model->date_add = $item['date_add'];
                $model->date_upd = $item['date_upd'];
                $model->deleted = $item['deleted'];
                $model->alias = $item['alias'];
                foreach ($languages as $language) {
                    $model->name[$language['id_lang']] = $item['name'][$language['id_lang']];
                }
                $model->add();
            }

            return [
                'success' => true,
                'message' => $module->l('Tipi di movimento importati con successo', 'ModelStockMvtReason'),
                'type' => 'v16',
            ];
        }

        return [
            'success' => false,
            'message' => $module->l('Nessun dato trovato', 'ModelStockMvtReason'),
            'type' => 'v16',
        ];
    }

    /**
     * Restituisce risultati paginati e filtrati per la tabella AJAX.
     */
    public static function getPaginated($page = 1, $limit = 10, $search = '')
    {
        $offset = ($page - 1) * $limit;
        $db = \Db::getInstance();
        $table = _DB_PREFIX_.self::$definition['table'];
        $primary = (string) self::$definition['primary'];
        $langTable = $table.'_lang';
        $id_lang = (int) \Context::getContext()->language->id;

        $where = '';
        if ($search) {
            $searchSql = pSQL($search);
            $where = "WHERE t.alias LIKE '%$searchSql%' OR l.name LIKE '%$searchSql%'";
        }

        $sql = "SELECT t.$primary as id_mpstock_mvt_reason, t.alias, t.sign, t.deleted, l.name
                FROM $table t
                LEFT JOIN $langTable l ON l.$primary = t.$primary AND l.id_lang = $id_lang
                $where
                ORDER BY l.name ASC
                LIMIT $limit OFFSET $offset";
        $data = $db->executeS($sql);

        $sqlCount = "SELECT COUNT(*) FROM $table t
            LEFT JOIN $langTable l ON l.$primary = t.$primary AND l.id_lang = $id_lang
            $where";
        $total = (int) $db->getValue($sqlCount);

        return [
            'data' => $data,
            'total' => $total,
        ];
    }
}
