<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPriceListsFallback implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOrob2BPriceListAccountFallbackTable($schema);
        $this->createOrob2BPriceListAccGroupFallbackTable($schema);
        $this->createOrob2BPriceListWebsiteFallbackTable($schema);

        $this->addOrob2BPriceListAccountFallbackForeignKeys($schema);
        $this->addOrob2BPriceListAccGroupFallbackForeignKeys($schema);
        $this->addOrob2BPriceListWebsiteFallbackForeignKeys($schema);
    }

    /**
     * Create orob2b_price_list_acc_fb table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListAccountFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_acc_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['website_id'], 'idx_2582e3518f45c82', []);
        $table->addUniqueIndex(['account_id'], 'uniq_2582e359b6b5fba');
    }

    /**
     * Create orob2b_price_list_acc_gr_fb table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListAccGroupFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_acc_gr_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->addIndex(['website_id'], 'idx_29d4f57618f45c82', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_group_id'], 'uniq_29d4f576869a3bf1');
    }

    /**
     * Create orob2b_price_list_website_fb table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListWebsiteFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_website_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_price_list_account_fallback foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListAccountFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_acc_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_list_acc_gr_fb foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListAccGroupFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_acc_gr_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_list_website_fb foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListWebsiteFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_website_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
