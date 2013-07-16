<?php

class Eschrade_UserAgent_Block_Graph extends Mage_Core_Block_Abstract
{
	protected function _toHtml()
	{
		$connection = Mage::getSingleton('core/resource')->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
		$visitor = Mage::getModel('log/visitor');
		/* @var $visitor Mage_Log_Model_Visitor */
		$url = $visitor->initServerData()->getUrl();
		$collection = Mage::getModel('eschrade_useragent/agentreport')->getCollection();
		/* @var $collection Eschrade_UserAgent_Model_Resource_Agentreport_Collection */
		$collection->addFieldToFilter('page', $url);
		$html = '';
		$max = 0;
		foreach ($collection as $report) {
			/* @var $report Eschrade_UserAgent_Model_Agentreport */
			if ($report->getRequestCount() > $max) {
				$max = $report->getRequestCount();
			}
		}
		
		foreach ($collection as $report) {
			$html .= sprintf(
				'<div style="width: %d%%; background-color: lightblue; height: 22px; "><div style="position: absolute; padding: 2px;">%s</div></div>',
				($report->getRequestCount() / $max ) * 100,
				$report->getAgent()
			);
		}
		
		return '<div style="width: 183px; ">' . $html . '</div>';
	}
	
}