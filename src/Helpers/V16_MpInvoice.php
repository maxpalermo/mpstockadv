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

class MpInvoice
{
    public $id_doc_type;
    public $id_order;
    public $id_order_invoice;
    public $header;
    public $order;
    public $order_invoice;
    public $address_invoice;
    public $address_delivery;
    public $country_invoice;
    public $dom;
    public $id_lang;
    protected $products;
    protected $total_products;
    protected $type_document;
    protected $current_type;
    protected $discount_note;
    protected $nc;

    public function __construct($id_order, $id_doc_type = 0, $header = false)
    {
        $this->id_lang = (int) Context::getContext()->language->id;
        $this->id_order = (int) $id_order;
        $this->id_doc_type = $id_doc_type;
        $this->header = $header;
        $this->order = new Order($this->id_order);
        $this->id_order_invoice = $this->getIdOrderInvoice();
        $this->order_invoice = new OrderInvoice((int) $this->id_order_invoice);
        $this->address_invoice = new Address($this->order->id_address_invoice);
        $this->address_delivery = new Address($this->order->id_address_delivery);
        $this->country_invoice = new Country($this->address_invoice->id_country);
        $this->products = [];
        $this->dom = new DomDocument();
    }

    public static function getDocumentType($type)
    {
        $documents = [
            [
                'id' => 60,
                'name' => 'order',
            ],
            [
                'id' => 71,
                'name' => 'invoice',
            ],
            [
                'id' => 78,
                'name' => 'delivery',
            ],
            [
                'id' => 98,
                'name' => 'return',
            ],
            [
                'id' => 99,
                'name' => 'credit_slip',
            ],
        ];

        foreach ($documents as $doc) {
            if ($doc['id'] == $type) {
                return $doc['name'];
            }
        }

        return '';
    }

    public function getDocument($type = 'order')
    {
        switch ($type) {
            case 'order':
                $this->current_type = 'order';
                $content = $this->exportOrder();

                break;
            case 'invoice':
                $this->current_type = 'invoice';
                $content = $this->exportInvoice();

                break;
            case 'delivery':
                $this->current_type = 'delivery';
                $content = $this->exportDelivery();

                break;
            default:
                $this->current_type = 'order';
                $content = $this->exportOrder();
        }
        if ($this->header) {
            $content = $this->getHeader() . "\n<invoices>" . $content . "\n</invoices>";
        }

        return $content;
    }

    public function getIdOrderInvoice()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_order_invoice')
            ->from('order_invoice')
            ->where('id_order=' . (int) $this->id_order);

        return (int) $db->getValue($sql);
    }

    public function exportOrder()
    {
        $this->dom = new DOMDocument;
        $this->dom->formatOutput = true;
        $this->type_document = 'order';
        $rootElem = $this->dom->createElement('invoice');
        $rootNode = $this->dom->appendChild($rootElem);
        $this->products = $this->getProducts($this->type_document);

        return $this->finalizeDoc($rootNode);
    }

    public function exportDelivery()
    {
        $this->dom = new DOMDocument;
        $this->dom->formatOutput = true;
        $this->type_document = 'delivery';
        $rootElem = $this->dom->createElement('invoice');
        $rootNode = $this->dom->appendChild($rootElem);
        $this->products = $this->getProducts($this->type_document);

        return $this->finalizeDoc($rootNode);
    }

    public function exportInvoice()
    {
        $this->dom = new DOMDocument;
        $this->dom->formatOutput = true;
        $this->type_document = 'invoice';
        $rootElem = $this->dom->createElement('invoice');
        $rootNode = $this->dom->appendChild($rootElem);
        $this->products = $this->getProducts($this->type_document);

        return $this->finalizeDoc($rootNode);
    }

    public function finalizeDoc($rootNode)
    {
        $rootNode = $this->addNodes($rootNode, $this->getDocumentNode());

        $customerNode = $this->createCustomerNode();
        $productsNode = $this->createProductsNode();
        $feesNode = $this->createFeesNode();

        $rootNode->appendChild($customerNode);
        $rootNode->appendChild($productsNode);
        $rootNode->appendChild($feesNode);

        $xml = $this->dom->saveXML();
        $lines = preg_split("/\r\n|\n|\r/", $xml);
        array_shift($lines);
        $xml = implode("\n", $lines);

        return $xml;
    }

    public function getDiscountNote()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('cr.description')
            ->from('cart_rule', 'cr')
            ->innerJoin('order_cart_rule', 'ocr', 'cr.id_cart_rule=ocr.id_cart_rule')
            ->where('ocr.id_order=' . (int) $this->order->id);
        $value = $this->cleanHtml($db->getValue($sql));
        $this->discount_note = html_entity_decode($value);
    }

    public function cleanHtml($value)
    {
        $convert = [
            '&#039;' => '\'',
        ];
        $result = $value;
        foreach ($convert as $key => $value) {
            $result = str_replace($key, $value, $result);
        }

        return $result;
    }

    public function updateOrderDiscount()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('cr.code')
            ->from('cart_rule', 'cr')
            ->innerJoin('order_cart_rule', 'ocr', 'cr.id_cart_rule=ocr.id_cart_rule')
            ->where('ocr.id_order=' . (int) $this->order->id);
        $code = $db->getValue($sql);
        if ($code && $this->startsWith($code, 'NC-')) {
            $this->nc = true;
        }
    }

    public function startsWith($haystack, $needle)
    {
        $length = Tools::strlen($needle);

        return (Tools::substr($haystack, 0, $length) === $needle);
    }

    public function endsWith($haystack, $needle)
    {
        $length = Tools::strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (Tools::substr($haystack, -$length) === $needle);
    }

    public function getDocumentNode()
    {
        if ($this->current_type != 'order') {
            $this->updateOrderDiscount();
            $this->discount_note = '';
        } else {
            $this->getDiscountNote();
        }

        $invoiceNodes = [
            'document_type' => $this->id_doc_type,
            'order_id' => $this->id_order,
            'order_date' => $this->order->date_add,
            'order_reference' => $this->order->reference,
            'current_status' => $this->getCurrentStatus(),
            'invoice_id' => $this->order_invoice->id,
            'invoice_number' => $this->order_invoice->number,
            'invoice_date' => $this->order_invoice->date_add,
            'products_tax_excl' => self::extractTax($this->order->total_products_wt, 22, 2),
            'discounts_tax_excl' => self::extractTax($this->order->total_discounts_tax_incl, 22, 2),
            'shipping_tax_excl' => self::extractTax($this->order->total_shipping_tax_incl, 22, 2),
            'wrapping_tax_excl' => self::extractTax($this->order->total_wrapping_tax_incl, 22, 2),
            'products_tax_incl' => $this->order->total_products_wt,
            'discounts_tax_incl' => $this->order->total_discounts_tax_incl,
            'shipping_tax_incl' => $this->order->total_shipping_tax_incl,
            'wrapping_tax_incl' => $this->order->total_wrapping_tax_incl,
            'total_tax_excl' => $this->order->total_paid_tax_excl,
            'total_taxes' => $this->order->total_paid_tax_incl - $this->order->total_paid_tax_excl,
            'total_tax_incl' => $this->order->total_paid_tax_incl,
            'total_paid' => $this->order->total_paid_tax_incl,
            'vat_code' => $this->getVatCode($this->order),
            'rounds' => 0,
            'nc' => (int) $this->nc,
            'payment' => $this->getPayment(),
            'carrier' => $this->getCarrier(),
            'shop_address' => $this->getShopAddress(),
            'foreign' => $this->isForeign(),
            'discount_note' => $this->discount_note,
        ];

        if ($this->type_document == 'order') {
            $invoiceNodes['invoice_id'] = $this->order->id;
            $invoiceNodes['invoice_number'] = $this->order->id;
            $invoiceNodes['invoice_date'] = $this->order->date_add;
        }

        if ($this->type_document == 'delivery') {
            $invoiceNodes['invoice_id'] = $this->order->id;
            $invoiceNodes['invoice_number'] = $this->order_invoice->delivery_number;
            $invoiceNodes['invoice_date'] = $this->order_invoice->delivery_date;
        }

        return $invoiceNodes;
    }

    public function getVatCode($order)
    {
        $customer = new Customer($order->id_customer);
        if (isset($customer->cig) && $customer->cig) {
            return '22SP';
        }
        if ($this->isForeign()) {
            return 'NI7';
        }

        return '';
    }

    public function getShippingTE()
    {
        $tax_rate = 1.22;
        $shipping = $this->order->total_shipping_tax_incl / $tax_rate;

        return Tools::ps_round($shipping, 6, PS_ROUND_HALF_UP);
    }

    public function isForeign()
    {
        $id_address = (int) $this->order->id_address_invoice;
        $address = new Address($id_address);
        $country = new Country((int) $address->id_country);
        $it = ['IT', 'ITI'];

        return (int) (!in_array($country->iso_code, $it));
    }

    public function getCarrier()
    {
        $id_carrier = (int) $this->order->id_carrier;
        $carrier = new Carrier($id_carrier);

        return $carrier->name;
    }

    public function addNodes($parent, $values)
    {
        foreach ($values as $k => $n) {
            $parent->appendChild($this->createNode($k, $n));
        }

        return $parent;
    }

    public function createCustomerNode()
    {
        $node = $this->dom->createElement('customer');
        $customer = new Customer($this->order->id_customer);
        $values = [
            'id' => 'DL' . $customer->id,
            'id_customer' => $customer->id,
            'gender' => '',
            'firstname' => Tools::ucwords($customer->firstname),
            'lastname' => Tools::ucwords($customer->lastname),
            'birthday' => $customer->birthday,
            'pec' => isset($customer->pec) ? $customer->pec : '',
            'uid' => isset($customer->uid) ? $customer->uid : '',
            'email' => Tools::strtolower($customer->email),
            'new' => self::isNewCustomer($customer->id, $this->order->id),
        ];
        $node = $this->addNodes($node, $values);

        $addressDelivery = $this->dom->createElement('address_delivery');
        $addressDelivery = $this->fillAddress($addressDelivery, $this->address_delivery);

        $addressInvoice = $this->dom->createElement('address_invoice');
        $addressInvoice = $this->fillAddress($addressInvoice, $this->address_invoice);

        $node->appendChild($addressDelivery);
        $node->appendChild($addressInvoice);

        return $node;
    }

    public function fillAddress($node, $address)
    {
        $subjects = [
            '-',
            'E',
            'G',
            'F',
        ];

        // Tools::dieObject($address);

        if (isset($address->id_state)) {
            $state_obj = new State($address->id_state);
            $state = $state_obj->iso_code;
            $state_name = Tools::strtoupper($state_obj->name);
        } else {
            $state = '';
            $state_name = '';
        }

        if (isset($address->id_country)) {
            $country_obj = new Country($address->id_country);
            $country = $country_obj->iso_code;
            $country_name = Tools::strtoupper($country_obj->name[$this->id_lang]);

            if ($country == 'ITI') {
                $country = 'IT';
                $country_name = 'ITALIA';
            }
        } else {
            $country = '';
            $country_name = '';
            $state = '';
        }

        if ($this->isForeign()) {
            $address1 = Tools::ucwords($address->postcode . ', ' . $address->address1);
            $postcode = '00000';
            $state = 'EE';
        } else {
            $address1 = Tools::ucwords($address->address1);
            $postcode = Tools::ucwords($address->postcode);
            $state = Tools::strtoupper($state);
        }

        $values = [
            'subject' => isset($address->subject) ? $subjects[(int) $address->subject] : '',
            'company' => Tools::ucwords($address->company),
            'firstname' => Tools::ucwords($address->firstname),
            'lastname' => Tools::ucwords($address->lastname),
            'address1' => $address1,
            'address2' => Tools::ucwords($address->address2),
            'postcode' => $postcode,
            'city' => Tools::ucwords($address->city),
            'state_name' => $state_name,
            'country_name' => $country_name,
            'phone' => Tools::strtoupper($address->phone),
            'phone_mobile' => Tools::strtoupper($address->phone_mobile),
            'vat_number' => Tools::strtoupper($address->vat_number),
            'dni' => Tools::strtoupper($address->dni),
            'state' => $state,
            'country' => Tools::strtoupper($country),
        ];

        // Tools::dieObject($values);

        $node = $this->addNodes($node, $values);

        return $node;
    }

    public function createNode($node, $value)
    {
        if (is_integer($node)) {
            $node = 'lev_' . $node;
        }
        $nodeElem = $this->dom->createElement($node);
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $nodeElem->appendChild($this->createNode($k, $v));
            }
        } else {
            $nodeValue = $this->dom->createTextNode($value);
            $nodeElem->appendChild($nodeValue);
        }

        return $nodeElem;
    }

    public function createProductsNode()
    {
        $node = $this->dom->createElement('rows');
        $products = $this->products;

        foreach ($products as $p) {
            $row = $this->dom->createElement('row');
            $row = $this->addNodes($row, $p);
            $node->appendChild($row);
        }

        return $node;
    }

    public function createFeesNode()
    {
        $node = $this->dom->createElement('fees');
        $values = [
            'fee_tax_excl' => 0,
            'fee_tax_rate' => 0,
            'fee_tax_incl' => 0,
        ];
        $node = $this->addNodes($node, $values);

        return $node;
    }

    public function getPayment()
    {
        $payment = '';
        switch (Tools::strtolower($this->order->module)) {
            case 'setefi':
                $payment = 'CARTA DI CREDITO';

                break;
            case 'mpcash':
            case 'mpcodfee':
                $payment = 'CONTRASSEGNO CONTANTI';

                break;
            case 'mpbankwire':
            case 'bankwire':
                $payment = 'BONIFICO ANTICIPATO';

                break;
            case 'mppaypal':
            case 'paypal':
                $payment = 'PAYPAL';

                break;
            default:
                // code...
                break;
        }

        return $payment;
    }

    public function getProducts($type = 'order')
    {
        switch ($type) {
            case 'order':
                return $this->getOrderProducts();
            case 'invoice':
                return $this->getInvoiceProducts();
            case 'delivery':
                return $this->getInvoiceProducts('delivery');
        }
    }

    public function getOrderProducts()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('*')
            ->from('order_detail')
            ->where('id_order=' . (int) $this->id_order)
            ->orderBy('product_reference')
            ->orderBy('product_id')
            ->orderBy('product_attribute_id');

        $res = $db->executeS($sql);
        if ($res) {
            $prods = $this->parseProducts($res);

            return $prods;
        }
    }

    public function getInvoiceProducts($type = 'invoice')
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('*')
            ->from('order_detail')
            ->orderBy('id_order_detail');
        if ($type == 'invoice') {
            $sql->where('id_order_invoice=' . (int) $this->id_order_invoice);
        } elseif ($type == 'delivery') {
            $sql->where('id_order=' . (int) $this->id_order);
        }
        $res = $db->executeS($sql);
        if ($res) {
            return $this->parseProducts($res);
        }

        return [];
    }

    public static function getDiscountRate($fullPrice, $reductionPrice)
    {
        if ($fullPrice == 0) {
            return 0;
        }
        $reduction = (($fullPrice - $reductionPrice) / $fullPrice) * 100;
        if ($reduction < 0) {
            return 0;
        }

        return Tools::ps_round($reduction, 2, PS_ROUND_HALF_UP);
    }

    public function parseProducts($products)
    {
        $output = [];
        $this->total_products = 0;
        foreach ($products as &$p) {
            $p['name'] = $this->getProductNameOriginal($p['product_id']);
            $tax_rate = $this->getTaxRate($p['id_tax_rules_group']);
            $id_product = (int) $p['product_id'];
            $id_pa = (int) $p['product_attribute_id'];
            $qty = (int) $p['product_quantity'];
            if ($p['reduction_percent'] == 0) {
                $discount = self::getDiscountRate(
                    $p['product_price'],
                    $p['unit_price_tax_excl']
                );
            } else {
                $discount = $p['reduction_percent'];
            }

            $original_price = $p['original_product_price'];
            $unit_price = $p['unit_price_tax_excl'];
            $unit_price_tax_incl = $p['unit_price_tax_incl'];
            $total_price = $p['total_price_tax_excl'];
            $total_price_tax_incl = $p['total_price_tax_incl'];

            $isCheckedQty = $this->getCheckedQty($id_product);

            $prod = [
                'ean13' => Tools::strtoupper($p['product_ean13']),
                'reference' => Tools::strtoupper($p['product_reference']),
                'original_price_tax_excl' => $original_price,
                'product_price_tax_excl' => $original_price,
                'original_price_tax_incl' => self::addTax($original_price, $tax_rate, 2),
                'discount_percent' => $discount,
                'reduction_amount' => Tools::ps_round(0, 2, PS_ROUND_HALF_UP),
                'price_tax_excl' => $unit_price,
                'unit_price_tax_excl' => $unit_price,
                'unit_price_tax_incl' => $unit_price_tax_incl,
                'qty' => (int) $p['product_quantity'],
                'total_tax_excl' => $total_price,
                'total_price_tax_excl' => $total_price,
                'total_price_tax_incl' => $total_price_tax_incl,
                'total_tax_incl' => $total_price_tax_incl,
                'tax_rate' => Tools::ps_round($tax_rate, 2, PS_ROUND_HALF_UP),
                'product_id' => $id_product,
                'product_attribute_id' => $id_pa,
                'product_check_qty' => $isCheckedQty,
                'product_name' => Tools::strtoupper($p['name']),
                'size' => $this->getSize($id_pa),
                'color' => $this->getColor($id_pa),
                'stock_service' => $this->getStockService($p['product_id'], $p['product_attribute_id']),
                'product_in_stock' => (int) $p['product_quantity_in_stock'],
                'product_refunded' => (int) $p['product_quantity_refunded'],
                'product_returned' => (int) $p['product_quantity_return'],
                'product_reinjected' => (int) $p['product_quantity_reinjected'],
                'image_url' => $this->getImageProduct($p['product_id']),
                'customization' => $this->getCustomization($id_product, $id_pa),
                'attributes' => $this->getAttributes($id_pa),
                'product_position' => $this->getProductPosition($id_product, $id_pa),
            ];
            $output[] = $prod;
        }

        return $output;
    }

    public function getStockService($id_product, $id_product_attribute)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('count(*)')
            ->from('mpstockservice')
            ->where('id_product=' . (int) $id_product);
        // Tools::dieObject((string)$sql);
        $count = (int) $db->getValue($sql);
        if ($count) {
            $sql = new DbQuery();
            $sql->select('quantity')
                ->from('mpstockservice')
                ->where('id_product_attribute=' . (int) $id_product_attribute);

            return (int) $db->getValue($sql);
        }

        return -1;
    }

    public function getCheckedQty($id_product)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_employee')
            ->select('date_upd')
            ->select('is_checked')
            ->from('mpstockservice_check')
            ->where('id_product=' . (int) $id_product);
        $row = $db->getRow($sql);
        if ($row) {
            $employee = new Employee((int) $row['id_employee']);

            return [
                'employee' => Tools::ucWords($employee->firstname . ' ' . $employee->lastname),
                'date_checked' => Tools::displayDate($row['date_upd'], null, true),
                'is_checked' => (int) $row['is_checked'],
            ];
        } else {
            return [
                'employee' => '--',
                'date_checked' => '--',
                'is_checked' => 0,
            ];
        }
    }

    public function getProductPosition($id_product, $id_product_attribute)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('b.name as warehouse')
            ->select('c.name as shelf')
            ->select('d.name as col')
            ->select('e.name as level')
            ->from('product_shelves', 'a')
            ->InnerJoin('mpshelves_warehouse', 'b', 'a.id_warehouse=b.id_mpshelves_warehouse')
            ->InnerJoin('mpshelves_shelf', 'c', 'a.id_shelf=c.id_mpshelves_shelf')
            ->leftJoin('mpshelves_column', 'd', 'a.id_column=d.id_mpshelves_column')
            ->leftJoin('mpshelves_level', 'e', 'a.id_level=e.id_mpshelves_level')
            ->where('a.id_product=' . (int) $id_product)
            ->where('a.id_product_attribute=' . (int) $id_product_attribute);
        $res = $db->getRow($sql);
        if ($res) {
            return $res;
        } else {
            if ($id_product_attribute) {
                return $this->getProductPosition($id_product, 0);
            } else {
                return false;
            }
        }
    }

    public function getTaxRate($id_tax_rules_group)
    {
        $db = Db::getInstance();
        $tax = new DbQuery();
        $tax->select('t.rate')
            ->from('tax', 't')
            ->innerJoin('tax_rule', 'tr', 'tr.id_tax=t.id_tax')
            ->where('tr.id_tax_rules_group = ' . $id_tax_rules_group);
        $tax_rate = Tools::ps_round($db->getValue($tax), 2, PS_ROUND_HALF_UP);

        return $tax_rate;
    }

    public function getHeader()
    {
        return self::getXmlHeader();
    }

    public static function getXmlHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }

    public function getCurrentStatus()
    {
        $id_order_state = (int) $this->order->current_state;
        $os = new OrderState($id_order_state);

        return $os->name[$this->id_lang];
    }

    public static function toArray($data)
    {
        // convert xml string into an object
        $xml = simplexml_load_string($data);

        // convert into json
        $json = json_encode($xml);

        // convert into associative array
        $xmlArr = json_decode($json, true);
        // Tools::dieObject($xmlArr);
        $rows = [];
        if (!isset($xmlArr['invoice']['rows']['row'])) {
            $xmlArr['invoice']['rows']['row'] = [];
        }
        foreach ($xmlArr['invoice']['rows']['row'] as $key => $row) {
            if (is_integer($key)) {
                $rows[] = $row;
            } else {
                $rows[] = $xmlArr['invoice']['rows']['row'];

                break;
            }
        }
        $xmlArr['invoice']['rows'] = [];
        $xmlArr['invoice']['rows'] = $rows;
        $output = self::convertEmptyArray($xmlArr);

        return $output;
    }

    public static function convertEmptyArray($arr)
    {
        if (is_array($arr) && !$arr) {
            return '';
        }
        if (is_array($arr)) {
            foreach ($arr as &$val) {
                $val = self::convertEmptyArray($val);
            }
        }

        return $arr;
    }

    public function getProductNameOriginal($id_product)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('name')
        ->from('product_lang')
        ->where('id_product=' . (int) $id_product)
        ->where('id_lang=' . (int) Context::getContext()->language->id);

        return $db->getValue($sql);
    }

    public function getProductName($id_product_attribute, $id_product)
    {
        $db = Db::getInstance();
        $sql = 'select name from ' . _DB_PREFIX_ . 'product_lang '
        . 'where id_product=' . (int) $id_product . ' '
        . 'and id_lang=' . (int) Context::getContext()->language->id;

        return $db->getValue($sql);

        // OLD PROCEDURE
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $id_lang = Context::getContext()->language->id;
        $sql->select('id_attribute')
        ->from('product_attribute_combination')
        ->where('id_product_attribute = ' . (int) $id_product_attribute);
        $product = new ProductCore((int) $id_product);
        $name = $product->name[(int) $id_lang];
        $attributes = $db->executeS($sql);
        foreach ($attributes as $attribute) {
            $attr = new AttributeCore($attribute['id_attribute']);
            $name .= ' ' . $attr->name[(int) $id_lang];
        }

        return $name;
    }

    public function getSize($id_product_attribute)
    {
        // Attribute group: 13
        $attributes = $this->getAttributes($id_product_attribute);
        $out = [];
        foreach ($attributes as $row) {
            if ($row['id_attribute_group'] == 13) {
                $out[] = Tools::strtoupper($row['name']);
            }
        }

        return implode(', ', $out);
    }

    public function getColor($id_product_attribute)
    {
        // Attribute group: 14
        // Attribute group: 37
        $attributes = $this->getAttributes($id_product_attribute);
        $out = [];
        foreach ($attributes as $row) {
            if ($row['id_attribute_group'] == 14 or $row['id_attribute_group'] == 37) {
                $out[] = Tools::strtoupper($row['name']);
            }
        }

        return implode(', ', $out);
    }

    public function getImageProduct($id_product)
    {
        $shop = Context::getContext()->shop;
        $product = new Product((int) $id_product);
        /** @var array */
        $cover = Image::getCover($id_product);
        $image = new Image((int) $cover['id_image']);
        $imagePath = 'img/p/' . $image->getExistingImgPath() . '-small_default.' . $image->image_format;
        if (!file_exists(_PS_ROOT_DIR_ . '/img/p/' . $image->getExistingImgPath() . '-small_default.' . $image->image_format)) {
            $imagePath = 'img/404.gif';
        }

        $image_path = $shop->getBaseURL(true, true) . $imagePath;
        $image_path = _PS_IMG_DIR_ . 'p/' . $image->getExistingImgPath() . '-small_default.' . $image->image_format;

        return $image_path;
    }

    public function getImageProduct_($id_product)
    {
        $shop = new Shop(Context::getContext()->shop->id);
        $product = new Product((int) $id_product);
        $images = $product->getImages(Context::getContext()->language->id);

        foreach ($images as $obj_image) {
            $image = new Image((int) $obj_image['id_image']);
            if ($image->cover) {
                return $shop->getBaseURL(true) . 'img/p/' . $image->getExistingImgPath() . '.jpg';
            }
        }

        return $shop->getBaseURL(true) . 'img/p/' . $image->getExistingImgPath() . '.jpg';
    }

    public function getCustomization($id_product, $id_product_attribute)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $output = [];
        $sql->select('id_customization')
        ->from('customization')
        ->where('id_cart=' . (int) $this->order->id_cart)
        ->where('id_product=' . (int) $id_product)
        ->where('id_product_attribute=' . (int) $id_product_attribute);
        $result = $db->executeS($sql);

        if ($result) {
            $row_cust = [];
            foreach ($result as $id) {
                $sql = new DbQueryCore();
                $sql->select('*')
                ->from('customized_data')
                ->where('id_customization=' . (int) $id['id_customization']);
                $custom = $db->executeS($sql);
                if ($custom) {
                    $output = [];
                    foreach ($custom as $custom_data) {
                        $row = [
                            'id_customization' => $custom_data['id_customization'],
                            'type' => $custom_data['type'],
                            'index' => $custom_data['index'],
                            'title' => $this->getCustomizationTitle($custom_data['index']),
                            'value' => $this->getCustomizationValue($custom_data['value'], $custom_data['type']),
                        ];
                        $output[] = $row;
                    }
                }
                $row_cust[] = $output;
            }

            // die(var_dump($row_cust));

            return $row_cust;
        }

        return [];
    }

    public function getCustomizationTitle($index)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();

        $sql->select('name')
        ->from('customization_field_lang')
        ->where('id_customization_field=' . (int) $index)
        ->where('id_lang=' . (int) $this->id_lang);
        $value = $db->getValue($sql);

        return $value;
    }

    public function getCustomizationValue($value, $type)
    {
        if ($type == 0) {
            $value = $this->getImageCustomization($value);
        } else {
            $value = $value;
        }

        return $value;
    }

    public function getImageCustomization($value)
    {
        $image = _PS_ROOT_DIR_ . '/upload/' . $value;
        if (file_exists($image)) {
            return $image;
        }

        return _PS_ROOT_DIR_ . '/upload/noimage.png';
    }

    public function getAttributes($id_product_attribute)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('ag.position')
        ->select('a.id_attribute')
        ->from('attribute', 'a')
        ->innerJoin('product_attribute_combination', 'pac', 'pac.id_attribute=a.id_attribute')
        ->innerJoin('attribute_group', 'ag', 'ag.id_attribute_group=a.id_attribute_group')
        ->where('pac.id_product_attribute=' . (int) $id_product_attribute)
        ->orderBy('ag.position');
        /*
        $sql = "select id_attribute from "
        ._DB_PREFIX_.'product_attribute_combination '
        .'where id_product_attribute='.(int)$id_product_attribute;
        */
        $result = $db->executeS($sql);
        $out = [];
        if ($result) {
            foreach ($result as $row) {
                $line = $this->getAttributeLine($row['id_attribute']);
                $out[] = $line;
            }

            return $out;
        }

        return [];
    }

    public function getAttributeLine($id_attribute)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('a.id_attribute_group')
        ->select('ag.name as `group`')
        ->select('al.name')
        ->from('attribute', 'a')
        ->innerJoin('attribute_group_lang', 'ag', 'ag.id_attribute_group=a.id_attribute_group')
        ->innerJoin('attribute_lang', 'al', 'al.id_attribute=a.id_attribute')
        ->where('a.id_attribute=' . (int) $id_attribute)
        ->where('al.id_lang=' . (int) Context::getContext()->language->id)
        ->where('ag.id_lang=' . (int) Context::getContext()->language->id);
        $result = $db->getRow($sql);
        if ($result) {
            return $result;
        }

        return [
            'id_attribute_group' => 0,
            'group' => '',
            'name' => '',
        ];
    }

    public static function isNewCustomer($id_customer, $id_order)
    {
        $db = Db::getInstance();
        $sql = 'SELECT count(so.id_order) FROM `' . _DB_PREFIX_ . 'orders` so WHERE so.id_customer=' . (int) $id_customer . ' AND so.id_order<' . (int) $id_order;
        $result = (bool) $db->getValue($sql);

        return !$result;
    }

    public function getMessages()
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('n.*')
        ->select('concat(e.firstname,\' \', e.lastname) as employee')
        ->from('mp_customer_order_notes', 'n')
        ->leftJoin('employee', 'e', 'e.id_employee=n.id_employee')
        ->where('n.id_order = ' . (int) $this->order->id)
        ->where('n.deleted = 0')
        ->where('n.printable = 1')
        ->where('n.id_lang = ' . (int) $this->id_lang)
        ->where('n.id_shop = ' . (int) $this->id_shop)
        ->orderBy('n.date_add DESC');
        $result = $db->executeS($sql);
        if ($result) {
            $output = [];
            foreach ($result as $row) {
                $output[] = [
                    'date' => $row['date_add'],
                    'employee' => $row['employee'],
                    'content' => stripslashes(str_replace('\n', PHP_EOL, $row['content'])),
                ];
            }

            return $output;
        } else {
            return [];
        }
    }

    public function getPayments()
    {
        $carrier = new CarrierCore((int) $this->order->id_carrier);
        $output = [
            'payment_method' => $this->order->payment,
            'carrier' => $carrier->name,
            'current_state' => $this->getCurrentState(),
            'print_date' => date('Y-m-d H:i:s'),
        ];

        $this->smarty->assign(
            [
                'payments' => $output,
            ]
        );

        return $this->smarty->fetch(_PS_MODULE_DIR_ . $this->context->controller->module->name . '/views/templates/pdf/order_info.tpl');
    }

    /**
    * Returns the customer addresses
    *
    * @param [int] $id_address
    *
     * @return array Associative Array of address
    */
    public function getAddress($id_address)
    {
        $id_lang = (int) Context::getContext()->language->id;
        $address = new Address($id_address);
        $state = new State($address->id_state);
        $country = new Country($address->id_country);
        $output = [
            'header' => $address->company ? Tools::strtoupper($address->company) : Tools::strtoupper($address->firstname . ' ' . $address->lastname),
            'address1' => $address->address1,
            'address2' => $address->address2,
            'postcode' => $address->postcode . ' - ' . $address->city,
            'state' => Tools::strtoupper($state->name),
            'country' => Tools::strtoupper($country->name[$id_lang]),
            'phone' => $address->phone,
            'phone_mobile' => $address->phone_mobile,
            'vat_number' => Tools::strtoupper($address->vat_number),
        ];

        if (!$output['address2']) {
            unset($output['address2']);
        }
        if (!$output['phone']) {
            unset($output['phone']);
        }
        if (!$output['phone_mobile']) {
            unset($output['phone_mobile']);
        }

        return $output;
    }

    /**
    * Returns the shop address
    *
     * @return string
    */
    protected function getShopAddress()
    {
        $shop_address = '';
        $shop = Context::getContext()->shop;
        $shop_address_obj = $shop->getAddress();
        if (isset($shop_address_obj) && $shop_address_obj instanceof Address) {
            $shop_address = AddressFormat::generateAddress($shop_address_obj, [], ' - ', ' ');
        }

        return $shop_address;
    }

    public static function extractTax($price, $tax_rate = 22, $decimals = 2)
    {
        $extract_rate = Tools::ps_round(1 + ($tax_rate / 100), 2, PS_ROUND_HALF_UP);
        $value = $price / $extract_rate;

        return Tools::ps_round($value, $decimals, PS_ROUND_HALF_UP);
    }

    public static function addTax($price, $tax_rate = 22, $decimals = 2)
    {
        $amount = $price * $tax_rate / 100;

        return Tools::ps_round($price + $amount, $decimals, PS_ROUND_HALF_UP);
    }

    public static function getTaxAmount($price, $tax_rate = 22, $decimals = 2)
    {
        $amount = $price * $tax_rate / 100;

        return Tools::ps_round($amount, $decimals, PS_ROUND_HALF_UP);
    }

    public static function getTotalAmount($price, $qty = 22, $decimals = 2)
    {
        $amount = $price * $qty;

        return Tools::ps_round($amount, $decimals, PS_ROUND_HALF_UP);
    }

    public static function getPriceReducted($price, $discount, $decimals = 6)
    {
        $amount = 0;
        if ($discount == 0) {
            $amount = 0;
        } else {
            $amount = $price * abs($discount) / 100;
        }

        return Tools::ps_round($price - $amount, $decimals, PS_ROUND_HALF_UP);
    }
}
