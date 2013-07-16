<?php

/* @var $this Mage_Core_Model_Resource_Setup */

$table = $this->getConnection()->newTable(
	$this->getTable('eschrade_useragent/agentreport')
);
$table->addColumn(
	'agent_report_id',
	Varien_Db_Ddl_Table::TYPE_INTEGER,
	null,
	array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ),
	'Agent Report Id'
);

$table->addColumn('page', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
    ), 'Page');

$table->addColumn('agent', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
    ), 'User Agent');

$table->addColumn('request_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ), 'Request Count');

$table->addIndex(
	$this->getIdxName('eschrade_useragent/agentreport', array('page', 'agent')),
	array('page', 'agent'),
	array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
);

$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable(
		$this->getTable('eschrade_useragent/agentnames')
);

$table->addColumn('http_user_agent', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
		'nullable'  => false,
), 'Join Column for User Agent');

$table->addColumn('normalized_user_agent', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
		'nullable'  => false,
), 'Normalized User Agent');

$table->addIndex(
		$this->getIdxName('eschrade_useragent/agentreport', array('http_user_agent')),
		array('http_user_agent'),
		array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
);

$this->getConnection()->createTable($table);

$logTable = Mage::getModel('log/log')->getResource()->getTable('url_table');
$pkData = $this->_conn->getIndexList($logTable);

if (!isset($pkData['PRIMARY']['COLUMNS_LIST'][0])) {
	Mage::throwException('Unable to find log table primary key');
}

$logPrimaryKey = $pkData['PRIMARY']['COLUMNS_LIST'][0];

Mage::getModel('enterprise_mview/metadata')
	->setViewName($logTable)
	->setTableName($logTable)
	->setKeyColumn($logPrimaryKey)
	->setGroupCode('eschrade_useragent_report')
	->setStatus(Enterprise_Mview_Model_Metadata::STATUS_INVALID)
	->save();

$client = Mage::getModel('enterprise_mview/client');
/* @var $client Enterprise_Mview_Model_Client */
$client->init($logTable);

$client->execute('enterprise_mview/action_changelog_create', array(
	'table_name' => $logTable
));

$client->execute('enterprise_mview/action_changelog_subscription_create', array(
		'target_table' 	=> $logTable,
		'target_column' => $logPrimaryKey
));
