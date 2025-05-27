<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockSupplyOrderController extends FrameworkBundleAdminController
{
    /**
     * @Route("/mpstockadv/supply-orders", name="mpstockadv_supply_orders")
     */
    public function index(Request $request)
    {
        // Parametri di ricerca e paginazione
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $search = $request->query->get('q', '');
        $result = \MpSoft\MpStockAdv\Models\ModelSupplyOrder::getPaginated($page, $limit, $search);
        $suppliers = \MpSoft\MpStockAdv\Models\ModelSupplier::getAll();

        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/supply_orders.html.twig', [
            'orders' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * @Route("/mpstockadv/supply-orders/edit/{id}", name="mpstockadv_supply_orders_edit")
     */
    public function edit(Request $request, $id)
    {
        // Logica per editare un documento di carico
        return new Response('Edit form');
    }

    /**
     * @Route("/mpstockadv/supply-orders/receive/{id}", name="mpstockadv_supply_orders_receive")
     */
    public function receive(Request $request, $id)
    {
        if ($request->isMethod('POST')) {
            $received = $request->request->all('received');
            $id_employee = $this->getUser() ? $this->getUser()->getId() : 1;
            $errors = [];
            foreach ($received as $id_detail => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) {
                    continue;
                }
                // Aggiorna supply_order_detail
                \Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'supply_order_detail SET quantity_received = quantity_received + '.(float) $qty.' WHERE id_supply_order_detail = '.(int) $id_detail);
                // Inserisci in supply_order_receipt_history
                \MpSoft\MpStockAdv\Models\ModelSupplyOrderReceipt::addReceipt($id_detail, $qty, $id_employee);
                // Recupera dati dettaglio
                $detail = \Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'supply_order_detail WHERE id_supply_order_detail = '.(int) $id_detail);
                // Aggiorna stock_available
                \Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'stock_available SET quantity = quantity + '.(float) $qty.' WHERE id_product = '.(int) $detail['id_product'].' AND id_product_attribute = '.(int) $detail['id_product_attribute']);
                // Inserisci movimento in stock_mvt
                \Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'stock_mvt (id_stock, id_order, id_supply_order, id_stock_mvt_reason, id_employee, id_product, id_product_attribute, id_warehouse, sign, quantity, date_add) VALUES ('
                    .(int) $detail['id_stock'].', NULL, '.(int) $detail['id_supply_order'].', 1, '.(int) $id_employee.', '.(int) $detail['id_product'].', '.(int) $detail['id_product_attribute'].', '.(int) $detail['id_warehouse'].', 1, '.(float) $qty.', NOW())');
            }

            return $this->json(['success' => true, 'message' => 'Ricezione registrata']);
        }

        // Visualizza dettagli ordine e form ricezione
        $order = \MpSoft\MpStockAdv\Models\ModelSupplyOrder::getById($id);
        $details = \MpSoft\MpStockAdv\Models\ModelSupplyOrderDetail::getBySupplyOrderId($id);

        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/supply_order_receive.html.twig', [
            'order' => $order,
            'details' => $details,
        ]);
    }

    /**
     * @Route("/mpstockadv/supply-orders/create", name="mpstockadv_supply_orders_create", methods={"POST"})
     */
    public function create(Request $request)
    {
        $data = $request->request->all();
        $products = $data['products'] ?? [];
        if (empty($products)) {
            return $this->json(['success' => false, 'message' => 'Nessun prodotto inserito']);
        }
        // Inserisci supply_order
        $fields = [
            'supplier_name' => pSQL($data['supplier_name'] ?? ''),
            'date_add' => pSQL($data['date_add'] ?? date('Y-m-d')),
            'reference' => pSQL($data['reference'] ?? ''),
            'note' => pSQL($data['note'] ?? ''),
            'id_supply_order_state' => 1,
        ];
        $cols = implode(',', array_keys($fields));
        $vals = "'".implode("','", array_values($fields))."'";
        \Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_."supply_order ($cols) VALUES ($vals)");
        $id_supply_order = \Db::getInstance()->Insert_ID();
        // Inserisci dettagli prodotti
        foreach ($products as $prod) {
            \Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'supply_order_detail (id_supply_order, name, reference, quantity_expected, quantity_received) VALUES ('
                .(int) $id_supply_order.", '"
                .pSQL($prod['name'])."', '"
                .pSQL($prod['reference'])."', "
                .(int) $prod['quantity_expected'].', 0)'
            );
        }

        return $this->json(['success' => true, 'message' => 'Documento creato']);
    }

    /**
     * @Route("/mpstockadv/supply-orders/product-search", name="mpstockadv_supply_orders_product_search", methods={"GET"})
     */
    public function productSearch(Request $request)
    {
        $q = $request->query->get('q', '');
        if (mb_strlen($q) < 3) {
            return $this->json(['results' => []]);
        }
        $results = \MpSoft\MpStockAdv\Models\ModelProductSearch::search($q);
        $items = [];
        foreach ($results as $row) {
            $label = $row['product_name'];
            if ($row['id_product_attribute']) {
                $label .= ' — '.$row['attribute_names'];
            }
            $label .= ' [Rif: '.($row['attr_reference'] ?: $row['reference']).']';
            if ($row['ean13'] || $row['attr_ean13']) {
                $label .= ' EAN: '.($row['attr_ean13'] ?: $row['ean13']);
            }
            if ($row['upc'] || $row['attr_upc']) {
                $label .= ' UPC: '.($row['attr_upc'] ?: $row['upc']);
            }
            if ($row['isbn'] || $row['attr_isbn']) {
                $label .= ' ISBN: '.($row['attr_isbn'] ?: $row['isbn']);
            }
            // Recupera l'immagine di default
            $image_url = null;
            $id_product = (int) $row['id_product'];
            $id_product_attribute = (int) $row['id_product_attribute'];

            // Costruisci URL immagine (usa link di PrestaShop)
            $cover = \Product::getCover($id_product);
            if ($cover) {
                $path = \Image::getImgFolderStatic($cover['id_image']);
                $image_url = _PS_BASE_URL_.__PS_BASE_URI__.'img/p/'.$path.'/'.$cover['id_image'].'-small_default.jpg';
            } else {
                $image_url = _PS_BASE_URL_.__PS_BASE_URI__.'img/404.gif';
            }

            $items[] = [
                'id' => json_encode([
                    'img_url' => $image_url,
                    'id_product' => $row['id_product'],
                    'id_product_attribute' => $row['id_product_attribute'],
                    'name' => $row['product_name'],
                    'attribute_names' => $row['attribute_names'],
                    'reference' => $row['attr_reference'] ?: $row['reference'],
                    'ean13' => $row['attr_ean13'] ?: $row['ean13'],
                    'upc' => $row['attr_upc'] ?: $row['upc'],
                    'isbn' => $row['attr_isbn'] ?: $row['isbn'],
                ]),
                'text' => $label,
                'img' => $image_url,
                'name' => $row['product_name'],
                'combination' => $row['attribute_names'],
            ];
        }

        return $this->json(['results' => $items]);
    }
}
