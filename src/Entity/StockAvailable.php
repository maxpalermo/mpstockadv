<?php

namespace MpSoft\MpStockAdv\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class StockAvailable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_stock_available")
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
     * @ORM\Column(type="integer", name="id_shop")
     */
    private ?int $shopId = null;

    /**
     * @ORM\Column(type="integer", name="id_shop_group")
     */
    private ?int $shopGroupId = null;

    /**
     * @ORM\Column(type="float", name="quantity")
     */
    private ?float $quantity = null;

    /**
     * @ORM\Column(type="integer", name="depends_on_stock")
     */
    private ?int $dependsOnStock = null;

    /**
     * @ORM\Column(type="integer", name="out_of_stock")
     */
    private ?int $outOfStock = null;

    public function getId(): ?int { return $this->id; }
    public function getProductId(): ?int { return $this->productId; }
    public function setProductId($id): self { $this->productId = $id; return $this; }
    public function getProductAttributeId(): ?int { return $this->productAttributeId; }
    public function setProductAttributeId($id): self { $this->productAttributeId = $id; return $this; }
    public function getShopId(): ?int { return $this->shopId; }
    public function setShopId($id): self { $this->shopId = $id; return $this; }
    public function getShopGroupId(): ?int { return $this->shopGroupId; }
    public function setShopGroupId($id): self { $this->shopGroupId = $id; return $this; }
    public function getQuantity(): ?float { return $this->quantity; }
    public function setQuantity($quantity): self { $this->quantity = $quantity; return $this; }
    public function getDependsOnStock(): ?int { return $this->dependsOnStock; }
    public function setDependsOnStock($val): self { $this->dependsOnStock = $val; return $this; }
    public function getOutOfStock(): ?int { return $this->outOfStock; }
    public function setOutOfStock($val): self { $this->outOfStock = $val; return $this; }

    public function toArray(): array
    {
        return [
            'id_stock_available' => $this->getId(),
            'id_product' => $this->getProductId(),
            'id_product_attribute' => $this->getProductAttributeId(),
            'id_shop' => $this->getShopId(),
            'id_shop_group' => $this->getShopGroupId(),
            'quantity' => $this->getQuantity(),
            'depends_on_stock' => $this->getDependsOnStock(),
            'out_of_stock' => $this->getOutOfStock(),
        ];
    }

    public static function getSqlCreateStatement()
    {
        $prefix = _DB_PREFIX_;
        return "CREATE TABLE IF NOT EXISTS `{$prefix}stock_available` (
            `id_stock_available` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `id_product_attribute` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `id_shop_group` int(11) NOT NULL,
            `quantity` float NOT NULL,
            `depends_on_stock` tinyint(1) NOT NULL DEFAULT 0,
            `out_of_stock` tinyint(1) NOT NULL DEFAULT 2,
            PRIMARY KEY (`id_stock_available`),
            KEY `id_product` (`id_product`),
            KEY `id_product_attribute` (`id_product_attribute`),
            KEY `id_shop` (`id_shop`),
            KEY `id_shop_group` (`id_shop_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
