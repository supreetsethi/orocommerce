<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class DefaultVisibilityListenerTest extends WebTestCase
{
    use EntityTrait;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var AccountGroup
     */
    protected $accountGroup;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
        ]);

        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $this->account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $this->accountGroup = $this->getReference(LoadGroups::GROUP1);
    }

    /**
     * @param string $entityClass
     * @param array $parameters
     * @param string|null $entityClassToClear
     * @dataProvider onFlushDataProvider
     */
    public function testOnFlushVisibility($entityClass, array $parameters, $entityClassToClear = null)
    {
        $entityManager = $this->getManager($entityClass);

        $properties = [];
        foreach ($parameters as $parameter) {
            $properties[$parameter] = $this->$parameter;
        }

        // persisted with custom visibility
        /** @var VisibilityInterface $entity */
        $entity = $this->findOneBy($entityClass, $properties);
        if (!$entity) {
            $entity = $this->getEntity($entityClass, $properties);
        }
        $entity->setVisibility(VisibilityInterface::VISIBLE);
        $entityManager->persist($entity);
        $entityManager->flush();
        $this->assertEntitiesSame($entity, $this->findOneBy($entityClass, $properties));
        $this->assertEquals(VisibilityInterface::VISIBLE, $entity->getVisibility());
        if ($entityClassToClear) {
            $entityManager->clear($entityClassToClear);
        }

        // updated with custom visibility
        $entity->setVisibility(VisibilityInterface::HIDDEN);
        $entityManager->flush();
        $this->assertEntitiesSame($entity, $this->findOneBy($entityClass, $properties));
        $this->assertEquals(VisibilityInterface::HIDDEN, $entity->getVisibility());
        if ($entityClassToClear) {
            $entityManager->clear($entityClassToClear);
        }

        // updated with default visibility
        $entity->setVisibility($entity::getDefault($entity->getTargetEntity()));
        $entityManager->flush();
        $this->assertNull($this->findOneBy($entityClass, $properties));
        if ($entityClassToClear) {
            $entityManager->clear($entityClassToClear);
        }

        // persisted with default visibility
        $entity = $this->getEntity($entityClass, $properties);
        $entity->setVisibility($entity::getDefault($entity->getTargetEntity()));
        $entityManager->persist($entity);
        $entityManager->flush();
        $this->assertNull($this->findOneBy($entityClass, $properties));
        if ($entityClassToClear) {
            $entityManager->clear($entityClassToClear);
        }
    }

    /**
     * @return array
     */
    public function onFlushDataProvider()
    {
        return [
            'category visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility',
                'parameters' => ['category'],
            ],
            'account category visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility',
                'parameters' => ['category', 'account'],
            ],
            'account group category visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility',
                'parameters' => ['category', 'accountGroup'],
            ],
            'product visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility',
                'parameters' => ['website', 'product'],
                'entityClassToClear' =>
                    'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved',
            ],
            'account product visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility',
                'parameters' => ['website', 'product', 'account'],
                'entityClassToClear' =>
                    'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved',

            ],
            'account group product visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility',
                'parameters' => ['website', 'product', 'accountGroup'],
                'entityClassToClear' =>
                    'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved',
            ],
        ];
    }

    /**
     * @param string $entityClass
     * @return ObjectManager
     */
    protected function getManager($entityClass)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }

    /**
     * @param string $entityClass
     * @param array $criteria
     * @return object|null
     */
    protected function findOneBy($entityClass, array $criteria)
    {
        return $this->getManager($entityClass)->getRepository($entityClass)->findOneBy($criteria);
    }

    /**
     * @param object $expected
     * @param object $actual
     */
    protected function assertEntitiesSame($expected, $actual)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $this->assertEquals(
            $propertyAccessor->getValue($expected, 'id'),
            $propertyAccessor->getValue($actual, 'id')
        );
    }
}
