<?php

namespace MpSoft\MpStockAdv\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class StockMvtReason
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_stock_mvt_reason")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255, name="name")
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="integer", name="sign")
     */
    private ?int $sign = null;

    /**
     * @ORM\Column(type="datetime", name="date_add")
     */
    private ?\DateTime $dateAdd = null;

    /**
     * @ORM\Column(type="datetime", name="date_upd")
     */
    private ?\DateTime $dateUpd = null;

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName($name): self { $this->name = $name; return $this; }
    public function getSign(): ?int { return $this->sign; }
    public function setSign($sign): self { $this->sign = $sign; return $this; }
    public function getDateAdd(): ?\DateTime { return $this->dateAdd; }
    public function setDateAdd($dateAdd): self { if (is_string($dateAdd)) { $dateAdd = new \DateTime($dateAdd); } $this->dateAdd = $dateAdd; return $this; }
    public function getDateUpd(): ?\DateTime { return $this->dateUpd; }
    public function setDateUpd($dateUpd): self { if (is_string($dateUpd)) { $dateUpd = new \DateTime($dateUpd); } $this->dateUpd = $dateUpd; return $this; }

    public function toArray(): array
    {
        return [
            'id_stock_mvt_reason' => $this->getId(),
            'name' => $this->getName(),
            'sign' => $this->getSign(),
            'date_add' => $this->getDateAdd()?->format('Y-m-d H:i:s'),
            'date_upd' => $this->getDateUpd()?->format('Y-m-d H:i:s'),
        ];
    }

    public static function getSqlCreateStatement()
    {
        $prefix = _DB_PREFIX_;
        return "CREATE TABLE IF NOT EXISTS `{$prefix}stock_mvt_reason` (
            `id_stock_mvt_reason` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `sign` int(11) NOT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_stock_mvt_reason`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
