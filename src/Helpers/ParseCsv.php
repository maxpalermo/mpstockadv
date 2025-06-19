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

namespace MpSoft\MpStockAdv\Helpers;

class ParseCsv
{
    protected $csv;
    protected $delimitator;
    protected $enclosure;
    protected $errors = [];

    public function __construct($csv, $delimitator = ';', $enclosure = '"')
    {
        $this->csv = $csv;
        $this->delimitator = $delimitator;
        $this->enclosure = $enclosure;
    }

    public function parse()
    {
        $csv = $this->csv;
        $csvArray = explode("\n", $csv);
        $header = array_shift($csvArray);
        $headerCols = $this->parseHeaderCols($header);
        $list = [];
        foreach ($csvArray as $item) {
            $item = $this->parseCol($item, $headerCols);
            if (isset($item['error'])) {
                $this->errors[] = $item['error'];
                continue;
            }
            $list[] = $item;
        }

        return $list;
    }

    protected function parseHeaderCols($header)
    {
        $arrayCols = explode($this->delimitator, $header);

        $arrayCols = array_map(fn ($col) => trim($col), $arrayCols);
        $arrayCols = array_map(fn ($col) => preg_replace('/^'.$this->enclosure.'|'.$this->enclosure.'$/', '', $col), $arrayCols);
        $arrayCols = array_map(fn ($col) => trim($col), $arrayCols);

        return $arrayCols;
    }

    protected function parseCol($col, $headerCols)
    {
        $arrayCols = explode($this->delimitator, $col);
        $arrayCols = array_map(fn ($col) => preg_replace('/^'.$this->enclosure.'|'.$this->enclosure.'$/', '', $col), $arrayCols);
        $arrayCols = array_map(fn ($col) => trim($col), $arrayCols);

        if (count($headerCols) !== count($arrayCols)) {
            return [
                'error' => "Numero colonne non corrisponde all'header: header=".count($headerCols).', valori='.count($arrayCols),
            ];
        }

        try {
            return array_combine($headerCols, $arrayCols);
        } catch (\Throwable $th) {
            return [
                'error' => $th->getMessage(),
            ];
        }
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
