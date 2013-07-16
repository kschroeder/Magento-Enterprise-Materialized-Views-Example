<?php

class Eschrade_UserAgent_Model_Index_Ua_Changed extends  Enterprise_Mview_Model_Action_Mview_Refresh_Changelog
{

	public function preIndexHook()
	{
		
	}
	
	public function execute()
	{
		try {
			$this->_connection->beginTransaction();
			
			$this->preIndexHook();
				
			$this->populateUserAgentTable();
			$versionId = $this->_selectLastVersionId();
			$this->processIndex();
				
			$this->_metadata->setValidStatus()
				->setVersionId($versionId)
				->save();
			$this->_connection->commit();
		} catch (Exception $e) {
			$this->_connection->rollBack();
			$this->_metadata->setInvalidStatus()->save();
			
			throw $e;
		}
		return $this;
	}
	
	public function populateUserAgentTable()
	{
		
		$uaTable = Mage::getModel('log/log')->getResource()->getTable('visitor_info');
		$nuaTable = Mage::getModel('eschrade_useragent/agentreport')->getResource()->getTable('agentnames');
		
		// Create a SELECT statement that looks in the log_visitor_info table for 
		
		$select = $this->_connection->select();
		$select->from(array('lvi' => $uaTable), 'http_user_agent');
		$select->distinct(true);
		$select->joinLeft(array('eua' => $nuaTable), 'eua.http_user_agent = lvi.http_user_agent', null);
		$select->where('eua.normalized_user_agent IS NULL');
		
		$stmt = $select->query();
		
		while (($row = $stmt->fetch()) !== false) {
			$ua = get_browser($row['http_user_agent']);
			$browser = !isset($ua->parent)?'Unknown':$ua->parent;
			try {
				$this->_connection->insert(
					$nuaTable,
					array(
						'http_user_agent'		=> $row['http_user_agent'],
						'normalized_user_agent'	=> $browser
					)
				);
			} catch (Exception $e) {
				/*
				* We ignore this because there are many times when the native visitor_info table truncates the user agent
				* What this means is that we can get multiple different user agents, but if their length is greater than
				* 255 chars they will be cut off.
				*/
				
				Mage::log('Unexpected User Agent Normalization Error ' . $e->getMessage() . ' for ' . $ua, Zend_Log::NOTICE);
			}
		}
	}
	
	public function processIndex()
	{
		
		$indexTable = Mage::getModel('eschrade_useragent/agentreport')->getResource()->getMainTable();
		
		$select = $this->_selectChangedRows();
		$this->joinSelectWithUrlAndVisitor($select);
		
		$stmt = $select->query();
		
		while (($row = $stmt->fetch()) !== false) {
			
			$this->_connection->insertOnDuplicate(
				$indexTable,
				array(
					'page'			=> $row['url'],
					'agent'			=> $row['normalized_user_agent'],
					'request_count'	=> $row['page_count']
				),
				array(
					'request_count'  => new Zend_Db_Expr('request_count + VALUES(request_count)')
				)
			);
		}
	}
	
	protected function joinSelectWithUrlAndVisitor(Varien_Db_Select $select)
	{
		
		$uaTableNorm= Mage::getModel('eschrade_useragent/agentreport')->getResource()->getTable('agentnames');
		$uaTable = Mage::getModel('log/log')->getResource()->getTable('visitor_info');
		$urlTable = Mage::getModel('log/log')->getResource()->getTable('url_info_table');
		$select->reset(Varien_Db_Select::COLUMNS);
		$source = $select->getPart(Varien_Db_Select::FROM);
		$sourceName = key($source);		
		$select->join(array('ua_table' => $uaTable), 'ua_table.visitor_id = ' . $sourceName . '.visitor_id', null);
		$select->join(array('uan_table' => $uaTableNorm), 'ua_table.http_user_agent = uan_table.http_user_agent', 'normalized_user_agent');
		$select->join(array('url_table' => $urlTable), 'url_table.url_id = ' . $sourceName . '.url_id', 'url');
		$select->columns(array('page_count' => 'COUNT(*)'));
		$select->group(array('uan_table.normalized_user_agent', 'url_table.url'));
	}
	
	protected function _selectChangedIds()
	{
		/*
		 * This method needed to be overridden because it was generating a column that
		 * was ambiguous as well as more columns than we actually needed.  So we remove 
		 * the first * reference and set a unique alias to remove the ambiguity. 
		 */
		
		$select = parent::_selectChangedIds();
		$part = $select->getPart(Varien_Db_Select::COLUMNS);
		if (count($part) > 1) {
			$select->reset(Varien_Db_Select::COLUMNS);
			$select->columns(array('log_url_id' => $part[1][1]), $part[1][0]);
		}
		return $select;
	}
	
	protected function _selectChangedRows()
	{
		/*
		* This method needed to be overridden because the WHERE clause in the parent class
		* did not use the source alias and because there are multiple columns with the name
		* "url_id" we need to add the alias to specify which "url_id" we're actually talking about 
		*/
		
		return $this->_connection->select()
		->from(array('source' => $this->_metadata->getViewName()))
		->where('source.' . $this->_metadata->getKeyColumn() . ' IN ( '  . $this->_selectChangedIds() . ' )');
	}
	
	/**
	 * Convenience method 
	 * 
	 * @return Eschrade_UserAgent_Model_Resource_Agentreport
	 */
	
	public function getReportResource()
	{
		return Mage::getModel('eschrade_useragent/agentreport')->getResource();
	}
}