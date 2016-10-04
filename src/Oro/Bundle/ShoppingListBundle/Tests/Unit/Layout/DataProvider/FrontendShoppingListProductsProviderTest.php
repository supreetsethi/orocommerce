<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsProvider;

class FrontendShoppingListProductsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var FrontendProductPricesDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var  FrontendShoppingListProductsProvider
     */
    protected $provider;

    /**
     * @var ShoppingListLineItemsDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shoppingListLineItemsDataProvider;

    /**
     * @var ProductPriceFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceFormatter;

    public function setUp()
    {
        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->frontendProductPricesDataProvider = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shoppingListLineItemsDataProvider = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider')
            ->disableOriginalConstructor()->getMock();

        $this->productPriceFormatter = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->provider = new FrontendShoppingListProductsProvider(
            $this->doctrineHelper,
            $this->frontendProductPricesDataProvider,
            $this->shoppingListLineItemsDataProvider,
            $this->productPriceFormatter
        );
    }

    public function testGetAllPrices()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 2]);

        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 1]),
        ];
        $prices = ['price_1', 'price_2'];
        $expected = ['price_1', 'price_2'];

        $this->shoppingListLineItemsDataProvider->expects($this->once())
            ->method('getShoppingListLineItems')
            ->with($shoppingList)
            ->willReturn($lineItems);

        $this->frontendProductPricesDataProvider
            ->expects($this->once())
            ->method('getProductsAllPrices')
            ->with($lineItems)
            ->willReturn($prices);

        $this->productPriceFormatter->expects($this->once())
            ->method('formatProducts')
            ->with($prices)
            ->willReturn($expected);

        $result = $this->provider->getAllPrices($shoppingList);
        $this->assertEquals($expected, $result);
    }

    public function testGetAllPricesWithoutShoppingList()
    {
        $this->shoppingListLineItemsDataProvider->expects($this->never())
            ->method('getShoppingListLineItems');
        $this->frontendProductPricesDataProvider->expects($this->never())
            ->method('getProductsAllPrices');
        $this->productPriceFormatter->expects($this->never())
            ->method('formatProducts');

        $this->provider->getAllPrices();
    }

    /**
     * @dataProvider matchedPriceDataProvider
     * @param ShoppingList|null $shoppingList
     */
    public function testGetMatchedPrice($shoppingList)
    {
        $expected = null;

        if ($shoppingList) {
            $lineItems = [];

            $this->shoppingListLineItemsDataProvider->expects($this->once())
                ->method('getShoppingListLineItems')
                ->willReturn($lineItems);

            $expected = 'expectedData';
            $this->frontendProductPricesDataProvider
                ->expects($this->once())
                ->method('getProductsMatchedPrice')
                ->with($lineItems)
                ->willReturn($expected);
        }

        $result = $this->provider->getMatchedPrice($shoppingList);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function matchedPriceDataProvider()
    {
        return [
            'with shoppingList' => [
                'entity' => new ShoppingList(),
            ],
            'without shoppingList' => [
                'entity' => null,
            ],
        ];
    }

    public function testGetLastProductNamesGroupedByShoppingList()
    {
        $lineItemRepository = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($lineItemRepository);

        $shoppingLists = [$this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList')];
        $productCount = 1;

        $lineItemRepository->expects($this->once())
            ->method('getLastProductNamesGroupedByShoppingList')
            ->with($shoppingLists, $productCount);

        $this->provider->getLastProductNamesGroupedByShoppingList($shoppingLists, $productCount);
    }
}
