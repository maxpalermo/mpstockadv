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
class OrderToJson extends DependencyHelper
{
    protected $document;
    protected $order;
    protected $delivery;
    protected $invoice;

    public function __construct()
    {
        parent::__construct();
    }

    public function getOrder($id_order)
    {
        $this->order = new \Order($id_order);
        $this->document = $this->order->getFields();
    }

    public function getDelivery($id_order)
    {
        $this->getOrder($id_order);
        $this->document['delivery']['number'] = $this->order->delivery_number;
        $this->document['delivery']['date'] = $this->order->delivery_date;
    }

    public function getInvoice($id_order)
    {
        $this->getOrder($id_order);
        $id_invoice = $this->order->invoice_number;
        if (!$id_invoice) {
            $this->document['invoice'] = [
                'error' => 'Nessuna fattura associata all\'ordine'
            ];
            return;
        }
        $this->invoice = new \OrderInvoice($id_invoice);
        $this->document['invoice'] = $this->invoice->getFields();
    }

    public function createDocument($id_order, $type)
    {
        switch ($type) {
            case 'order':
                $this->getOrder($id_order);
                break;
            case 'delivery':
                $this->getDelivery($id_order);
                break;
            case 'invoice':
                $this->getInvoice($id_order);
                break;
            default:
                $this->document = [
                    'error' => 'Tipo di documento non valido'
                ];
                return false;
        }
    }

    public function getDocument()
    {
        return $this->document;
    }
}