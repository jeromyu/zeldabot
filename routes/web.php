<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('weebhook', function(){
    return "Congratulations for install ZeldaBot!";
});

$app->group(['middleware' => 'verify.token'], function () use ($app) {
	$app->post('/webhook', 'BotController@webhook');
	$app->post('/addlink', 'BotController@addlink');
	$app->post('/mylinks', 'BotController@myLinks');
	$app->post('/favorites', 'BotController@favorites');
	$app->post('/all', 'BotController@all');
	$app->post('/preferences', 'BotController@addPreferences');
	$app->post('/recommendations', 'BotController@getRecommendations');
});

$app->post('/add-favorite', 'BotController@favoriteButtonAction');