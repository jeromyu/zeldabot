<?php

namespace App\Services;

class DataFormatService
{
	protected $exceptions = ['$'];

	public function getExceptions()
	{
		return $exceptions;
	}

	public function escapeContent($string)
	{
		for ($i=0; $i < count($this->exceptions); $i++) { 
			$string = str_replace($this->exceptions[$i], '\\' . $this->exceptions[$i], $string);
		}

		return $string;
	}

	public function getLinkAndTags($command)
	{
		$link = preg_replace('/\|.*/', '', strtok($command, "\<\>\$ "));
		$link = explode('?', $link)[0];
		$tok = $link;
		$tags = [];
		while ($tok !== false) {
			$tok = strtok("\<\> ");

			if ($tok) {
				$tags[] = $tok;
			}
		}

		$data = [
			'link' => $link,
			'tags' => $tags
		];

		return $data;
	}

	public function getTags($command_text)
	{
		$arr = explode(" ", preg_replace('/\s+/', " ", $command_text));

		array_walk($arr, function($element, $key) use (&$arr){
			if (!$element) {
				unset($arr[$key]);
			}
		});

		return $arr;
	}
}