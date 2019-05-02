<?php
/**
Description:  Aramex Shipping Magento2 plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Aramex\Shipping\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;

/**
 * Class for Cod installation
 */
class InstallSchema implements InstallSchemaInterface
{

    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        foreach (['sales_order', 'sales_invoice', 'sales_creditmemo'] as $item) {
            foreach (['base_aramex_cash_on_delivery' => 'Base Cash On Delivery',
                         'aramex_cash_on_delivery' => 'Cash On Delivery'] as $key => $value) {
                if ($connection->tableColumnExists($item, $key) === false) {
                    $connection
                        ->addColumn(
                            $setup->getTable($item),
                            $key,
                            [
                                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                                'length' => '12,4',
                                'comment' => $value,
                                'nullable' => false,
                                'default' => (float)null,
                            ]
                        );
                }
            }
        }
        $installer->endSetup();
    }
}
