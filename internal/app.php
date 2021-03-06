<?php
include_once('config/cfgLocal.php'); // local config
if ( ! class_exists('AppCfg')) exit('Error: Obojobo cfgLocal invalid or missing.');
spl_autoload_register('classAutoLoader');
require_once('vendor/autoload.php');
define ("CONFIG_ROOT", dirname(__FILE__) . '/config/');

// Setup the global logger
use Monolog\Logger;
use Monolog\Registry;
use Monolog\Handler\RotatingFileHandler;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;

$app = new Logger('app');
$handler = new RotatingFileHandler(\AppCfg::DIR_BASE.'internal/logs/obojobo', 0, Logger::DEBUG, true, 0664);
$handler->setFormatter(new LineFormatter(null, null, true, true));
$app->pushHandler($handler);
ErrorHandler::register($app); // catch error_log and other php errors
Registry::addLogger($app);

function trace($arg, $force=0)
{
	if (is_object($arg) || is_array($arg))
	{
		$arg = var_export($arg, true);
	}
	$level = $force ? Logger::INFO : Logger::DEBUG;
	Registry::app()->addRecord($level, $arg);
}

function profile($name, $stuff)
{
	if ( ! Registry::hasLogger($name))
	{
		$logger = new Logger($name);
		$handler = new RotatingFileHandler(\AppCfg::DIR_BASE.'internal/logs/profile-'.$name, 0, Logger::INFO, true, 0664);
		$handler->setFormatter(new LineFormatter("[%datetime%]: %message%\n", null, false, true));
		$logger->pushHandler($handler);
		Registry::addLogger($logger);
	}

	Registry::$name()->addInfo($stuff);
}

function classAutoLoader($className)
{
	// classname is using namespaces
	if (strpos($className , '\\') !== false)
	{
		$file = \AppCfg::DIR_BASE . \AppCfg::DIR_CLASSES  . str_replace('\\', '/', $className) . '.class.php';
	}
	// classname is using old nm_class package notation
	else
	{
		$prefix = substr($className, 0, 4);
		switch ($prefix)
		{
			// TODO: move these into vendor and manage via composer
			case 'plg_': // convert plg_pluginName_ClassName to plugins/pluginName/classes/ClassName.class.php
				$pkgs = explode("_", $className);
				$package = $pkgs[1];
				unset($pkgs[0], $pkgs[1]);
				$file = \AppCfg::DIR_BASE.\AppCfg::DIR_PLUGIN.$package.'/classes/'.implode('/', $pkgs).'.class.php';
				break;

			case 'cfg_': // look in the config dir EX: config_plugin_UCFCourses
				$file = CONFIG_ROOT . '/' .str_replace('_', '/', substr($className, 4)) . '.class.php';
				break;

			default: // Look in the app classes dir EX: \rocketD\auth\AuthModule
				$file = \AppCfg::DIR_BASE . \AppCfg::DIR_CLASSES . str_replace('_', '/', $className) . '.class.php';
		}
	}
	@include($file);
}

// Fix a bug with php7.1 + and php72-pecl-memcache which breaks the session handling w/ memcache
class PeclMemcacheBugFixForSessionsHandler extends SessionHandler
{
	public function read($id)
	{
		$data = parent::read($id);
		 // the bug is a result of pecl-memcache not returning an empty string
		 // and that being incompatable with php 7.1's session handler
		return empty($data) ? '' : $data;
	}
}

if(\AppCfg::CACHE_MEMCACHE === true)
{
	$sessionHandler = new PeclMemcacheBugFixForSessionsHandler();
	session_set_save_handler($sessionHandler);
}
// END php72-pecl-memcache bug fix
