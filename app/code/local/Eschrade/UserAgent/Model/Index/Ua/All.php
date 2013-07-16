<?php

class Eschrade_UserAgent_Model_Index_Ua_All extends Eschrade_UserAgent_Model_Index_Ua_Changed 
{
		
	public function preIndexHook()
	{
		$this->_connection->truncateTable(
				$this->getReportResource()->getTable('agentreport')
		);
	}
	
	protected function joinSelectWithUrlAndVisitor(Varien_Db_Select $select)
	{
		$select->reset(Varien_Db_Select::WHERE);
		parent::joinSelectWithUrlAndVisitor($select);
		
	}
}