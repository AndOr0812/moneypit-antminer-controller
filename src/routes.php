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

    $this->logger->info(json_encode(array('timestamp' => date('c'), 'event' => 'FETCH_ANTMINER_INFO', 'ip' => $r['ip'], 'pw' => $r['pw'], 'type' => $r['type'])));

    $antminer = new Antminer($r['ip'],$r['pw'], strtoupper($r['type']));

    $details = array(
      'timestamp'   => date('c'),
      'ip'          => $antminer->getIp(),
      'type'        => $antminer->getType(),
      'state'       => $antminer->getState(),
      'network'     => $antminer->getNetwork(),
      'config'      => $antminer->getConfig(),
      'summary'     => $antminer->getSummary(),
      'pools'       => $antminer->getPools(),
      'stats'       => $antminer->getStats()
    );
    return $response->withJson($details);
});
