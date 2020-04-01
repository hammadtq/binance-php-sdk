<?php

namespace Binance\Client;

use GuzzleHttp;

class HttpClient {

    private $server;

    function __construct($network) {
        if ($network == "mainnet"){
            $this->server = 'https://dex.binance.org';
        }else if($network == "testnet"){
            $this->server = 'https://'.$network.'-dex.binance.org';
        }else{
            throw Exception("wrong network");
        }
    }

    function SendPost($endpoint, $payload){

        $client = new GuzzleHttp\Client();
        
        $response = $client->post($this->server.$endpoint, [
            'debug' => FALSE,
            'body' => $payload,
            'headers' => [
            'Content-Type' => 'text/plain',
            ]
        ]);
        
        $body = $response->getBody();
        return json_decode((string) $body);
    }
}
?>