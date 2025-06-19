<?php

namespace MpSoft\MpStockAdv\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_stock")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer", name="id_product")
     */
    private ?int $productId = null;

    /**
     * @ORM\Column(type="integer", name="id_product_attribute")
     */
    private ?int $productAttributeId = null;

    /**
     * @ORM\Column(type="integer", name="id_warehouse")
     */
    private ?int $warehouseId = null;

    /**
     * @ORM\Column(type="integer", name="id_currency")
     */
    private ?int $currencyId = null;

    /**
     * @ORM\Column(type="float", name="physical_quantity")
     */
    private ?float $physicalQuantity = null;

    /**
     * @ORM\Column(type="float", name="usable_quantity")
     */
    private ?float $usableQuantity = null;

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
    public function getProductId(): ?int { return $this->productId; }
    public function setProductId($id): self { $this->productId = $id; return $this; }
    public function getProductAttributeId(): ?int { return $this->productAttributeId; }
    public function setProductAttributeId($id): self { $this->productAttributeId = $id; return $this; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function setWarehouseId($id): self { $this->warehouseId = $id; return $this; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function setCurrencyId($id): self { $this->currencyId = $id; return $this; }
    public function getPhysicalQuantity(): ?float { return $this->physicalQuantity; }
    public function setPhysicalQuantity($val): self { $this->physicalQuantity = $val; return $this; }
    public function getUsableQuantity(): ?float { return $this->usableQuantity; }
    public function setUsableQuantity($val): self { $this->usableQuantity = $val; return $this; }
    public function getPriceTe(): ?float { return $this->priceTe; }
    public function setPriceTe($val): self { $this->priceTe = $val; return $this; }
    public function getDateAdd(): ?\DateTime { return $this->dateAdd; }
    public function setDateAdd($dateAdd): self { if (is_string($dateAdd)) { $dateAdd = new \DateTime($dateAdd); } $this->dateAdd = $dateAdd; return $this; }
    public function getDateUpd(): ?\DateTime { return $this->dateUpd; }
    public function setDateUpd($dateUpd): self { if (is_string($dateUpd)) { $dateUpd = new \DateTime($dateUpd); } $this->dateUpd = $dateUpd; return $this; }

    public function toArray(): array
    {
        return [
            'id_stock' => $this->getId(),
            'id_product' => $this->getProductId(),
            'id_product_attribute' => $this->getProductAttributeId(),
            'id_warehouse' => $this->getWarehouseId(),
            'id_currency' => $this->getCurrencyId(),
            'physical_quantity' => $this->getPhysicalQuantity(),
            'usable_quantity' => $this->getUsableQuantity(),
            'price_te' => $this->getPriceTe(),
            'date_add' => $this->getDateAdd()?->format('Y-m-d H:i:s'),
            'date_upd' => $this->getDateUpd()?->format('Y-m-d H:i:s'),
        ];
    }

    public static function getSqlCreateStatement()
    {
        $prefix = _DB_PREFIX_;
        return "CREATE TABLE IF NOT EXISTS `{$prefix}stock` (
            `id_stock` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `id_product_attribute` int(11) NOT NULL,
            `id_warehouse` int(11) NOT NULL,
            `id_currency` int(11) NOT NULL,
            `physical_quantity` float NOT NULL,
            `usable_quantity` float NOT NULL,
            `price_te` float NOT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_stock`),
            KEY `id_product` (`id_product`),
            KEY `id_product_attribute` (`id_product_attribute`),
            KEY `id_warehouse` (`id_warehouse`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
