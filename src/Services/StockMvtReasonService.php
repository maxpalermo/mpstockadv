<?php

namespace MpSoft\MpStockAdv\Services;

class StockMvtReasonService
{
    /** @var \Doctrine\DBAL\Connection */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Restituisce l'elenco dei motivi di movimento magazzino (stock_mvt_reason)
     * @param int $idLang
     * @return array
     */
    public function getMvtReasons($idLang = 1)
    {
        $prefix = _DB_PREFIX_;
        $sql = "
            SELECT r.id_stock_mvt_reason AS id, rl.name, r.sign
            FROM {$prefix}stock_mvt_reason r
            INNER JOIN {$prefix}stock_mvt_reason_lang rl ON (rl.id_stock_mvt_reason = r.id_stock_mvt_reason AND rl.id_lang = :id_lang)
            WHERE r.deleted = 0
            ORDER BY rl.name ASC
        ";
        $results = $this->conn->fetchAllAssociative($sql, ['id_lang' => $idLang]);
        return $results;
    }
}
