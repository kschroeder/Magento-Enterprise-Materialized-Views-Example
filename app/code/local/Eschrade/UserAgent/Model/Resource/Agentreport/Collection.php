<?php

class Eschrade_UserAgent_Model_Resource_Agentreport_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	protected function _construct()
	{
		$this->_init('eschrade_useragent/agentreport');
	}
}