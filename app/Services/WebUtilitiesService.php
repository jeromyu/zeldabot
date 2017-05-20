<?php

namespace App\Services;

use Curl\Curl;

class WebUtilitiesService
{
	public function performCurlAction($url, $method, $data = [], $headers = [], $cookies = [])
	{
		$curl = new Curl();
		if (!empty($headers)) {
			foreach ($headers as $key => $header) {
				$curl->setHeader($key, $header);
			}
		}

		if (!empty($cookies)) {
			foreach ($cookies as $key => $cookie) {
				$curl->setHeader($key, $cookie);
			}
		}

		$curl->{$method}($url, $data);

		return $curl->error ? $curl->error_code : $curl->response;
	}

	public function scrapWeb($url)
	{
		return file_get_contents($url);
	}
}