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

class PaginationHelper
{
    private $page;
    private $perPage;
    private $totalRows;

    public function __construct(int $page = 1, int $perPage = 10, int $totalRows = 0)
    {
        $this->page = max(1, $page);
        $this->perPage = max(1, $perPage);
        $this->totalRows = max(0, $totalRows);
    }

    public function createPagination(): array
    {
        $totalPages = $this->getTotalPages();
        $currentPage = $this->page;
        $visiblePages = 5; // Numero di pagine visibili nella navigazione

        // Calcola range di pagine da mostrare
        $startPage = max(1, $currentPage - floor($visiblePages / 2));
        $endPage = min($totalPages, $startPage + $visiblePages - 1);

        // Aggiusta il range se siamo vicini all'inizio/fine
        if ($endPage - $startPage + 1 < $visiblePages) {
            $startPage = max(1, $endPage - $visiblePages + 1);
        }

        $paginationData = [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'startPage' => $startPage,
            'endPage' => $endPage,
            'hasPrevious' => $currentPage > 1,
            'hasNext' => $currentPage < $totalPages,
        ];

        return $this->getPaginationArray($paginationData);
    }

    public function getTotalPages(): int
    {
        return ceil($this->totalRows / $this->perPage);
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function getCurrentPage()
    {
        return (int) $this->page;
    }

    protected function getPaginationArray(array $data): array
    {
        $pages = 0;
        $first = 1;
        $last = $data['totalPages'];
        $current = $data['currentPage'];
        $perPages = $data['perPages'];

        $result = [
            'totalPages' => $last,
            'perPages' => $perPages,
            'first' => $first,
            'last' => $last,
            'pages' => [],
            'current' => $current,
        ];

        $gap = ($current + 5) < $last;
        $pages = [];
        if ($gap) {
            for ($i = $current; $i < 5; $i++) {
                $pages[] = $i;
            }
        } else {
            for ($i = $current; $i < ($last - $current); $i++) {
                $pages[] = $i;
            }
        }
        $result['pages'] = $pages;

        return $result;
    }
}