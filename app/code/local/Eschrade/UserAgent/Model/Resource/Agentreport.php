<?php

class Eschrade_UserAgent_Model_Resource_Agentreport extends Mage_Core_Model_Resource_Db_Abstract
{
	protected function _construct()
	{
		$this->_init('eschrade_useragent/agentreport', 'agent_report_id');
	}
}