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

use Symfony\Component\HttpFoundation\JsonResponse;

class GetDocuments extends DependencyHelper
{
    private $page = 1;
    private $perPage = 50;
    private $totalRows = 0;
    private $chunk = [];
    private $idOrderStates;
    private $typeDocument;
    private $module;
    private $viewsPath;

    public function __construct($page, $perPage, $idOrderStates, $typeDocument)
    {
        parent::__construct();
        $this->module = \Module::getInstanceByName('mpstockadv');
        $this->page = $page;
        $this->perPage = $perPage;
        $this->idOrderStates = $idOrderStates;
        $this->typeDocument = $typeDocument;
        $this->viewsPath = $this->module->getLocalPath() . 'views/';
    }

    public function run()
    {
        $start = ($this->page - 1) * $this->perPage;
        $pfx = _DB_PREFIX_;
        $id_lang = $this->id_lang;
        $orderStateList = implode(",", $this->idOrderStates);
        $typeDocument = (int) $this->typeDocument;

        $typeOrder = \Configuration::get("MPSTOCKADV_TYPE_ORDER");
        $typeInvoice = \Configuration::get("MPSTOCKADV_TYPE_INVOICE");
        $typeDelivery = \Configuration::get("MPSTOCKADV_TYPE_DELIVERY");
        $typeReturn = \Configuration::get("MPSTOCKADV_TYPE_RETURN");
        $typeCreditSlip = \Configuration::get("MPSTOCKADV_TYPE_CREDIT_SLIP");

        switch ($typeDocument) {
            case $typeOrder:
                break;
            case $typeInvoice:
                break;
            case $typeDelivery:
                break;
            case $typeReturn:
                break;
            case $typeCreditSlip:
                break;
        }

        $query = "
            SELECT
                o.id_order,
                o.reference,
                o.date_add,
                osl.name as current_state,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                o.total_paid_tax_incl as total_order
            FROM
                {$pfx}orders o
            INNER JOIN
                {$pfx}order_state_lang osl
                ON (o.current_state=osl.id_order_state AND osl.id_lang={$id_lang})
            INNER JOIN
                {$pfx}customer c
                ON (o.id_customer=c.id_customer)    
            WHERE
                o.current_state IN ({$orderStateList})
            ORDER BY o.id_order DESC
        ";

        $statement = $this->connection->executeQuery($query);
        $result = $statement->fetchAllAssociative();
        $this->chunk = [];

        if ($result) {
            $this->totalRows = count($result);
            $this->chunk = array_splice($result, $start, $this->perPage);
        }

        return [
            'rows' => $this->chunk,
            'pagination' => $this->createPagination(),
        ];
    }

    public function createPagination()
    {
        $currentPage = (int) $this->page;
        $perPage = (int) $this->perPage;
        $totalRows = (int) $this->totalRows;

        $pagination = new PaginationHelper($currentPage, $perPage, $totalRows);
        $paginator = [
            'currentPage' => $pagination->getCurrentPage(),
            'totalPages' => $pagination->getTotalPages(),
            'startPage' => max(1, $currentPage - 2),
            'endPage' => min($pagination->getTotalPages(), $currentPage + 2),
            'hasPrevious' => $currentPage > 1,
            'hasNext' => $currentPage < $pagination->getTotalPages(),
            'perPages' => $perPage,
        ];
        // Invia i dati alla view Twig
        return $paginator;
    }
}