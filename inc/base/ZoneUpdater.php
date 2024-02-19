<?php

/**
* ZoneUpdater
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*/

class ZoneUpdater {
	
	private $ipv4;
	private $ipv6;
	private $need_ipv4;
	private $need_ipv6;
	private $ip_type;
	private $email;
	private $key;
	private $token;
	private $domain;
	private $zones;
	private $api;
	private $api_zone;
	
	public function setIpv4($ipv4) {
		$this->ipv4 = $ipv4;
	}
	
	public function getIpv4() {
		return $this->ipv4;
	}

	public function setIpv6($ipv6) {
		$this->ipv6 = $ipv6;
	}
	
	public function getIpv6() {
		return $this->ipv6;
	}

	public function setNeedIpv4($need_ipv4) {
		$this->need_ipv4 = $need_ipv4;
	}
	
	public function getNeedIpv4() {
		return $this->need_ipv4;
	}

	public function setNeedIpv6($need_ipv6) {
		$this->need_ipv6 = $need_ipv6;
	}
	
	public function getNeedIpv6() {
		return $this->need_ipv6;
	}

	private function setTypeIpv4() {
		$this->ip_type = 'ipv4';
	}

	private function setTypeIpv6() {
		$this->ip_type = 'ipv6';
	}
	
	public function setEmail($email) {
		$this->email = $email;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function setKey($key) {
		$this->key = $key;
	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function setToken($token) {
		$this->token = $token;
	}
	
	public function getToken() {
		return $this->token;
	}
	
	public function setDomain($domain) {
		$this->domain = $domain;
	}
	
	public function getDomain() {
		return $this->domain;
	}
	
	public function setZones($zones) {
		$this->zones = $zones;
	}
	
	public function getZones() {
		return $this->zones;
	}

	private function isTypeIpv4() {
		if ($this->ip_type == 'ipv4') {
			return true;
		} else {
			return false;
		}
	}
	
	# Обновить данные об IP
	public function update() {
		foreach ($this->zones as $zone) {
			// Домен не существует на Cloudflare
			if ($this->api_zone === false) {
				Logger::write('Failed to get zone for domain: '.$this->domain);
				break;
			}
			if ($this->getNeedIpv4() == true && $this->ipv4 !== false) {
				$this->setTypeIpv4();
				$this->updateIp($zone);
			}
			if ($this->getNeedIpv6() == true && $this->ipv6 !== false) {
				$this->setTypeIpv6();
				$this->updateIp($zone);
			}			
		}
	}
	
	# Обновить IP
	private function updateIp($zone) {
		$rec_ip = $this->getRecordIp($zone);
		if ( $this->isTypeIpv4() ) {
			$curr_ip = $this->ipv4;
			$ip_type = 'IPv4';
		} else {
			$curr_ip = $this->ipv6;
			$ip_type = 'IPv6';
		}
		if ($rec_ip !== false) {
			if ($rec_ip != $curr_ip) {
				$this->getApiInstance();
				$this->getApiZone();
				if ($this->api_zone !== false) {
					$rec_params = $this->checkZone($zone);
					if ($rec_params === false) {
						$this->addZone($zone);
					} else {
						$this->updateZone($zone, $rec_params);
					}
				}
			} else {
				Logger::write("Skipped ".$this->fullRecordName($zone)." as it's already pointing to ".$curr_ip, Config::get('log.detail'));
			}
		} else {
			Logger::write('Failed to get current '.$ip_type.' for zone: '.$this->fullRecordName($zone), Config::get('log.detail'));
		}
	}
	
	# Получить IP записи
	private function getRecordIp($zone) {
		$rec_name = $this->fullRecordName($zone).'.';
		if ($this->isTypeIpv4()) {
			$dns_type = DNS_A;
			$ip_key = 'ip';
		} else {
			$dns_type = DNS_AAAA;
			$ip_key = 'ipv6';			
		}
		$ip = dns_get_record($rec_name, $dns_type);
		// При первом запросе возвращает IP не всегда. Небольшая пауза для этого 0.2 сек.
		usleep(200000);
		$ip = dns_get_record($rec_name, $dns_type);
		if ($ip !== false) {
			return isset($ip[0][$ip_key]) ? $ip[0][$ip_key] : '';
		}
		return false;
	}
	
	# Получить API инстанс Cloudflare
	private function getApiInstance() {
		if (!isset($this->api)) {
			$this->api = new Cloudflare( $this->email, $this->key, $this->token );
			$this->api->setCurlParams([
				'useragent' => Config::get('curl.useragent'),
				'timeout'   => Config::get('curl.timeout'),
				'cookie'    => Config::get('db.cookie'),
			]);
		}
		return $this->api;
	}
	
	# Получить данные домена с Cloudflare
	private function getApiZone() {
		if (!isset($this->api_zone)) {
			$zone = $this->api->getZone($this->domain);
			$this->api_zone = $zone ? $zone : false;
		}
		return $this->api_zone;
	}
	
	# Существует ли зона на Cloudflare
	private function checkZone($zone) {
		$res = false;
		$rec_name = $this->fullRecordName($zone);
		$records = $this->api->getZoneDnsRecords($this->api_zone->id, ['name' => $rec_name]);
		if ($records) {
			$rec_type = $this->isTypeIpv4() ? 'A' : 'AAAA';
			$dns_search = array_filter($records, function($a) use ($rec_name, $rec_type) {
				return $a->name == $rec_name && $a->type == $rec_type;
			});
			if ($dns_search) {
				$res = array_shift($dns_search);
			}			
		}
		return $res;
	}
	
	# Создать зону
	private function addZone($zone) {
		$rec_name = $this->fullRecordName($zone);
		$rec_ttl = $this->getValidTTL($zone);
		if ( $this->isTypeIpv4() ) {
			$rec_type = 'A';
			$rec_ip = $this->ipv4;
		} else {
			$rec_type = 'AAAA';
			$rec_ip = $this->ipv6;			
		}
		$ans = $this->api->createDnsRecord($this->api_zone->id, $rec_type, $rec_name, $rec_ip, [
			'ttl' => $rec_ttl,
			'proxied' => $this->getValidProxied($zone),
		]);
		$result = 'Added ';
		// Запись НЕ создана
		if ( !isset($ans->id) ) {
			$res = 'Problem adding ';
		}
		Logger::write( $result.$rec_name.' pointing to '.$rec_ip.' (TTL: '.$rec_ttl.')' );
	}
	
	# Обновить зону
	private function updateZone($zone, $rec_params) {
		$rec_name = $this->fullRecordName($zone);
		$rec_ttl = $this->getValidTTL($zone);
		if ( $this->isTypeIpv4() ) {
			$rec_type = 'A';
			$rec_ip = $this->ipv4;
		} else {
			$rec_type = 'AAAA';
			$rec_ip = $this->ipv6;			
		}		
		if ( $rec_params->type != $rec_type || $rec_params->content != $rec_ip || $rec_params->ttl != $rec_ttl ) {
			$ans = $this->api->updateDnsRecord($this->api_zone->id, $rec_params->id, [
				'type'    => $rec_type,
				'name'    => $rec_name,
				'content' => $rec_ip,
				'ttl'     => $rec_ttl,
				'proxied' => $this->getValidProxied($zone),
			]);
			$result = 'Updated ';
			// Запись НЕ обновлена
			if ( !isset($ans->id) ) {
				$result = 'Problem updating ';
			}
			Logger::write( $result.$rec_name.' pointing to '.$rec_ip.' (TTL: '.$rec_ttl.')' );
		} else {
			Logger::write("Skipped ".$rec_name." as it's already pointing to ".$rec_ip." (TTL: ".$rec_params->ttl.")", Config::get('log.detail'));
		}
	}

	# Получить корректный TTL
	private function getValidTTL($zone) {
		// При проксировании запросов TTL всегда = 1 (auto)
		if ( $this->getValidProxied($zone) ) {
			return 1;
		}		
		// RFC2181 http://www.rfc-editor.org/rfc/rfc2181.txt
		$res = 30;
		$maxTTL = 2147483647;
		if ( isset($zone['ttl']) ) {
			$ttl = $zone['ttl'];
			if( is_numeric($ttl) && $ttl > 0 && $ttl <= $maxTTL ) {
				$res = $ttl;
			}
		}
		return $res;
	}
	
	# Получить корректный параметр "proxied" - проксирование запросов через Cloudflare
	private function getValidProxied($zone) {
		return isset($zone['proxied']) && $zone['proxied'] == true ? true : false;
	}
	
	# Полное имя записи с доменом
	private function fullRecordName($zone) {
		return (isset($zone['name']) && strlen($zone['name']) > 0 ? $zone['name'].'.' : '').$this->domain;
	}

}
