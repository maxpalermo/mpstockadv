<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
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
namespace MpSoft\MpStockAdv\Helpers;

use MpSoft\MpStockAdv\Helpers\DependencyHelper;

class UpdateTablesHelper extends DependencyHelper
{
    public function __construct()
    {
        parent::__construct();
    }

    public function updateTableStock()
    {
        $table = _DB_PREFIX_ . 'stock';
        $sql = "
            ALTER TABLE
                `{$table}`
            MODIFY COLUMN
                `physical_quantity` INT SIGNED NOT NULL, 
            MODIFY COLUMN
                `usable_quantity` INT SIGNED NOT NULL";

        $conn = $this->connection;
        return $conn->executeStatement($sql);
    }

    public function updateTableStockMvt()
    {
        $conn = $this->connection;
        $table = _DB_PREFIX_ . 'stock_mvt';
        $sql_modify = "
            ALTER TABLE
                `{$table}`
            MODIFY COLUMN
                `physical_quantity` INT SIGNED NOT NULL
        ";
        $modify = $conn->executeStatement($sql_modify);

        try {
            $sql_add = "
                ALTER TABLE
                    `{$table}`
                ADD COLUMN
                    `stock_before` INT SIGNED NOT NULL
                AFTER `employee_firstname`,
                ADD COLUMN
                    `stock_after` INT SIGNED NOT NULL
                AFTER `physical_quantity`
            ";
            $modify &= $conn->executeStatement($sql_add);
        } catch (\Throwable $th) {
            \PrestaShopLogger::addLog($th->getMessage(), 1, $th->getCode(), "Connection");
        }

        return $modify;
    }

    public function getTableStructure($tableName)
    {
        $fields = [];
        if (!preg_match('/^' . _DB_PREFIX_ . '/', $tableName)) {
            $tableName = _DB_PREFIX_ . $tableName;
        }

        $conn = $this->connection; // Presuppone che la property $connection sia settata (Doctrine\DBAL\Connection)

        $sql = "SHOW FULL COLUMNS FROM `$tableName`";
        $columns = $conn->fetchAllAssociative($sql);

        foreach ($columns as $col) {
            // Esempio Type: int(11) unsigned, varchar(64), decimal(20,6)
            $typeStr = $col['Type'];
            $null = (strtoupper($col['Null']) === 'YES');
            $unsigned = false;

            if (preg_match('/^(\w+)(\(([\d,]+)\))?( unsigned)?/i', $typeStr, $matches)) {
                $unsigned = false;
                $type = $matches[1];
                $size = isset($matches[3]) ? $matches[3] : null;
                if (!empty($matches[4]) && strpos($matches[4], 'unsigned') !== false) {
                    $unsigned = true;
                }
                $col['Unsigned'] = $unsigned;
                $col['Type'] = $type;
                $col['Size'] = $size;
            } else {
                $col['Unsigned'] = $unsigned;
                $col['Type'] = $typeStr;
                $col['Size'] = null;
            }

            $fields[$col['Field']] = $col;
        }

        return $fields;

    }
}