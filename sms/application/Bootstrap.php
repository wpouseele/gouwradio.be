<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initBaseUrl()
	{
	    $options = $this->getOptions();
	    $baseUrl = isset($options['settings']['baseUrl']) 
	        ? $options['settings']['baseUrl']
	        : null;  // null tells front controller to use autodiscovery, the default
	    $this->bootstrap('frontcontroller');
	    $front = $this->getResource('frontcontroller');
	    $front->setBaseUrl($baseUrl);
	}
}

