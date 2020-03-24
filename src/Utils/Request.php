<?php

namespace Binance\Utils;

use Graze\GuzzleHttp\JsonRpc\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Request {

    public $client;

    function __construct($server){
        $this->client = Client::factory($server);
    }

    function AsyncRequest($method, $params){
        $promise = $this->client->sendAsync($this->client->request(123, $method, $params));

        return $promise->then(function($results) {
            return json_decode($results->getBody());
        });
    }

}


?>
