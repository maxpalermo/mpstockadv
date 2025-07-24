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

class OrderToXmlHelper extends DependencyHelper
{
    /**
     * Genera un file XML da una struttura JSON seguendo lo schema fornito
     * 
     * @param array $data Array contenente i dati della fattura
     * @param string $outputFile Percorso del file XML da generare
     * 
     * @return string XML Content
     */
    function generateInvoiceXml(array $data, string $outputFile = null): string
    {
        // Crea un nuovo documento XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Crea l'elemento radice <invoices>
        $invoices = $xml->createElement('invoices');
        $xml->appendChild($invoices);

        foreach ($data['invoices'] as $dataInvoice) {

            // Crea l'elemento <invoice>
            $invoice = $xml->createElement('invoice');
            $invoices->appendChild($invoice);

            // Aggiungi i campi principali della fattura
            $fields = [
                'document_type',
                'order_id',
                'order_date',
                'order_reference',
                'current_status',
                'invoice_id',
                'invoice_number',
                'invoice_date',
                'products_tax_excl',
                'discounts_tax_excl',
                'shipping_tax_excl',
                'wrapping_tax_excl',
                'products_tax_incl',
                'discounts_tax_incl',
                'shipping_tax_incl',
                'wrapping_tax_incl',
                'total_tax_excl',
                'total_taxes',
                'total_tax_incl',
                'total_paid',
                'vat_code',
                'rounds',
                'nc',
                'payment',
                'carrier',
                'shop_address',
                'foreign',
                'discount_note'
            ];

            foreach ($fields as $field) {
                $value = $dataInvoice[$field] ?? '';
                $element = $xml->createElement($field, htmlspecialchars($value));
                $invoice->appendChild($element);
            }

            // Aggiungi i dati del cliente
            $customer = $xml->createElement('customer');
            $invoice->appendChild($customer);

            $customerFields = [
                'id',
                'id_customer',
                'gender',
                'firstname',
                'lastname',
                'birthday',
                'pec',
                'uid',
                'email',
                'new'
            ];

            foreach ($customerFields as $field) {
                $value = $dataInvoice['customer'][$field] ?? '';
                $element = $xml->createElement($field, htmlspecialchars($value));
                $customer->appendChild($element);
            }

            // Aggiungi indirizzi di consegna e fatturazione
            $addressTypes = ['address_delivery', 'address_invoice'];

            foreach ($addressTypes as $type) {
                $address = $xml->createElement($type);
                $customer->appendChild($address);

                $addressFields = [
                    'subject',
                    'company',
                    'firstname',
                    'lastname',
                    'address1',
                    'address2',
                    'postcode',
                    'city',
                    'state_name',
                    'country_name',
                    'phone',
                    'phone_mobile',
                    'vat_number',
                    'dni',
                    'state',
                    'country'
                ];

                foreach ($addressFields as $field) {
                    $value = $dataInvoice['customer'][$type][$field] ?? '';
                    $element = $xml->createElement($field, htmlspecialchars($value));
                    $address->appendChild($element);
                }
            }

            // Aggiungi le righe dei prodotti
            $rows = $xml->createElement('rows');
            $invoice->appendChild($rows);

            foreach ($dataInvoice['rows'] as $rowData) {
                $row = $xml->createElement('row');
                $rows->appendChild($row);

                $productFields = [
                    'ean13',
                    'reference',
                    'original_price_tax_excl',
                    'product_price_tax_excl',
                    'original_price_tax_incl',
                    'discount_percent',
                    'reduction_amount',
                    'price_tax_excl',
                    'unit_price_tax_excl',
                    'unit_price_tax_incl',
                    'qty',
                    'total_tax_excl',
                    'total_price_tax_excl',
                    'total_price_tax_incl',
                    'total_tax_incl',
                    'tax_rate',
                    'product_id',
                    'product_attribute_id',
                    'product_name',
                    'size',
                    'color',
                    'stock_service',
                    'product_in_stock',
                    'product_refunded',
                    'product_returned',
                    'product_reinjected',
                    'image_url'
                ];

                foreach ($productFields as $field) {
                    $value = $rowData[$field] ?? '';
                    $element = $xml->createElement($field, htmlspecialchars($value));
                    $row->appendChild($element);
                }

                // Aggiungi product_check_qty
                $checkQty = $xml->createElement('product_check_qty');
                $row->appendChild($checkQty);

                $checkFields = ['employee', 'date_checked', 'is_checked'];
                foreach ($checkFields as $field) {
                    $value = $rowData['product_check_qty'][$field] ?? '';
                    $element = $xml->createElement($field, htmlspecialchars($value));
                    $checkQty->appendChild($element);
                }

                // Aggiungi customization (vuoto)
                $customization = $xml->createElement('customization');
                $row->appendChild($customization);

                // Aggiungi attributes
                $attributes = $xml->createElement('attributes');
                $row->appendChild($attributes);

                foreach ($rowData['attributes'] as $level => $attrData) {
                    $levelElement = $xml->createElement($level);
                    $attributes->appendChild($levelElement);

                    $attrFields = ['id_attribute_group', 'group', 'name'];
                    foreach ($attrFields as $field) {
                        $value = $attrData[$field] ?? '';
                        $element = $xml->createElement($field, htmlspecialchars($value));
                        $levelElement->appendChild($element);
                    }
                }

                // Aggiungi product_position
                $position = $xml->createElement('product_position');
                $row->appendChild($position);

                if (!empty($rowData['product_position'])) {
                    $positionFields = ['warehouse', 'shelf', 'col', 'level'];
                    foreach ($positionFields as $field) {
                        $value = $rowData['product_position'][$field] ?? '';
                        $element = $xml->createElement($field, htmlspecialchars($value));
                        $position->appendChild($element);
                    }
                }
            }

            // Aggiungi le fees
            $fees = $xml->createElement('fees');
            $invoice->appendChild($fees);

            $feeFields = ['fee_tax_excl', 'fee_tax_rate', 'fee_tax_incl'];
            foreach ($feeFields as $field) {
                $value = $dataInvoice['fees'][$field] ?? '0';
                $element = $xml->createElement($field, htmlspecialchars($value));
                $fees->appendChild($element);
            }
        }


        // Salva il file XML
        $xmlContent = $xml->saveXml();
        return $xmlContent;
    }

    protected function getTestJsonData()
    {
        // Esempio di utilizzo:
        $jsonData = '{
            "document_type": "60",
            "order_id": "139014",
            "order_date": "2025-07-10 09:24:41",
            "order_reference": "1000139014",
            "current_status": "IN ATTESA DI BONIFICO BANCARIO",
            "invoice_id": "139014",
            "invoice_number": "139014",
            "invoice_date": "2025-07-10 09:24:41",
            "products_tax_excl": "636.92",
            "discounts_tax_excl": "0",
            "shipping_tax_excl": "6.48",
            "wrapping_tax_excl": "0",
            "products_tax_incl": "777.040000",
            "discounts_tax_incl": "0.000000",
            "shipping_tax_incl": "7.900000",
            "wrapping_tax_incl": "0.000000",
            "total_tax_excl": "643.395410",
            "total_taxes": "141.54459",
            "total_tax_incl": "784.940000",
            "total_paid": "784.940000",
            "vat_code": "",
            "rounds": "-0.01",
            "nc": "0",
            "payment": "BONIFICO ANTICIPATO",
            "carrier": "BRT Corriere Espresso",
            "shop_address": "www.dalavoro.com - site by: Soc. IMPRENDO s.r.l.s. - Via Mafalda di Savoia 28,30 - P.iva: IT03412990784 - 87013 Fagnano Castello (Cs) - Cosenza - Italia",
            "foreign": "0",
            "discount_note": "",
            "customer": {
                "id": "DL109285",
                "id_customer": "109285",
                "gender": "",
                "firstname": "Luigi",
                "lastname": "Dottarelli",
                "birthday": "0000-00-00",
                "pec": "HOTELEDENBOLSENA@PEC.IT",
                "uid": "W7YVJK9",
                "email": "info@hoteledenbolsena.it",
                "new": "1",
                "address_delivery": {
                    "subject": "G",
                    "company": "Eden Di Dottarelli L. E C. Sas",
                    "firstname": "Luigi",
                    "lastname": "Dottarelli",
                    "address1": "Via Cassia Nord. Km 114,200, 46",
                    "address2": "",
                    "postcode": "01023",
                    "city": "Bolsena",
                    "state_name": "VITERBO",
                    "country_name": "ITALIA",
                    "phone": "0761799015",
                    "phone_mobile": "3478539856",
                    "vat_number": "",
                    "dni": "",
                    "state": "VT",
                    "country": "IT"
                },
                "address_invoice": {
                    "subject": "G",
                    "company": "Eden Di Dottarelli L. E C. Sas",
                    "firstname": "Luigi",
                    "lastname": "Dottarelli",
                    "address1": "Via Cassia Nord. Km 114,200, 46",
                    "address2": "",
                    "postcode": "01023",
                    "city": "Bolsena",
                    "state_name": "VITERBO",
                    "country_name": "ITALIA",
                    "phone": "0761799015",
                    "phone_mobile": "3478539856",
                    "vat_number": "01200860565",
                    "dni": "",
                    "state": "VT",
                    "country": "IT"
                }
            },
            "rows": [
                {
                    "ean13": "8000000090072",
                    "reference": "INTPAG4",
                    "original_price_tax_excl": "2.460000",
                    "product_price_tax_excl": "2.460000",
                    "original_price_tax_incl": "3",
                    "discount_percent": "0",
                    "reduction_amount": "0",
                    "price_tax_excl": "2.460000",
                    "unit_price_tax_excl": "2.460000",
                    "unit_price_tax_incl": "3.001200",
                    "qty": "33",
                    "total_tax_excl": "81.180000",
                    "total_price_tax_excl": "81.180000",
                    "total_price_tax_incl": "99.040000",
                    "total_tax_incl": "99.040000",
                    "tax_rate": "22",
                    "product_id": "7250",
                    "product_attribute_id": "107836",
                    "product_check_qty": {
                        "employee": " ",
                        "date_checked": "",
                        "is_checked": "0"
                    },
                    "product_name": "AGGIUNTO RICAMO LOGO",
                    "size": "0",
                    "color": "",
                    "stock_service": "-1",
                    "product_in_stock": "825",
                    "product_refunded": "0",
                    "product_returned": "0",
                    "product_reinjected": "0",
                    "image_url": "/var/www/vhosts/dalavoro.com/httpdocs/img/p/2/1/2/6/4/21264-small_default.jpg",
                    "customization": {},
                    "attributes": {
                        "lev_0": {
                            "id_attribute_group": "13",
                            "group": "Taglie Disponibili",
                            "name": "0"
                        }
                    },
                    "product_position": {}
                }
            ],
            "fees": {
                "fee_tax_excl": "0",
                "fee_tax_rate": "0",
                "fee_tax_incl": "0"
            }
        }';

        return json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
    }
}