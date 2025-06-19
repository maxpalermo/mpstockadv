<?php

namespace MpSoft\MpStockAdv\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class StockMvtReasonLang
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_stock_mvt_reason")
     */
    private ?int $stockMvtReasonId = null;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_lang")
     */
    private ?int $langId = null;

    /**
     * @ORM\Column(type="string", length=255, name="name")
     */
    private ?string $name = null;

    public function getStockMvtReasonId(): ?int { return $this->stockMvtReasonId; }
    public function setStockMvtReasonId($id): self { $this->stockMvtReasonId = $id; return $this; }
    public function getLangId(): ?int { return $this->langId; }
    public function setLangId($id): self { $this->langId = $id; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName($name): self { $this->name = $name; return $this; }

    public function toArray(): array
    {
        return [
            'id_stock_mvt_reason' => $this->getStockMvtReasonId(),
            'id_lang' => $this->getLangId(),
            'name' => $this->getName(),
        ];
    }

    public static function getSqlCreateStatement()
    {
        $prefix = _DB_PREFIX_;
        return "CREATE TABLE IF NOT EXISTS `{$prefix}stock_mvt_reason_lang` (
            `id_stock_mvt_reason` int(11) NOT NULL,
            `id_lang` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            PRIMARY KEY (`id_stock_mvt_reason`, `id_lang`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
