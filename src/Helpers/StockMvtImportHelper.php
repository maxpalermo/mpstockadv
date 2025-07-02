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

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use MpSoft\MpStockAdv\Helpers\ConfigurationHelper;
use MpSoft\MpStockAdv\Services\StockProductsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class StockMvtImportHelper extends DependencyHelper
{
    const SUPPLIER_ORDER_STATE_RECEIVED = 5;
    private StockProductsService $stockProductsService;
    private $configurationHelper;

    public function __construct()
    {
        parent::__construct();
        $this->stockProductsService = $this->container->get("MpSoft\MpStockAdv\Services\StockProductsService");
    }

    protected function formatDateIso($value)
    {
        //Trasformo la data da YYYYMMDD a mm/dd/yyyy
        $date = substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
        return $date;
    }

    protected function formatDate($date): string
    {
        // Crea un oggetto DateTime interpretando correttamente il formato
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);

        if ($dateTime) {
            $dateFormatted = $dateTime->format('d/m/Y'); // Converti in dd/mm/yyyy
        } else {
            $dateFormatted = date('d/m/Y');
        }

        return $dateFormatted;
    }

    protected function getSupplierByEan13($ean13)
    {
        $pfx = _DB_PREFIX_;
        $table = $pfx . 'product_attribute';
        $query = "
            SELECT
                id_product
            FROM
                {$table}
            WHERE
                ean13 = '{$ean13}'
            ORDER BY
                id_product DESC
        ";

        try {
            $id_product = (int) \Db::getInstance()->getValue($query);
            return $this->getSupplierByIdProduct($id_product);
        } catch (\Throwable $th) {
            return [
                'supplier_id' => 0,
                'supplier_name' => '',
                'error' => $th->getMessage(),
            ];
        }
    }

    protected function getSupplierByIdProduct($id_product)
    {
        $pfx = _DB_PREFIX_;
        $table_supplier = $pfx . 'supplier';
        $table_product = $pfx . 'product';
        $query = "
            SELECT
                s.id_supplier,
                s.name
            FROM
                {$table_supplier} s
            INNER JOIN
                {$table_product} p ON (p.id_supplier = s.id_supplier)
            WHERE
                p.id_product = {$id_product}
        ";

        try {
            $result = \Db::getInstance()->getRow($query);
        } catch (\Throwable $th) {
            return [
                'supplier_id' => 0,
                'supplier_name' => '',
                'error' => $th->getMessage(),
            ];
        }

        return [
            'supplier_id' => (int) $result['id_supplier'],
            'supplier_name' => (string) $result['name'],
        ];

    }

    /**
     * Analizza un file XML di movimento stock e restituisce un array strutturato per l'importazione.
     *
     * @param string $fileName Nome del file (es: C(CARIC CORR-20221007)161902.xml)
     * @param string $filePath Percorso completo al file XML
     *
     * @return array|null Array dei dati per l'importazione oppure null in caso di errore
     */
    public function parseStockMovementFile(string $fileName, string $filePath): ?array
    {
        // 1. Parsing del nome file
        $pattern = '/^(?P<type>[CS])\((?P<doc_number>[^-]+)-(?P<doc_date>\d{8})\)(?P<time>\d{6})/';
        if (!preg_match($pattern, $fileName, $matches)) {
            return null;
        }
        $movementDirection = 'C' === $matches['type'] ? 'carico' : 'scarico';
        $sign = 'C' === $matches['type'] ? 1 : -1;
        $docNumber = $matches['doc_number'];
        $docDateIso = $this->formatDateIso($matches['doc_date']);
        $docDate = $this->formatDate($docDateIso);
        $docTime = $matches['time'];     // formato hhmmss

        // 2. Parsing XML
        if (!file_exists($filePath)) {
            return null;
        }
        try {
            $xml = new \SimpleXMLElement(file_get_contents($filePath));
        } catch (\Exception $e) {
            return null;
        }
        $movementTypeAlias = (string) $xml->movement_type;
        $movementDate = (string) $xml->movement_date;
        $rows = [];

        // 3. Recupero segno movimento da stock_mvt_reason
        $conn = $this->entityManager->getConnection();
        $table = _DB_PREFIX_ . 'stock_mvt_reason';
        $table_lang = $table . '_lang';
        $sql = "
            SELECT
                m.id_stock_mvt_reason,
                m.sign,
                ml.name,
                m.alias
            FROM
                {$table} m
            INNER JOIN
                {$table_lang} ml ON (m.id_stock_mvt_reason=ml.id_Stock_mvt_reason and ml.id_lang = :id_lang)
            WHERE
                m.alias = :alias
            LIMIT 1
            ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('id_lang', (int) $this->id_lang, ParameterType::INTEGER);
        $stmt->bindValue('alias', (int) $movementTypeAlias, ParameterType::INTEGER);
        $result = $stmt->executeQuery();
        $rowMvtReason = $result->fetchAssociative();
        if (false === $rowMvtReason) {
            return null;
        }
        $sign = $rowMvtReason['sign'];
        $movementDirection = -1 === $sign ? 'scarico' : 'carico';

        if (isset($xml->rows->row)) {
            $xml_rows = reset($xml->rows);
            $firstRow = $xml_rows[0];
            $supplier_data = $this->getSupplierByEan13($firstRow->ean13);
            foreach ($xml->rows->row as $row) {
                $rows[] = [
                    'img' => $this->getProductImageUrl($row->ean13),
                    'supplier_id' => $supplier_data['supplier_id'],
                    'supplier_name' => $supplier_data['supplier_name'],
                    'ean13' => (string) $row->ean13,
                    'reference' => (string) $row->reference,
                    'product' => (string) $this->getProductName($row->ean13),
                    'combination' => (string) $this->getProductCombination($row->ean13),
                    'qty' => (int) $row->qty,
                    'qty_signed' => (int) $row->qty * (int) $sign,
                    'price' => (float) $row->price,
                    'wholesale_price' => (float) $row->wholesale_price,
                    'warehouse' => $this->getWarehouse(),
                ];
            }
        }

        // 5. Output strutturato
        return [
            'file' => [
                'type' => $movementDirection,
                'doc_number' => $docNumber,
                'doc_date' => $docDate,
                'doc_date_iso' => $docDateIso,
                'doc_time' => $docTime,
                'supplier_id' => $supplier_data['supplier_id'],
                'supplier_name' => $supplier_data['supplier_name'],
                'movement_id' => $rowMvtReason['id_stock_mvt_reason'],
                'movement_alias' => $rowMvtReason['alias'],
                'movement_name' => $rowMvtReason['name'],
                'movement_sign' => $rowMvtReason['sign'],
            ],
            'movement' => [
                'id' => $rowMvtReason['id_stock_mvt_reason'],
                'name' => $rowMvtReason['name'],
                'type_alias' => $rowMvtReason['alias'],
                'date' => $movementDate,
                'sign' => $rowMvtReason['sign'],
            ],
            'rows' => $rows,
        ];
    }

    public function importXmlAction(Request $request)
    {
        try {
            $document = json_decode($request->get('document'), true, 512, JSON_THROW_ON_ERROR);
            $rows = json_decode($request->get('rows'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'importazione.',
            ]);
        }

        if (!$document) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Nessun documento fornito',
                ]
            );
        }

        if (!$rows) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Nessun riga fornita',
                ]
            );
        }

        $employee = $this->context->getContext()->employee;
        $supplier_id = (int) $document['document_supplier_id'];
        $supplier = new \Supplier($supplier_id, $this->id_lang);
        $document_number = $document['document_number'];
        $document_date = $document['document_date_iso'];
        $stockManagerHelper = new StockManagerHelper();

        //Creo il documento in supply_order
        $supply_order = new \SupplyOrder();
        $data = [
            'id_supplier' => $supplier->id,
            'supplier_name' => $supplier->name,
            'id_lang' => $this->id_lang,
            'id_warehouse' => $this->id_warehouse,
            'id_supply_order_state' => self::SUPPLIER_ORDER_STATE_RECEIVED,
            'id_currency' => $this->id_currency,
            'id_ref_currency' => 0,
            'reference' => $document_number,
            'date_add' => $document_date,
            'date_upd' => date('Y-m-d H:i:s'),
            'date_delivery_expected' => date('Y-m-d H:i:s'),
        ];
        $supply_order->hydrate($data);
        try {
            $result = $supply_order->add();
        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'message' => "Errore durante il salvataggio del documento: \n {$th->getMessage()}"
            ]);
        }

        if ($result) {
            $id_supply_order = $supply_order->id;
        }


        foreach ($rows as $row) {
            $ean13 = $row['ean13'];
            $price = $row['price'];
            $quantity = $row['qty_signed'];

            $product_ids = $this->getProductIdByEan13($ean13);
            $id_product = $product_ids['id_product'] ?? 0;
            $id_product_attribute = $product_ids['id_product_attribute'] ?? 0;

            $product = new \Product($id_product, false, $this->id_lang);
            if (!\Validate::isLoadedObject($product)) {
                continue;
            }

            /*
            $movement = [
                'id_warehouse' => $id_warehouse,
                'id_order' => $id_order,
                'id_supply_order' => $id_supply_order,
                'id_currency' => $this->id_currency,
                'id_stock_mvt_reason' => $id_stock_mvt_reason,
                'sign' => $sign,
                'id_employee => $employee->id,
                'employee_lastname' => $employee->lastname,
                'employee_firstname' => $employee_firstname,
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'reference' => $product->reference,
                'supplier_reference' => $product->supplier_reference,
                'name' => $product->name,
                'ean13' => $product->ean13,
                'isbn' => $product->isbn,
                'upc' => $product->upc,
                'mpn' => $product->mpn,
                'exchange_rate' => 1,
                'quantity_expected' => $quantity,
                'quantity_received' => $quantity,
                'price_te' => $price,
            ];
            */

            $data = [
                'id_warehouse' => (new ConfigurationHelper())->getDefaultWarehouse(),
                'id_order' => '0',
                'id_supply_order' => $id_supply_order,
                'id_currency' => $this->id_currency,
                'id_stock_mvt_reason' => $document['document_movement_id'],
                'sign' => $document['document_movement_sign'],
                'id_employee' => $employee->id,
                'employee_lastname' => $employee->lastname,
                'employee_firstname' => $employee->firstname,
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'reference' => $product->reference,
                'supplier_reference' => $product->supplier_reference,
                'name' => $product->name,
                'ean13' => $product->ean13,
                'isbn' => $product->isbn,
                'upc' => $product->upc,
                'mpn' => $product->mpn,
                'exchange_rate' => 1,
                'quantity_expected' => $quantity,
                'quantity_received' => $quantity,
                'price_te' => $price,
            ];

            $stockManagerHelper->addMovement($data);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Importazione completata con successo',
        ]);
    }

    protected function getSupplier($id_product)
    {
        $product = new \Product($id_product, false, $this->id_lang);
        if (!\Validate::isLoadedObject($product)) {
            return false;
        }

        $id_supplier = (int) $product->id_supplier;
        $supplier = new \Supplier($id_supplier, $this->id_lang);
        if (!\Validate::isLoadedObject($supplier)) {
            return false;
        }

        return $supplier;
    }

    protected function getProductIdByEan13($ean13)
    {
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                `id_product`,
                `id_product_attribute`
            FROM
                {$pfx}product_attribute
            WHERE
                `ean13` = '{$ean13}'
        ";

        $db = \Db::getInstance();
        $row = $db->getRow($query);
        if (!$row) {
            return false;
        }

        return [
            'id_product' => (int) $row['id_product'],
            'id_product_attribute' => (int) $row['id_product_attribute'],
        ];

    }

    protected function getProductId($ean13)
    {
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                id_product, id_product_attribute
            FROM
                {$pfx}product_attribute
            WHERE
                ean13 = '{$ean13}' 
        ";
        $db = \Db::getInstance();
        $row = $db->getRow($query);
        if ($row) {
            return [
                'id_product' => (int) $row['id_product'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
            ];
        }

        return false;
    }

    protected function getProductImageUrl($ean13)
    {
        $idList = $this->getProductId($ean13);
        if ($idList) {
            $img_path = $this->stockProductsService->getProductImageUrl($idList['id_product']);

            return $img_path;
        }

        return '/img/404.gif';
    }

    protected function getSupplierProductName($ean13)
    {
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                s.name
            FROM 
                {$pfx}supplier s
            INNER JOIN 
                {$pfx}product p
                ON (p.id_supplier=s.id_supplier)
        ";

        $db = \Db::getInstance();
        $supplierName = $db->getValue($query);
        if (!$supplierName) {
            return '--';
        }

        return $supplierName;
    }

    protected function getWareHouse()
    {
        return '--';
    }

    protected function getProductName($ean13)
    {
        $id_list = $this->getProductId($ean13);
        if ($id_list) {
            $product = new \Product($id_list['id_product'], false, $this->id_lang);

            return $product->name;
        }

        return '--';
    }

    protected function getProductCombination($ean13)
    {
        $id_list = $this->getProductId($ean13);
        if ($id_list) {
            $product = new \Product($id_list['id_product'], false, $this->id_lang);

            $comb = $product->getAttributeCombinationsById($id_list['id_product_attribute'], $this->id_lang);

            return implode(' ', array_map(fn($attr) => $attr['attribute_name'], $comb));
        }

        return '--';
    }
}