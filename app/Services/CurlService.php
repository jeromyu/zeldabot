<?php

namespace App\Services;

use Curl\Curl;

class CurlService
{
	public function performAction($url, $method, $data = [], $headers = [], $cookies = [])
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
}