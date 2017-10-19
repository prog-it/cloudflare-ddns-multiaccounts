<?php error_reporting(E_ALL | E_STRICT); ini_set('display_errors', 'On'); 

chdir(__DIR__);

require_once 'inc/Cloudflare.class.php';
require_once 'inc/Config.class.php';
require_once 'inc/ConfigNostatic.class.php';
require_once 'inc/Func.class.php';
require_once 'inc/Scraper.class.php';
require_once 'inc/ZoneUpdater.class.php';
require_once 'inc/Logger.class.php';

# Путь к конфигу
Config::setPath('config/config.php');

# Проверка токена
Func::checkToken();

# Очистка лога
Logger::clean();

# Записи для обновления
$entries_paths = Func::getAllEntries();

if ($entries_paths) {
	$scraper = new Scraper();
	foreach ($entries_paths as $path) {
		$entry = new ConfigNostatic();
		$entry->init($path);
		if (Func::checkValidEntry($path, $entry)) {
			$scraper->setNeedIpv4($entry->get('ipv4_enabled'));
			$scraper->setNeedIpv6($entry->get('ipv6_enabled'));
			$scraper->getIps();
			$ipv4 = $scraper->getIpv4();
			$ipv6 = $scraper->getIpv6();
			if($ipv4 !== false && $ipv6 !== false) {
				$updater = new ZoneUpdater();
				$updater->setEmail($entry->get('email'));
				$updater->setKey($entry->get('key'));
				$updater->setDomain($entry->get('domain'));
				$updater->setNeedIpv4($entry->get('ipv4_enabled'));
				$updater->setNeedIpv6($entry->get('ipv6_enabled'));
				$updater->setIpv4($ipv4);
				$updater->setIpv6($ipv6);
				$updater->setZones($entry->get('zones'));
				$updater->update();
				unset($updater);
			}
		}
		unset($entry);
	}
}
