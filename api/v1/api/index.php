<?php
	require_once("../lib/env.php");

	require_once("http.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			$data = httpParseInput();
			if (!$data or !isset($data['action']))
				httpResponse(400, array('message' => 'action required'));

			switch ($data['action']) {
				case 'check':
					if (!isset($data['api key']))
						httpResponse(400, array('message' => 'api key required'));

					$apikey = $dbDriver->getApiKeyByKey($data['api key']);
					if ($apikey === null)
						httpResponse(500, array('message' => 'Query failure'));
					elseif ($apikey === false)
						httpResponse(401, array('message' => 'Invalid API key'));
					else
						httpResponse(200, array('message' => 'Valid API key'));
			}

		case 'OPTIONS':
			httpOptionsMethod(HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
