<?php

/**
* Cloudflare API v4
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*
* Forked from https://github.com/mmerian/cloudflare
*
*/

class CloudflareException extends Exception
{

}

class Cloudflare
{
	/**
	 * @var string
	 */
	const GET = 'GET';
	/**
	 * @var string
	 */
	const POST = 'POST';
	/**
	 * @var string
	 */
	const PUT = 'PUT';
	/**
	 * @var string
	 */
	const PATCH = 'PATCH';
	/**
	 * @var string
	 */
	const DELETE = 'DELETE';
	/**
	 * @var string
	 */
	const ENDPOINT = 'https://api.cloudflare.com/client/v4/';
	/**
	 * @var string
	 */
	private $email;
	/**
	 * @var string
	 */
	private $apiKey;
	/**
	 * @var stdClass
	 */
	private $resultInfo;
	/**
	 * @var string
	 */
	private $curlUseragent = 'Mozilla/5.0 (compatible; MSIE 11.0; Windows NT 6.3; Trident/6.0)';
	/**
	 * @var integer
	 */	
	private $curlTimeout = 10;
	/**
	 * @var string
	 */	
	private $curlCookie;
	/**
	 * @var integer
	 */	
	private $curlMaxRedirs = 10;

	/**
	 * @param string $email
	 * @param string $apiKey
	 *
	 * @return ZiCloudFlare_Client
	 */
	public function __construct($email, $apiKey)
	{
		$this->email = $email;
		$this->apiKey = $apiKey;
	}
	
	/**
	 * Доп. параметры cURL
	 *
	 * @param array $p Параметры cURL
	 *
	 * @return void
	 */
	public function setCurlParams($p) {
		if ( isset($p['useragent']) ) { $this->curlUseragent = $p['useragent']; }
		if ( isset($p['timeout']) ) { $this->curlTimeout = $p['timeout']; }
		if ( isset($p['cookie']) ) { $this->curlCookie = $p['cookie']; }
		if ( isset($p['maxRedirs']) ) { $this->curlMaxRedirs = $p['maxRedirs']; }
	}

	/**
	 * Issues an HTTPS request and returns the result
	 *
	 * @param string $method
	 * @param string $endpoint
	 * @param array $params
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function request($method, $endpoint, $params = [])
	{
		$curl = curl_init();
		$headers = array(
			'X-Auth-Email: ' . $this->email,
			'X-Auth-Key: ' . $this->apiKey,
			'Content-type: application/json',
		);

		$url = self::ENDPOINT . ltrim($endpoint, '/');
		$json_params = json_encode($params);
		switch ($method) {
			case self::POST :
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json_params);
				break;
			case self::PUT :
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json_params);
				break;
			case self::PATCH :
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json_params);
				break;
			case self::DELETE :
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json_params);
				break;
			default:
				if ($params) {
					$url .= '?' . http_build_query($params);
				}
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, $this->curlUseragent);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->curlTimeout);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->curlTimeout);
		curl_setopt($curl, CURLOPT_MAXREDIRS, $this->curlMaxRedirs);
		if ( isset($this->curlCookie) ) {
			curl_setopt($curl, CURLOPT_COOKIEJAR, $this->curlCookie);
			curl_setopt($curl, CURLOPT_COOKIEFILE, $this->curlCookie);				
		}
		$response = json_decode(curl_exec($curl));
		if (! $response) {
			throw new CloudflareException(curl_error($curl));
		} elseif (false == $response->success) {
			throw new CloudflareException($response->errors[0]->message);
		}
		curl_close($curl);
		$this->resultInfo = isset($response->result_info) ? $response->result_info : null;
		return $response->result;
	}

	/**
	 * Issues an HTTP GET request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	public function get($endpoint, $params = [])
	{
		return $this->request(self::GET, $endpoint, $params);
	}

	/**
	 * Issues an HTTP POST request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	public function post($endpoint, $params = [])
	{
		return $this->request(self::POST, $endpoint, $params);
	}

	/**
	 * Issues an HTTP PUT request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	public function put($endpoint, $params = [])
	{
		return $this->request(self::PUT, $endpoint, $params);
	}

	/**
	 * Issues an HTTP PATCH request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	public function patch($endpoint, $params = [])
	{
		return $this->request(self::PATCH, $endpoint, $params);
	}

	/**
	 * Issues an HTTP DELETE request
	 *
	 * @param string $endpoint
	 * @param array $params
	 */
	public function delete($endpoint, $params = [])
	{
		return $this->request(self::DELETE, $endpoint, $params);
	}

	public function getZones(array $params = [])
	{
		return $this->get('/zones', $params);
	}

	public function getResultInfo()
	{
		return $this->resultInfo;
	}

	public function getZone($name)
	{
		$zones = $this->getZones([
			'name' => $name
		]);
		foreach ($zones as $zone) {
			if ($zone->name == $name) {
				return $zone;
			}
		}
		return null;
	}

	public function getZoneDnsRecords($zoneId, array $params = [])
	{
		return $this->get('/zones/' . $zoneId . '/dns_records', $params);
	}

	public function createDnsZone($name, array $params = [])
	{
		$params['name'] = $name;
		return $this->post('/zones', $params);
	}

	/**
	 * Creates a zone if it doesn't already exist.
	 *
	 * Returns information about the zone
	 */
	public function registerDnsZone($name, $params = [])
	{
		if ($res = $this->getZone($name)) {
			return $res;
		}
		return $this->createDnsZone($name, $params);
	}

	public function setDnsZoneSsl($zoneId, $type)
	{
		$allowedTypes = [
			'off',
			'flexible',
			'full',
			'full_strict'
		];

		if (! in_array($type, $allowedTypes)) {
			throw new Exception('SSL type not allowed. valid types are ' . join(', ', $allowedTypes));
		}

		return $this->patch('/zones/' . $zoneId . '/settings/ssl', [
			'value' => $type
		]);
	}

	public function setDnsZoneCache($zoneId, $type)
	{
		$allowedTypes = [
			'aggressive',
			'basic',
			'simplified',
		];

		if (! in_array($type, $allowedTypes)) {
			throw new Exception('Cache type not allowed. valid types are ' . join(', ', $allowedTypes));
		}

		return $this->patch('/zones/' . $zoneId . '/settings/cache_level', [
			'value' => $type
		]);
	}

	public function clearZoneCache($zoneId)
	{
		return $this->delete('/zones/' . $zoneId . '/purge_cache', [
			'purge_everything' => true
		]);
	}

	public function setDnsZoneMinify($zoneId, $settings)
	{
		return $this->patch('/zones/' . $zoneId . '/settings/minify', [
			'value' => $settings
		]);
	}

	public function createDnsRecord($zoneId, $type, $name, $content, array $params = [])
	{
		$params = array_merge($params, [
			'type' => $type,
			'name' => $name,
			'content' => $content,
		]);
		return $this->post('/zones/' . $zoneId . '/dns_records', $params);
	}

	public function updateDnsRecord($zoneId, $recordId, array $params = [])
	{
		return $this->put('/zones/' . $zoneId . '/dns_records/' . $recordId, $params);
	}

	public function deleteDnsRecord($zoneId, $recordId)
	{
		return $this->delete('/zones/' . $zoneId . '/dns_records/' . $recordId);
	}
}
