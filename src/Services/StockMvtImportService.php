<?php

namespace MpSoft\MpStockAdv\Services;

use Doctrine\ORM\EntityManagerInterface;

class StockMvtImportService
{
    private EntityManagerInterface $entityManager;
    private StockProductsService $stockProductService;

    public function __construct(EntityManagerInterface $entityManager, StockProductsService $stockProductService)
    {
        $this->entityManager = $entityManager;
        $this->stockProductService = $stockProductService;
        $this->id_lang = 1;
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
        $docNumber = $matches['doc_number'];
        $docDate = $matches['doc_date']; // formato yyyymmdd
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
        if (isset($xml->rows->row)) {
            foreach ($xml->rows->row as $row) {
                $rows[] = [
                    'img' => $this->getProductImageUrl($row->ean13),
                    'supplier' => $this->getSupplierProductName($row->ean13),
                    'ean13' => (string) $row->ean13,
                    'reference' => (string) $row->reference,
                    'product' => (string) $this->getProductName($row->ean13),
                    'combination' => (string) $this->getProductCombination($row->ean13),
                    'qty' => (int) $row->qty,
                    'price' => (float) $row->price,
                    'wholesale_price' => (float) $row->wholesale_price,
                    'warehouse' => $this->getWarehouse(),
                ];
            }
        }

        // 3. Recupero segno movimento da stock_mvt_reason
        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT sign FROM '._DB_PREFIX_.'stock_mvt_reason WHERE alias = :alias LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('alias', $movementTypeAlias);
        $result = $stmt->executeQuery();
        $sign = (int) $result->fetchOne();
        if (false === $sign) {
            return null;
        }
        $sign = (int) $sign;

        // 4. Calcolo quantità finale per ogni riga
        foreach ($rows as &$row) {
            $row['qty_signed'] = $row['qty'] * $sign;
        }
        unset($row);

        // 5. Output strutturato
        return [
            'file' => [
                'type' => $movementDirection,
                'doc_number' => $docNumber,
                'doc_date' => $docDate,
                'doc_time' => $docTime,
            ],
            'movement' => [
                'type_alias' => $movementTypeAlias,
                'date' => $movementDate,
                'sign' => $sign,
            ],
            'rows' => $rows,
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
            $img_path = $this->stockProductService->getProductImageUrl($idList['id_product']);

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

            return implode(' ', array_map(fn ($attr) => $attr['attribute_name'], $comb));
        }

        return '--';
    }
}