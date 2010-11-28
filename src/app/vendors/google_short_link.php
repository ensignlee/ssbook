<?php

/**
 * Create shortlinks through an API from go.indeed.com
 */
class GoogleShortLink {

	private $user;
	private $host;
	private $secret;

	const API_METHOD_HASH = 'get_or_create_hash';
	const API_METHOD_SHORTLINK = 'get_or_create_shortlink';

	/**
	 * @param $user Username the short link is assoctiated to
	 * @param $host Host name
	 * @param $secret Secret
	 */
	public function __construct($user, $host, $secret) {
		$this->user = $user;
		$this->host = $host;
		$this->secret = $secret;
	}

	/**
	 * Build a base64 encoded string from the content
	 */
	private function getEncodedHmac($content) {
		$raw = hash_hmac('sha1', $content, $this->secret, true);
		return base64_encode($raw);
	}

	/**
	 * Create a link, auto generate the hash
	 */
	public function createHashedShortLink($url, $public = true) {
		return $this->createLink(self::API_METHOD_HASH, null, $url, $public);
	}

	/**
	 * Create a link, specify the hash
	 */
	public function createShortLink($name, $url, $public = true) {
		return $this->createLink(self::API_METHOD_SHORTLINK, $name, $url, $public);
	}

	/**
	 * Generate the correct go.indeed.com url and paramaters, then curl get the page
	 * and
	 * @return string $short_url
	 */
	private function createLink($apiMethod, $name, $url, $public) {
		$requestUrl = $this->buildRequestUrl($apiMethod, $name, $url, $public);
		$raw_json = curl_file_get_contents($requestUrl);
		$json = json_decode($raw_json);
		if ($json->status == 'ok') {
			return $this->host.'/'.$json->shortcut;
		} else {
			return false;
		}
	}

	/**
	 * @param $apiMethod api method
	 * @param $name to give the short url
	 * @param $url to create from the stort url
	 * @param boolean $public or not
	 * @param $time mainly used for debugging, defaults to now
	 */
	public function buildRequestUrl($apiMethod, $name, $url, $public, $time = false) {
		if (empty($url)) {
			throw new Exception('Url cannot be empty');
		}
		if (empty($this->user)) {
			throw new Exception('User cannot be empty');
		}
		if (empty($time)) {
			$time = time();
		}

		$requestPath = $this->host.'/js/'.$apiMethod;
		$params = array(
			'is_public' => ($public) ? 'true' : 'false',
			'oauth_signature_method' => 'HMAC-SHA1',
			'timestamp' => $time.'.0',
			'url' => $url,
			'user' => $this->user
		);
		if (!empty($name)) {
			$params['shortcut'] = $name;
		}

		// http_build_query encode the paramas, but then the API expects the params to then be
		// url encoded all together
		$content = 'GET&'.urlencode($requestPath).'&'.urlencode(http_build_query($params));
		$params['oauth_signature'] = $this->getEncodedHmac($content);

		$requestUrl = $requestPath.'?'.http_build_query($params);
		return $requestUrl;
	}

}