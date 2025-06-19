<?php

namespace MpSoft\MpStockAdv\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class StockMvt
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_stock_mvt")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer", name="id_stock")
     */
    private ?int $stockId = null;

    /**
     * @ORM\Column(type="integer", name="id_order")
     */
    private ?int $orderId = null;

    /**
     * @ORM\Column(type="integer", name="id_stock_mvt_reason")
     */
    private ?int $stockMvtReasonId = null;

    /**
     * @ORM\Column(type="integer", name="id_employee")
     */
    private ?int $employeeId = null;

    /**
     * @ORM\Column(type="integer", name="id_supply_order", nullable=true)
     */
    private ?int $supplyOrderId = null;

    /**
     * @ORM\Column(type="float", name="physical_quantity")
     */
    private ?float $physicalQuantity = null;

    /**
     * @ORM\Column(type="float", name="price_te")
     */
    private ?float $priceTe = null;

    /**
     * @ORM\Column(type="datetime", name="date_add")
     */
    private ?\DateTime $dateAdd = null;

    /**
     * @ORM\Column(type="datetime", name="date_upd")
     */
    private ?\DateTime $dateUpd = null;

    public function getId(): ?int { return $this->id; }
    public function getStockId(): ?int { return $this->stockId; }
    public function setStockId($stockId): self { $this->stockId = $stockId; return $this; }
    public function getOrderId(): ?int { return $this->orderId; }
    public function setOrderId($orderId): self { $this->orderId = $orderId; return $this; }
    public function getStockMvtReasonId(): ?int { return $this->stockMvtReasonId; }
    public function setStockMvtReasonId($stockMvtReasonId): self { $this->stockMvtReasonId = $stockMvtReasonId; return $this; }
    public function getEmployeeId(): ?int { return $this->employeeId; }
    public function setEmployeeId($employeeId): self { $this->employeeId = $employeeId; return $this; }
    public function getSupplyOrderId(): ?int { return $this->supplyOrderId; }
    public function setSupplyOrderId($supplyOrderId): self { $this->supplyOrderId = $supplyOrderId; return $this; }
    public function getPhysicalQuantity(): ?float { return $this->physicalQuantity; }
    public function setPhysicalQuantity($physicalQuantity): self { $this->physicalQuantity = $physicalQuantity; return $this; }
    public function getPriceTe(): ?float { return $this->priceTe; }
    public function setPriceTe($priceTe): self { $this->priceTe = $priceTe; return $this; }
    public function getDateAdd(): ?\DateTime { return $this->dateAdd; }
    public function setDateAdd($dateAdd): self { if (is_string($dateAdd)) { $dateAdd = new \DateTime($dateAdd); } $this->dateAdd = $dateAdd; return $this; }
    public function getDateUpd(): ?\DateTime { return $this->dateUpd; }
    public function setDateUpd($dateUpd): self { if (is_string($dateUpd)) { $dateUpd = new \DateTime($dateUpd); } $this->dateUpd = $dateUpd; return $this; }

    public function toArray(): array
    {
        return [
            'id_stock_mvt' => $this->getId(),
            'id_stock' => $this->getStockId(),
            'id_order' => $this->getOrderId(),
            'id_stock_mvt_reason' => $this->getStockMvtReasonId(),
            'id_employee' => $this->getEmployeeId(),
            'id_supply_order' => $this->getSupplyOrderId(),
            'physical_quantity' => $this->getPhysicalQuantity(),
            'price_te' => $this->getPriceTe(),
            'date_add' => $this->getDateAdd()?->format('Y-m-d H:i:s'),
            'date_upd' => $this->getDateUpd()?->format('Y-m-d H:i:s'),
        ];
    }

    public static function getSqlCreateStatement()
    {
        $prefix = _DB_PREFIX_;
        return "CREATE TABLE IF NOT EXISTS `{$prefix}stock_mvt` (
            `id_stock_mvt` int(11) NOT NULL AUTO_INCREMENT,
            `id_stock` int(11) NOT NULL,
            `id_order` int(11) DEFAULT NULL,
            `id_stock_mvt_reason` int(11) NOT NULL,
            `id_employee` int(11) NOT NULL,
            `id_supply_order` int(11) DEFAULT NULL,
            `physical_quantity` float NOT NULL,
            `price_te` float NOT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_stock_mvt`),
            KEY `id_stock` (`id_stock`),
            KEY `id_order` (`id_order`),
            KEY `id_stock_mvt_reason` (`id_stock_mvt_reason`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
