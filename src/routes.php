<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Swagger docs
$app->get('/', function (Request $request, Response $response, array $args) {
    $swaggerDocs = array();
    return $response->withJson($swaggerDocs);
});

// GET antminer details
$app->get('/antminer', function (Request $request, Response $response, array $args) {

    $r = $request->getQueryParams();

    $antminer = new Antminer($r['ip'],$r['pw']);



    $details = array();

    $details['ip'] = $antminer->getIp();
    $details['state'] = $antminer->getState();
    $details['summary'] = $antminer->getSummary();
    $details['pools'] = $antminer->getPools();
    $details['stats'] = $antminer->getStats();

    return $response->withJson($details);
});
