<?php

namespace Lb\MelhorEnvios\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package Lb\MelhorEnvios\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '0.4.0', '<')) {
            $this->createShippingTable($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function createShippingTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'melhor_envios_shipping_information'
         */
        $table = $installer->getConnection()->newTable(
        $installer->getTable('melhor_envios_shipping_information')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'shipping ID'
        )->addColumn(
            'shipping_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false],
            'Shiping Id'
        )->addColumn(
            'increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => false],
            'Order Id'
        )->addColumn(
            'package_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false],
            'Package Id'
        )->addColumn(
            'tracking_number',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'Tracking number'
        )->addColumn(
            'label_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'label url'
        )->addColumn(
            'creation_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Creation Time'
        )->addColumn(
            'update_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Modification Time'
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
