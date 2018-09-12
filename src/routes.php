<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Swagger docs
$app->get('/', function (Request $request, Response $response, array $args) {
    $swaggerDocs = array();
    return $response->withJson($swaggerDocs);
});

// GET antminer details
$app->get('/antminer', function (Request $request, Response $response, array $args) {

    $r = $request->getQueryParams();

    $l = array();
    $l['timestamp'] = date('c');
    $l['event'] = 'FETCH_ANTMINER_INFO';
    $l['ip'] = $r['ip'];
    $l['pw'] = $r['pw'];
    $l['type'] = $r['type'];

    $this->logger->info(json_encode($l));

    $antminer = new Antminer($r['ip'],$r['pw'], strtoupper($r['type']));

    $details = array();

    $details['timestamp'] = date('c');
    $details['ip'] = $antminer->getIp();
    $details['type'] = $antminer->getType();
    $details['config'] = $antminer->getConfig();
    $details['network'] = $antminer->getNetwork();
    $details['state'] = $antminer->getState();
    $details['summary'] = $antminer->getSummary();
    $details['pools'] = $antminer->getPools();
    $details['stats'] = $antminer->getStats();

    return $response->withJson($details);
});
