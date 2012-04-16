<?php


require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

//$configuration = ProjectConfiguration::getApplicationConfiguration('client', 'prod', false);
$configuration = ProjectConfiguration::getApplicationConfiguration('client', 'prod', true);
sfContext::createInstance($configuration)->dispatch();
