<?php

class Eschrade_UserAgent_Model_Index_Ua_Dummy extends Enterprise_Index_Model_Indexer_Dummy
{
	
	public function getName()
	{
		return Mage::helper('eschrade_useragent')->__('User Agent Report');
	}
	
	
	public function getDescription()
	{
		return Mage::helper('eschrade_useragent')->__('User Agent Report');
	}
}