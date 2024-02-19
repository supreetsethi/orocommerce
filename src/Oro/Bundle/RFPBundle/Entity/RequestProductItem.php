<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemChecksumAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * RFP Request Product Item entity.
 *
 * @ORM\Table(name="oro_rfp_request_prod_item")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="quotes"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class RequestProductItem implements
    ProductLineItemInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var RequestProduct
     *
     * @ORM\ManyToOne(targetEntity="RequestProduct", inversedBy="requestProductItems")
     * @ORM\JoinColumn(name="request_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $requestProduct;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", nullable=true)
     */
    protected $quantity;

    /**
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="product_unit_id", referencedColumnName="code", onDelete="SET NULL")
     */
    protected $productUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="product_unit_code", type="string", length=255)
     */
    protected $productUnitCode;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    protected $currency;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var Collection<RequestProductKitItemLineItem>
     */
    protected $kitItemLineItems;

    /**
     * Differentiates the unique constraint allowing to add the same product with the same unit code multiple times,
     * moving the logic of distinguishing of such line items out of the entity class.
     *
     * @ORM\Column(name="checksum", type="string", length=40, options={"default"=""}, nullable=false)
     */
    protected string $checksum = '';

    public function __construct()
    {
        $this->kitItemLineItems = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return $this->getRequestProduct();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quantity
     *
     * @param float $quantity
     * @return RequestProductItem
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set requestProduct
     *
     * @param RequestProduct|null $requestProduct
     * @return RequestProductItem
     */
    public function setRequestProduct(RequestProduct $requestProduct = null)
    {
        $this->requestProduct = $requestProduct;
        $this->loadKitItemLineItems();

        return $this;
    }

    /**
     * Get requestProduct
     *
     * @return RequestProduct
     */
    public function getRequestProduct()
    {
        return $this->requestProduct;
    }

    /**
     * Set productUnit
     *
     * @param ProductUnit|null $productUnit
     * @return RequestProductItem
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;
        if ($productUnit) {
            $this->productUnitCode = $productUnit->getCode();
        }

        return $this;
    }

    /**
     * Get productUnit
     *
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Set productUnitCode
     *
     * @param string $productUnitCode
     * @return RequestProductItem
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    /**
     * Get productUnitCode
     *
     * @return string
     */
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        $this->loadPrice();

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        $this->loadPrice();

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param Price|null $price
     * @return RequestProductItem
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    /**
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @ORM\PostLoad
     */
    public function loadPrice()
    {
        if ($this->value !== null && $this->currency !== null) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatePrice()
    {
        if ($this->price !== null) {
            $this->value = (float)$this->price->getValue();
            $this->currency = (string)$this->price->getCurrency();
        } else {
            $this->value = $this->currency = null;
        }
    }

    public function getProduct()
    {
        return $this->requestProduct?->getProduct();
    }

    public function getProductSku()
    {
        return $this->getProduct()?->getSku();
    }

    public function getParentProduct()
    {
        return null;
    }

    /**
     * @ORM\PostLoad
     */
    public function loadKitItemLineItems(): void
    {
        if ($this->requestProduct) {
            $this->kitItemLineItems = $this->requestProduct->getKitItemLineItems()->map(
                fn (RequestProductKitItemLineItem $item) => (clone $item)->setLineItem($this)
            );
        } else {
            $this->kitItemLineItems = new ArrayCollection();
        }
    }

    /**
     * @return Collection<RequestProductKitItemLineItem>
     */
    public function getKitItemLineItems()
    {
        if (!$this->kitItemLineItems->count()) {
            $this->loadKitItemLineItems();
        }

        return $this->kitItemLineItems;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
