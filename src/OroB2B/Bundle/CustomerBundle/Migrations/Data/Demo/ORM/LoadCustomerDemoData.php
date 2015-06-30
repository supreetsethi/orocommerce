<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomerDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerInternalRatingDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AccountUser[] $accountUsers */
        $accountUsers = $manager->getRepository('OroB2BCustomerBundle:AccountUser')->findAll();
        $internalRatings =
            $manager->getRepository(ExtendHelper::buildEnumValueClassName(Customer::INTERNAL_RATING_CODE))->findAll();

        $rootCustomer = null;
        $firstLevelCustomer = null;

        // create customer groups
        $rootGroup = $this->createCustomerGroup('Root');
        $firstLevelGroup = $this->createCustomerGroup('First');
        $secondLevelGroup = $this->createCustomerGroup('Second');

        $manager->persist($rootGroup);
        $manager->persist($firstLevelGroup);
        $manager->persist($secondLevelGroup);

        // create customers
        foreach ($accountUsers as $index => $accountUser) {
            $customer = $accountUser->getCustomer();
            switch ($index % 3) {
                case 0:
                    $customer->setGroup($rootGroup);
                    $rootCustomer = $customer;
                    break;
                case 1:
                    $customer->setGroup($firstLevelGroup)
                        ->setParent($rootCustomer);
                    $firstLevelCustomer = $customer;
                    break;
                case 2:
                    $customer->setGroup($secondLevelGroup)
                        ->setParent($firstLevelCustomer);
                    break;
            }
            $customer->setInternalRating($internalRatings[array_rand($internalRatings)]);
        }

        $manager->flush();
    }

    /**
     * @param string $name
     * @return CustomerGroup
     */
    protected function createCustomerGroup($name)
    {
        $customerGroup = new CustomerGroup();
        $customerGroup->setName($name);

        return $customerGroup;
    }
}
