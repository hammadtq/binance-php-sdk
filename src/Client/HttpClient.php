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

    function SendPost($endpoint, $payload, $sync=false){

        $client = new GuzzleHttp\Client();
        
        $response = $client->postAsync($this->server.$endpoint.'?sync='.$sync, [
            'debug' => FALSE,
            'sync' => TRUE,
            'body' => $payload,
            'headers' => [
            'Content-Type' => 'text/plain',
            ]
        ])->wait(function($results){
            return $results;
        });
        
        $body = $response->getBody();
        return json_decode((string) $body);
    }

    // async function
    function GetAsync($endpoint){

        $client = new GuzzleHttp\Client();
        
        $response = $client->getAsync($this->server.$endpoint)->wait(function($results){
            return $results;
        });
        
        $body = $response->getBody();
        return json_decode((string) $body);
    }

    // sync function
    function GetSync($endpoint){

        $client = new GuzzleHttp\Client();
        
        $response = $client->getSync($this->server.$endpoint)->wait(function($results){
            return $results;
        });
        
        $body = $response->getBody();
        return json_decode((string) $body);
    }
}
?>