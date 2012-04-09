<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2009 Thomas Rabaix <thomas.rabaix@soleoweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(sfConfig::get('sf_root_dir').'/plugins/sfSolrPlugin/lib/task/sfLuceneBaseTask.class.php');
require_once(sfConfig::get('sf_root_dir').'/apps/client/lib/StreemeUtil.class.php');

/**
* Start solr web server, use Jetty WebServer
*
* @author Thomas Rabaix <thomas.rabaix@soleoweb.com>
* @package sfLucenePlugin
* @subpackage Tasks
* @depends sfSolrPlugin <https://github.com/rande/sfSolrPlugin>
* @version SVN: $Id: sfLuceneInitializeTask.class.php 12678 2008-11-06 09:23:10Z rande $
*/

class solrBootstrapTask extends sfLuceneBaseTask
{
  protected
    $nohup = false,
    $java  = false;
  
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('action', sfCommandArgument::REQUIRED, 'The action name')
    ));
    
    $this->isWindows = StreemeUtil::is_windows();
    if($this->isWindows)
    {
      $java = 'java';
    }
    else
    {
      $results =  $output = false;
      exec('which java', $output, $results);
      $java = $results == 0 ? $output[0] : false;
  
      $results = $output = false;
      exec('which nohup', $output, $results);
      $nohup = $results == 0 ? $output[0] : false;     
    }
    
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'client'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('java', null, sfCommandOption::PARAMETER_REQUIRED, 'the java binary', $java),
      new sfCommandOption('nohup', null, sfCommandOption::PARAMETER_REQUIRED, 'the nohup binary', $nohup),
      new sfCommandOption('Xmx', null, sfCommandOption::PARAMETER_REQUIRED, 'maximum java heap size', '512m'),
      new sfCommandOption('Xms', null, sfCommandOption::PARAMETER_REQUIRED, 'initial java heap size', '256m'),
      new sfCommandOption('host', null, sfCommandOption::PARAMETER_REQUIRED, 'The Hostname for Solr', '127.0.0.1'),
      new sfCommandOption('port', null, sfCommandOption::PARAMETER_REQUIRED, 'Solr\'s listen port', '8983'),
    ));

    $this->namespace = '';
    $this->name = 'solr';
    $this->briefDescription = 'start or stop the Solr server on windows and unix';

    $this->detailedDescription = <<<EOF
The [lucene:service|INFO] start or stop the Solr server

The command use the SOLR indexing service

    [./symfony solr start|INFO]   start the Solr server
    [./symfony solr stop|INFO]    stop the Solr server
    [./symfony solr restart|INFO] restart the Solr server

Note about Jetty :

  Jetty is an open-source project providing a HTTP server, HTTP client and
  javax.servlet container. These 100% java components are full-featured,
  standards based, small foot print, embeddable, asynchronous and enterprise
  scalable. Jetty is dual licensed under the Apache Licence 2.0 and/or the
  Eclipse Public License 1.0. Jetty is free for commercial use and distribution
  under the terms of either of those licenses.

  more information about Jetty : http://www.mortbay.org/jetty/

EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $app = $options['application'];
    $env = $options['env'];

    if(!is_executable($options['java']) && !$this->isWindows )
    {
      throw new sfException("Solr: This application requires Java. Please install java before proceeding.\r\n(you can use the option --java=path to specify a java executable)");
    }

    $this->java = $options['java'];
    
    if(!is_executable($options['nohup']) && !$this->isWindows )
    {
      throw new sfException('Solr: Please provide a valid nohup executable file');
    }
    
    $this->nohup = $options['nohup'];


    $action = $arguments['action'];

    switch($action)
    {
      case 'start':
       $this->start($app, $env, $options);
       break;

     case 'stop':
       $this->stop($app, $env, $options);

       break;

     case 'restart':
       $this->stop($app, $env, $options);
       $this->start($app, $env, $options);
       break;
    }
  }

  public function isRunning($app, $env, $options = array())
  {
    try{
      $ping = new StreemeIndexerSolr();
      unset($ping);
      return true;
    }
    catch(Exception $e)
    {
      return false;
    }
  }

  public function start($app, $env, $options = array())
  {
    if($this->isRunning($app, $env))
    {
      $this->logSection("solr","Started. healthy.");
      return;
    }
    else
    {
      $host     = $options['host'];
      $port     = $options['port'];
      if($this->isWindows)
      {
        $command = sprintf('cd %s/plugins/sfSolrPlugin/lib/vendor/Solr/example && start /b "Streeme Solr Indexer" %s -Dsolr.solr.home=%s/config/solr/ -Dsolr.data.dir=%s/data/solr_index -Dsolr.lib.dir=%s/plugins/sfSolrPlugin/lib/vendor/Solr/example/solr/lib -Djetty.port=%s -Djetty.logs=%s -jar start.jar > %s/solr_server_%s_%s.log',
          sfConfig::get('sf_root_dir'),
          $this->java,
          sfConfig::get('sf_root_dir'),
          sfConfig::get('sf_root_dir'),
          sfConfig::get('sf_root_dir'),
          $port,
          sfConfig::get('sf_root_dir').'/log',
          sfConfig::get('sf_root_dir').'/log',
          $app,
          $env
        );    
      }
      else
      {
        $command = sprintf('cd %s/plugins/sfSolrPlugin/lib/vendor/Solr/example; %s %s -Xmx%s -Xms%s -Dsolr.solr.home=%s/config/solr/ -Dsolr.data.dir=%s/data/solr_index -Dsolr.lib.dir=%s/plugins/sfSolrPlugin/lib/vendor/Solr/example/solr/lib -Djetty.port=%s -Djetty.logs=%s -jar start.jar > %s/solr_server_%s_%s.log 2>&1 & echo $!',
          sfConfig::get('sf_root_dir'),
          $this->nohup,
          $this->java,
          $options['Xmx'],
          $options['Xms'],
          sfConfig::get('sf_root_dir'),
          sfConfig::get('sf_root_dir'),
          sfConfig::get('sf_root_dir'),
          $port,
          sfConfig::get('sf_root_dir').'/log',
          sfConfig::get('sf_root_dir').'/log',
          $app,
          $env
        );
      }

      $this->logSection('exec ', $command);
      exec($command ,$op);
      
      if(method_exists($this->getFilesystem(), 'execute')) // sf1.3 or greater
      {
        $this->getFilesystem()->execute(sprintf('cd %s',
          sfConfig::get('sf_root_dir')
        ));      
      }
  
      $pid = (int)$op[0];
      file_put_contents($this->getPidFile($app, $env), $pid);
  
      $this->logSection("solr", "Server started with pid : ".$pid);
      $this->logSection("solr", "server started  : http://".$host.":".$port);
    }
  }

  public function stop($app, $env, $options = array())
  {
    if(!$this->isRunning($app, $env))
    {
      $this->logSection("solr", "Solr server does not appear to be running");
      return;
    }
    if($this->isWindows)
    {
      $this->logSection("solr", "To stop the SOLR process in Windows, please use the taskmanager and stop the processes manually (java.exe)"); 
      return;  
    }
    else
    {
      $pid = file_get_contents($this->getPidFile($app, $env));
  
      if(!($pid > 0))
      {
        
        throw new sfException('Invalid pid provided : '.$pid);
      }
  
      if(method_exists($this->getFilesystem(), 'execute')) // sf1.3 or greater
      {
        $this->getFilesystem()->execute("kill -15 ".$pid);
      }
      else
      {
        $this->getFilesystem()->sh("kill -15 ".$pid);
      }
  
      unlink($this->getPidFile($app, $env));
    }
  }

  public function getPidFile($app, $env)
  {
    if($this->isWindows)
    {
      $file = sprintf('%TEMP%');
    }
    else
    {
      $file = sprintf('/tmp/%s_%s.pid',
        $app,
        $env
      );
    }
    return $file;
  }
}