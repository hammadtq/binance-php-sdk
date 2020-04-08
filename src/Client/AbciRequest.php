<?php

namespace Binance\Client;

use Binance\Utils\Request;
use Binance\AppAccount;

class AbciRequest{

    function GetAppAccount($server, $address){

        $request = new Request($server);
        $json = $request->AsyncRequest('abci_query', ['path'=>'/account/'.$address])->wait(function($results){
            return $results;
        });

        $decodedIn64 = base64_decode($json->result->response->value);
        $protoClass = new AppAccount();
        $response = substr($decodedIn64, 4);
        $protoClass -> mergeFromString($response);

        return $protoClass;
    }

}
?>