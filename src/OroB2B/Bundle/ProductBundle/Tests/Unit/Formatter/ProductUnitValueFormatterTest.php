<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductUnitValueFormatterTest extends UnitValueFormatterTestCase
{
    const TRANSLATION_PREFIX = 'orob2b.product_unit';

    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new ProductUnitValueFormatter($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }
}
