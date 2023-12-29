<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentBundle\Migrations\Schema\v1_6\OroPaymentBundle as BaseOroPaymentBundle;

class OroPaymentBundle implements Migration, ActivityExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            BaseOroPaymentBundle::PAYMENT_METHOD_CONFIG_RULE_TABLE
        );
    }
}
