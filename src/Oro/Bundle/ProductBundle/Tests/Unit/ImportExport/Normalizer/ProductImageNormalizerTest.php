<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductImageNormalizer;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductImageNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldHelper;

    /** @var ProductImageNormalizer */
    protected $productImageNormalizer;

    /** @var FileLocator|\PHPUnit\Framework\MockObject\MockObject */
    protected $fileLocator;

    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $imageTypeProvider;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    protected function setUp(): void
    {
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->fileLocator = $this->createMock(FileLocator::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->imageTypeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn(
                [
                    ProductImageType::TYPE_MAIN => true
                ]
            );

        $this->fieldHelper->expects($this->any())
            ->method('getFields')
            ->willReturn(
                [
                    [
                        'name' => 'types'
                    ],
                ]
            );

        $this->productImageNormalizer = new ProductImageNormalizer($this->fieldHelper);
        $this->productImageNormalizer->setProductImageClass(ProductImage::class);
        $this->productImageNormalizer->setImageTypeProvider($this->imageTypeProvider);
        $this->productImageNormalizer->setFileLocator($this->fileLocator);
    }

    /**
     * @param StubProductImage $productImage
     *
     * @dataProvider normalizationDataProvider
     */
    public function testNormalize($productImage)
    {
        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->willReturn(
                [
                    'types' => ['type' => ProductImageType::TYPE_MAIN]
                ]
            );

        $result = $this->productImageNormalizer->normalize(
            $productImage,
            null
        );

        $this->assertArrayHasKey('types', $result);
        $this->assertArrayHasKey('image', $result);
    }

    public function testDenormalize()
    {
        $productImageData = [
            'types' => [
                ProductImageType::TYPE_MAIN => true
            ],
            'image' => ['name' => 'imageName'],
        ];

        $this->fieldHelper->expects($this->once())
            ->method('setObjectValue')
            ->willReturnCallback(
                function (ProductImage $result, $fieldName, $value) {
                    return $result->{'add' . ucfirst(substr($fieldName, 0, -1))}(reset($value));
                }
            );

        $productImage = $this->productImageNormalizer->denormalize(
            $productImageData,
            ProductImage::class,
            null
        );

        $this->assertArrayHasKey(ProductImageType::TYPE_MAIN, $productImage->getTypes());
    }

    public function testSupportsNormalization()
    {
        $result = $this->productImageNormalizer->supportsNormalization(
            new StubProductImage(),
            null
        );

        $this->assertTrue($result);
    }

    public function testSupportsDenormalization()
    {
        $result = $this->productImageNormalizer->supportsDenormalization(
            [],
            StubProductImage::class,
            null
        );

        $this->assertTrue($result);
    }

    /**
     * @return array
     */
    public function normalizationDataProvider()
    {
        $productImage = new StubProductImage();

        $file = new File();
        $file->setOriginalFilename('sku_001_1.jpg');

        $product = new Product();
        $product->setSku('sku_001');

        $productImage->setImage($file);
        $productImage->addType(new ProductImageType(ProductImageType::TYPE_MAIN));
        $productImage->setProduct($product);

        return [
            [$productImage]
        ];
    }
}
