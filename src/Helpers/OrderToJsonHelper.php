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

use Doctrine\DBAL\Types\Types;

class OrderToJsonHelper extends DependencyHelper
{
    protected $document;
    protected $order;
    protected $delivery;
    protected $invoice;
    protected $invoiceJson;

    protected $invoicesJson;
    protected $errors = [];

    protected $document_type;

    public function __construct()
    {
        parent::__construct();
        $this->invoicesJson = [];

    }

    public function toXml(): string|false
    {
        if ($this->invoicesJson) {
            $xmlHelper = new OrderToXmlHelper();
            $xml = $xmlHelper->generateInvoiceXml($this->invoicesJson);

            return $xml;
        }

        return false;
    }

    public function createDocuments(int $documentType, array $ids): array|false
    {
        $this->document_type = $documentType;
        $invoicesJson = [];
        foreach ($ids as $id) {
            $invoice = $this->addNewInvoice($documentType, $id);
            if ($invoice) {
                $invoice = $this->fillOrder($invoice);
                $invoice['rows'] = $this->fillProductRow($id);
            }
            $invoicesJson['invoices'][] = $invoice;
        }

        $this->invoicesJson = $invoicesJson;
        return $invoicesJson;
    }

    public function addNewInvoice($documentType, $id)
    {
        $invoice = [
            "document_type" => $documentType,
            "order_id" => $id,
            "order_date" => "",
            "order_reference" => "",
            "current_status" => "",
            "invoice_id" => "",
            "invoice_number" => "",
            "invoice_date" => "",
            "products_tax_excl" => "",
            "discounts_tax_excl" => "",
            "shipping_tax_excl" => "",
            "wrapping_tax_excl" => "",
            "products_tax_incl" => "",
            "discounts_tax_incl" => "",
            "shipping_tax_incl" => "",
            "wrapping_tax_incl" => "",
            "total_tax_excl" => "",
            "total_taxes" => "",
            "total_tax_incl" => "",
            "total_paid" => "",
            "vat_code" => "",
            "rounds" => "",
            "nc" => "",
            "payment" => "",
            "carrier" => "",
            "shop_address" => "",
            "foreign" => "",
            "discount_note" => "",
            "customer" => [
                "id" => "",
                "id_customer" => "",
                "gender" => "",
                "firstname" => "",
                "lastname" => "",
                "birthday" => "",
                "pec" => "",
                "uid" => "",
                "email" => "",
                "new" => "",
                "address_delivery" => [
                    "subject" => "",
                    "company" => "",
                    "firstname" => "",
                    "lastname" => "",
                    "address1" => "",
                    "address2" => "",
                    "postcode" => "",
                    "city" => "",
                    "state_name" => "",
                    "country_name" => "",
                    "phone" => "",
                    "phone_mobile" => "",
                    "vat_number" => "",
                    "dni" => "",
                    "state" => "",
                    "country" => ""
                ],
                "address_invoice" => [
                    "subject" => "",
                    "company" => "",
                    "firstname" => "",
                    "lastname" => "",
                    "address1" => "",
                    "address2" => "",
                    "postcode" => "",
                    "city" => "",
                    "state_name" => "",
                    "country_name" => "",
                    "phone" => "",
                    "phone_mobile" => "",
                    "vat_number" => "",
                    "dni" => "",
                    "state" => "",
                    "country" => ""
                ]
            ]
        ];

        return $invoice;
    }

    protected function getOrderDetails($id_order)
    {
        $order = new \Order($id_order);
        $orderDetails = $order->getOrderDetailList();

        return $orderDetails;
    }

    protected function getLocation($id_product, $id_product_attribute): array
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT
                UPPER(a.name) as `warehouse`,
                UPPER(b.name) as `shelf`,
                UPPER(c.name) as `column`,
                UPPER(d.name) as `level`,
                loc.location as `location`
            FROM
                {$pfx}product_location loc
            LEFT JOIN
                {$pfx}product_location_data a
                ON
                    (a.type='warehouse' and a.id_product_location_data=loc.id_warehouse)
            LEFT JOIN
                {$pfx}product_location_data b
                ON
                    (b.type='shelf' and b.id_product_location_data=loc.id_shelf)
            LEFT JOIN
                {$pfx}product_location_data c
                ON
                    (c.type='column' and c.id_product_location_data=loc.id_column)
            LEFT JOIN
                {$pfx}product_location_data d
                ON
                    (d.type='level' and d.id_product_location_data=loc.id_level)
            WHERE
                loc.id_product = :id_product
                AND
                loc.id_product_attribute = :id_product_attribute
        QUERY;

        $statement = $this->connection->executeQuery(
            $query,
            [
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
            ],
            [
                'id_product' => Types::INTEGER,
                'id_product_attribute' => Types::INTEGER
            ]
        );

        $row = $statement->fetchAssociative();
        if ($row) {
            return $row;
        }

        return [
            'warehouse' => '',
            'shelf' => '',
            'column' => '',
            'level' => '',
            'location' => '',
        ];
    }

    protected function fillProductRow($id_order)
    {
        $orderDetails = $this->getOrderDetails($id_order);
        $rows = [];

        foreach ($orderDetails as $item) {
            $id_order_detail = (int) $item['id_order_detail'] ?? 0;

            $orderDetail = new \OrderDetail($id_order_detail, $this->id_lang);
            if (!\Validate::isLoadedObject($orderDetail)) {
                return false;
            }

            $id_product = (int) $orderDetail->product_id;
            $id_product_attribute = (int) $orderDetail->product_attribute_id;

            $product = new \Product($id_product, false, $this->id_lang);
            if (!\Validate::isLoadedObject($product)) {
                return false;
            }

            $combination = new \Combination($id_product_attribute, $this->id_lang);
            $tax_rate = $this->getTaxRate($product->id_tax_rules_group);
            $stockServiceCheck = $this->getStockServiceCheckProduct($id_product, $id_product_attribute);
            $combinations = $this->getCombinations($id_product, $id_product_attribute);
            $location = $this->getLocation($id_product, $id_product_attribute);

            $originalPriceTaxIncl = $this->addTax($orderDetail->original_product_price, $tax_rate);

            $productArray = [
                "ean13" => $combination->ean13 ?: $product->ean13,
                "reference" => $product->ean13,
                "original_price_tax_excl" => $product->price,
                "product_price_tax_excl" => $orderDetail->product_price,
                "original_price_tax_incl" => $originalPriceTaxIncl,
                "discount_percent" => $orderDetail->reduction_percent,
                "reduction_amount" => $orderDetail->reduction_amount_tax_excl,
                "price_tax_excl" => $orderDetail->unit_price_tax_excl,
                "unit_price_tax_excl" => $orderDetail->unit_price_tax_excl,
                "unit_price_tax_incl" => $orderDetail->unit_price_tax_incl,
                "qty" => $orderDetail->product_quantity,
                "total_tax_excl" => $orderDetail->total_price_tax_excl,
                "total_tax_incl" => $orderDetail->total_price_tax_incl,
                "total_price_tax_excl" => $orderDetail->total_price_tax_excl,
                "total_price_tax_incl" => $orderDetail->total_price_tax_incl,
                "tax_rate" => $tax_rate,
                "product_id" => $id_product,
                "product_attribute_id" => $id_product_attribute,
                "product_check_qty" => [
                    "employee" => $stockServiceCheck['employee'],
                    "date_checked" => $stockServiceCheck['date_checked'],
                    "is_checked" => $stockServiceCheck['is_checked'],
                ],
                "product_name" => \Tools::strtoupper($product->name),
                "color" => \Tools::strtoupper($combinations['lev0']['name'] ?? ''),
                "size" => \Tools::strtoupper($combinations['lev1']['name'] ?? ''),
                "stock_service" => (int) $stockServiceCheck,
                ['quantity'],
                "product_in_stock" => (int) $orderDetail->product_quantity_in_stock,
                "product_refunded" => (int) $orderDetail->product_quantity_refunded,
                "product_returned" => (int) $orderDetail->product_quantity_return,
                "product_reinjected" => (int) $orderDetail->product_quantity_reinjected,
                "image_url" => $this->getProductImageUrl($product->id),
                "customization" => "",
                "attributes" => [
                    "lev_0" => [
                        "id_attribute_group" => $combinations['lev0']['id_attribute_group'] ?? '',
                        "group" => $combinations['lev0']['group'] ?? '',
                        "name" => $combinations['lev0']['name'] ?? '',
                    ],
                    "lev_1" => [
                        "id_attribute_group" => $combinations['lev1']['id_attribute_group'] ?? '',
                        "group" => $combinations['lev1']['group'] ?? '',
                        "name" => $combinations['lev1']['name'] ?? '',
                    ],
                    "lev_2" => [
                        "id_attribute_group" => $combinations['lev2']['id_attribute_group'] ?? '',
                        "group" => $combinations['lev2']['group'] ?? '',
                        "name" => $combinations['lev2']['name'] ?? '',
                    ]
                ],
                "product_position" => [
                    "warehouse" => $location['warehouse'],
                    "shelf" => $location['shelf'],
                    "col" => $location['column'],
                    "level" => $location['level']
                ]
            ];

            $rows[] = $productArray;
        }

        return $rows;
    }

    protected function extractTax($totalTaxIncl, $taxRate)
    {
        $totalTaxExcl = $totalTaxIncl / (1 + ($taxRate / 100));
        return \Tools::ps_round($totalTaxExcl, 6, PS_ROUND_HALF_UP);
    }

    protected function addTax($price, $tax_rate)
    {
        $value_tax_incl = $price * (100 + $tax_rate) / 100;
        $price_tax_incl = \Tools::ps_round($value_tax_incl, 2, PS_ROUND_HALF_UP);

        return $price_tax_incl;
    }

    protected function getTaxAmount($totalTaxIncl, $totalTaxExcl)
    {
        return $totalTaxIncl - $totalTaxExcl;
    }

    protected function isNc($id_order)
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT
                cr.description
            FROM
                {$pfx}cart_rule cr
            INNER JOIN
                {$pfx}order_cart_rule ocr
                ON 
                    (cr.id_cart_rule=ocr.id_cart_rule)
            WHERE
                ocr.id_order = :id_order
        QUERY;

        $statement = $this->connection->executeQuery($query, ['id_order' => $id_order]);
        $cart_rule = $statement->fetchOne();
        if (preg_match('/^NC-/', $cart_rule)) {
            return true;
        }

        return false;
    }

    protected function isForeign($id_address_invoice)
    {
        $address_invoice = new \Address($id_address_invoice);
        $country = new \Country((int) $address_invoice->id_country);
        $it = ['IT', 'ITI'];

        return (int) (!in_array($country->iso_code, $it));
    }

    protected function getDiscountNote($id_order)
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT
                cr.description
            FROM
                {$pfx}cart_rule cr
            INNER JOIN
                {$pfx}order_cart_rule ocr
                ON (cr.id_cart_rule=ocr.id_cart_rule)
            WHERE
                ocr.id_order = :id_order
        QUERY;
        $statement = $this->connection->executeQuery($query, ['id_order' => $id_order]);
        $descritption = $statement->fetchOne();

        return $descritption;
    }

    protected function getGender($id_gender)
    {
        $gender = new \Gender($id_gender, $this->id_lang);

        return $gender->name;
    }

    protected function getTaxRate($id_tax_rules_group)
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT
                t.rate
            FROM
                {$pfx}tax t
            INNER JOIN
                {$pfx}tax_rule tr
                ON (tr.id_tax=t.id_tax)
            WHERE
                tr.id_tax_rules_group = :id_tax_rules_group
        QUERY;
        $statement = $this->connection->executeQuery($query, ['id_tax_rules_group' => $id_tax_rules_group]);
        $tax_rate = (float) $statement->fetchOne();

        return \Tools::ps_round($tax_rate, 2, PS_ROUND_HALF_UP);
    }

    protected function getInvoiceCustomer($id_customer)
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT *
            FROM
                {$pfx}customer_invoice
            WHERE
                id_customer = :id_customer
        QUERY;
        $statement = $this->connection->executeQuery($query, ['id_customer' => $id_customer]);
        $customer_invoice = $statement->fetchAssociative();

        return $customer_invoice;
    }

    protected function isNewCustomer($id_customer)
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT
                count(id_order)
            FROM
                {$pfx}orders
            WHERE
                id_customer = :id_customer
        QUERY;
        $statement = $this->connection->executeQuery($query, ['id_customer' => $id_customer]);
        $isNewCustomer = ((int) $statement->fetchOne()) == 1;

        return $isNewCustomer;
    }

    public function getProductImageUrl($id_product, $size = 'large')
    {
        $no_image = '/img/404.gif';
        $product = new \Product($id_product, false, $this->context->getContext()->language->id);
        if (!\Validate::isLoadedObject($product)) {
            return $no_image;
        }

        $cover = \Product::getCover($id_product);
        if (!$cover) {
            return $no_image;
        }
        $id_image = $cover['id_image'];
        $image = new \Image($id_image);
        if (!\Validate::isLoadedObject($image)) {
            return $no_image;
        }
        $dir_path = \Image::getImgFolderStatic($id_image);
        $format = $image->image_format;
        $image_default = "/img/p/{$dir_path}{$id_image}-{$size}_default.{$format}";
        if (!file_exists(_PS_ROOT_DIR_ . $image_default)) {
            $image_default = $no_image;
        }

        $shopUrl = $this->context->getContext()->shop->getBaseURL(true);

        return $shopUrl . $image_default;
    }

    protected function getAddress($id_address)
    {
        $address = new \Address($id_address, $this->id_lang);
        $country = new \Country($address->id_country, $this->id_lang);
        $state = new \State($address->id_state, $this->id_lang);

        return [
            'address' => $address,
            'country' => $country,
            'state' => $state,
        ];
    }

    public function getStockServiceCheckProduct($id_product, $id_product_attribute)
    {
        $pfx = _DB_PREFIX_;
        $query = <<<QUERY
            SELECT
                a.id_employee,
                a.date_upd,
                a.date_add as is_checked,
                b.quantity
            FROM
                {$pfx}product_stock_service_check a
            LEFT JOIN
                {$pfx}product_stock_service b
                ON
                    (a.id_product=b.id_product AND b.id_product_attribute = :id_product_attribute)
            WHERE
                a.id_product = :id_product
        QUERY;
        $statement = $this->connection->executeQuery(
            $query,
            [
                'id_product_attribute' => $id_product_attribute,
                'id_product' => $id_product
            ],
            [
                'id_product_attribute' => Types::INTEGER,
                'id_product' => Types::INTEGER,
            ]
        );
        $row = $statement->fetchAssociative();

        if ($row) {
            $employee = new \Employee($row['id_employee']);
            return [
                'employee' => \Tools::strtoupper($employee->firstname . ' ' . $employee->lastname),
                'date_checked' => \Tools::displayDate($row['date_upd'], false),
                'is_checked' => $row['is_checked'] ? true : false,
                'quantity' => (int) $row['quantity']
            ];
        }

        return [
            'employee' => '--',
            'date_checked' => '--',
            'is_checked' => 0,
            'quantity' => 0,
        ];
    }

    public function getCombinations($id_product, $id_product_attribute)
    {
        /**
         * LEV0 : Taglia
         * LEV1 : Colore
         * LEV2 : Rifinitura
         */

        $size_ids = \Configuration::get("MPSTOCKADV_ATTRIBUTE_SIZE");
        $color_ids = \Configuration::get("MPSTOCKADV_ATTRIBUTE_COLOR");
        $attributes = [];

        if ($size_ids) {
            $size_ids = json_decode($size_ids, true, 512, JSON_THROW_ON_ERROR);
        }

        if ($color_ids) {
            $color_ids = json_decode($color_ids, true, 512, JSON_THROW_ON_ERROR);
        }

        $product = new \Product($id_product, false, $this->id_lang);
        if (!\Validate::isLoadedObject($product)) {
            return false;
        }

        $combinations = $product->getAttributeCombinations($this->id_lang);
        foreach ($combinations as $combination) {
            if ($combination['id_product_attribute'] == $id_product_attribute) {
                $group_name = \Tools::strtolower($combination['group_name']);
                if (preg_match('/^color/i', $group_name)) {
                    $attributes['lev0'] = [
                        'id_attribute_group' => $combination['id_attribute_group'],
                        'group' => $combination['group_name'],
                        'name' => $combination['attribute_name']
                    ];
                } elseif (preg_match('/^fantas/i', $group_name)) {
                    $attributes['lev0'] = [
                        'id_attribute_group' => $combination['id_attribute_group'],
                        'group' => $combination['group_name'],
                        'name' => $combination['attribute_name']
                    ];
                } elseif (preg_match('/^tagl/i', $group_name)) {
                    $attributes['lev1'] = [
                        'id_attribute_group' => $combination['id_attribute_group'],
                        'group' => $combination['group_name'],
                        'name' => $combination['attribute_name']
                    ];
                } elseif (preg_match('/^rifinit/i', $group_name)) {
                    $attributes['lev2'] = [
                        'id_attribute_group' => $combination['id_attribute_group'],
                        'group' => $combination['group_name'],
                        'name' => $combination['attribute_name']
                    ];
                }
            }
        }

        return $attributes;

    }

    public function fillOrder($invoice): array|false
    {
        $id_order = (int) $invoice['order_id'];
        $order = new \Order($id_order);
        if (!\Validate::isLoadedObject($order)) {
            return false;
        }
        $orderState = new \OrderState($order->current_state, $this->id_lang);
        $currentStatus = $orderState->name;

        $carrier = new \Carrier($order->id_carrier);
        $carrierName = $carrier->name;

        $shopAddress = \Tools::strtoupper(
            \Configuration::get("PS_SHOP_NAME") . ' ' .
            \Configuration::get("PS_SHOP_ADDR1") . ' ' .
            \Configuration::get("PS_SHOP_CODE") . ' ' .
            \Configuration::get("PS_SHOP_CITY") . ' '
        );

        $customerPrefix = \Configuration::get("MPSTOCKADV_CUSTOMER_PREFIX", null, null, null, 'DL');
        $customer = new \Customer($order->id_customer);
        $invoice_customer = $this->getInvoiceCustomer($order->id_customer);
        $address_delivery = $this->getAddress($order->id_address_delivery);
        $address_invoice = $this->getAddress($order->id_address_invoice);

        $totalOrderTaxIncl = $order->total_paid_tax_incl;
        $tax_rate = 22;
        $totalOrderTaxExcl = $this->extractTax($totalOrderTaxIncl, $tax_rate);
        //Scorporo totale dell'IVA dall'ammontare dell'ordine
        $totalTaxesReal = $this->getTaxAmount($totalOrderTaxIncl, $totalOrderTaxExcl);
        //Totale IVA secondo i calcoli di Prestashop
        $totalTaxesOrder = $this->getTaxAmount($order->total_paid_tax_incl, $order->total_paid_tax_excl);
        //Arrotondamenti
        $rounds = \Tools::ps_round($totalTaxesOrder - $totalTaxesReal, 2, PS_ROUND_HALF_UP);

        switch ($this->document_type) {
            case \Configuration::get("MPSTOCKADV_TYPE_ORDER"):
                $invoice_id = $order->id;
                $invoice_number = $order->id;
                $invoice_date = $order->date_add;

                break;
            case \Configuration::get("MPSTOCKADV_TYPE_DELIVERY"):
                $invoice_id = $order->id;
                $invoice_number = $order->delivery_number;
                $invoice_date = $order->delivery_date;

                break;
            case \Configuration::get("MPSTOCKADV_TYPE_INVOICE"):
                $orderInvoice = new \OrderInvoice($order->invoice_number);
                $invoice_id = $orderInvoice->id;
                $invoice_number = $orderInvoice->number;
                $invoice_date = $orderInvoice->date_add;

                break;
        }

        $invoice = [
            "document_type" => $invoice['document_type'],
            "order_id" => $id_order,
            "order_date" => $order->date_add,
            "order_reference" => $order->reference,
            "current_status" => $currentStatus,
            "invoice_id" => $invoice_id,
            "invoice_number" => $invoice_number,
            "invoice_date" => $invoice_date,
            "products_tax_excl" => $order->total_products,
            "discounts_tax_excl" => $order->total_discounts_tax_excl,
            "shipping_tax_excl" => $order->total_shipping_tax_excl,
            "wrapping_tax_excl" => $order->total_wrapping_tax_excl,
            "products_tax_incl" => $order->total_products_wt,
            "discounts_tax_incl" => $order->total_discounts_tax_incl,
            "shipping_tax_incl" => $order->total_shipping_tax_incl,
            "wrapping_tax_incl" => $order->total_wrapping_tax_incl,
            "total_tax_excl" => $order->total_paid_tax_excl,
            "total_taxes" => $totalTaxesOrder,
            "total_tax_incl" => $order->total_paid_tax_incl,
            "total_paid" => $order->total_paid_real,
            "vat_code" => "",
            "rounds" => $rounds,
            "nc" => (int) $this->isNc($id_order),
            "payment" => \Tools::strtoupper($order->payment),
            "carrier" => \Tools::strtoupper($carrierName),
            "shop_address" => $shopAddress,
            "foreign" => (int) $this->isForeign($order->id_address_invoice),
            "discount_note" => $this->getDiscountNote($order->id),
            "customer" => [
                "id" => "{$customerPrefix}{$customer->id}",
                "id_customer" => $customer->id,
                "gender" => \Tools::strtoupper($this->getGender($customer->id_gender)),
                "firstname" => \Tools::strtoupper($customer->firstname),
                "lastname" => \Tools::strtoupper($customer->lastname),
                "birthday" => $customer->birthday,
                "pec" => $invoice_customer['pec'] ?? '',
                "uid" => $invoice_customer['sdi'] ?? '',
                "email" => $customer->email,
                "new" => (int) $this->isNewCustomer($order->id_customer),
                "address_delivery" => [
                    "subject" => \Tools::strtoupper($invoice_customer['type'] ?? ''),
                    "company" => \Tools::strtoupper($address_delivery['address']->company),
                    "firstname" => \Tools::strtoupper($address_delivery['address']->firstname),
                    "lastname" => \Tools::strtoupper($address_delivery['address']->lastname),
                    "address1" => \Tools::strtoupper($address_delivery['address']->address1),
                    "address2" => \Tools::strtoupper($address_delivery['address']->address2),
                    "postcode" => \Tools::strtoupper($address_delivery['address']->postcode),
                    "city" => \Tools::strtoupper($address_delivery['address']->city),
                    "state_name" => \Tools::strtoupper($address_delivery['state']->name),
                    "country_name" => \Tools::strtoupper($address_delivery['country']->name),
                    "phone" => \Tools::strtoupper($address_delivery['address']->phone),
                    "phone_mobile" => \Tools::strtoupper($address_delivery['address']->phone_mobile),
                    "vat_number" => $invoice_customer['vat_number'] ?? '',
                    "dni" => $invoice_customer['fiscal_code'] ?? '',
                    "state" => \Tools::strtoupper($address_delivery['state']->iso_code),
                    "country" => \Tools::strtoupper($address_delivery['country']->iso_code),
                ],
                "address_invoice" => [
                    "subject" => \Tools::strtoupper($invoice_customer['type'] ?? ''),
                    "company" => \Tools::strtoupper($address_invoice['address']->company),
                    "firstname" => \Tools::strtoupper($address_invoice['address']->firstname),
                    "lastname" => \Tools::strtoupper($address_invoice['address']->lastname),
                    "address1" => \Tools::strtoupper($address_invoice['address']->address1),
                    "address2" => \Tools::strtoupper($address_invoice['address']->address2),
                    "postcode" => \Tools::strtoupper($address_invoice['address']->postcode),
                    "city" => \Tools::strtoupper($address_invoice['address']->city),
                    "state_name" => \Tools::strtoupper($address_invoice['state']->name),
                    "country_name" => \Tools::strtoupper($address_invoice['country']->name),
                    "phone" => \Tools::strtoupper($address_invoice['address']->phone),
                    "phone_mobile" => \Tools::strtoupper($address_invoice['address']->phone_mobile),
                    "vat_number" => $invoice_customer['vat_number'] ?? '',
                    "dni" => $invoice_customer['fiscal_code'] ?? '',
                    "state" => \Tools::strtoupper($address_invoice['state']->iso_code),
                    "country" => \Tools::strtoupper($address_invoice['country']->iso_code),
                ]
            ]
        ];

        return $invoice;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getInvoiceJson()
    {
        return $this->invoiceJson;
    }

    public function getOrder($id_order)
    {
        $order = new \Order($id_order);
        if (!\validate::isLoadedObject($order)) {
            $this->errors[] = "Ordine {$id_order} non valido.";
            return false;
        }

        $jsonInvoice = $this->getJsonInvoice($order->id);

        $this->fillJsonOrder($order);
    }

    protected function getJsonInvoice($order_id)
    {
        foreach ($this->invoicesJson as $invoice) {
            if ($invoice['order_id'] == $order_id) {
                return $invoice;
            }
        }

        $invoice = $this->getJsonInvoiceStructure();
        return $invoice;
    }

    protected function replace($order_id, $invoice)
    {
        foreach ($this->invoicesJson as $key => $invoice) {
            if ($invoice['order_id'] == $order_id) {
                $this->invoicesJson[$key] = $invoice;
            }
        }
    }

    protected function delete($order_id)
    {
        foreach ($this->invoicesJson as $key => $invoice) {
            if ($invoice['order_id'] == $order_id) {
                unset($this->invoicesJson[$key]);
            }
        }
    }

    protected function fillJsonOrder($id_order)
    {

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

    public function getDocument()
    {
        return $this->document;
    }

    protected function setDocumentType($type)
    {
        $this->invoiceJson['document_type'] = $type;
    }
}