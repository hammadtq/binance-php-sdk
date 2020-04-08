<?php

namespace Binance\RPC;

use Binance\Crypto\Address;
use Binance\Utils\Request;
use Binance\TokenInfo;
use Binance\Exception;

class RPC {

    private $NETWORK_PREFIX_MAPPING;
    private $addressPrefix;

    function __construct() {
        $this->NETWORK_PREFIX_MAPPING = array('testnet' => 'tbnb', 'mainnet' => 'bnb'); 
    }

    /**
     * Sets the client network (testnet or mainnet).
     * @param {String} network Indicate testnet or mainnet
     */
    function chooseNetwork($network) {
        $this->addressPrefix = $this->NETWORK_PREFIX_MAPPING[$network];
    }

    /**
   * @param {String} symbol - required
   * @returns {Object} token detail info
   */
  function GetTokenInfo($server, $symbol) {

    $path = "/tokens/info/".$symbol;

    $request = new Request($server);
    $json = $request->AsyncRequest('abci_query', ['path'=>$path])->wait(function($results){
        return $results;
    });

    $decodedIn64 = base64_decode($json->result->response->value);
    $protoClass = new TokenInfo();
    $response = substr($decodedIn64, 5);
    $protoClass -> mergeFromString($response);
    $address = new Address();
    $bech32EncodedAddress = $address->EncodeAddress($protoClass -> getOwner(), $this->addressPrefix);
    $jsonArr = (array('name' => $protoClass -> getName(), 'symbol' => $protoClass -> getSymbol(), 'original_symbol' => $protoClass -> getOriginalSymbol(), 'total_supply'=> $protoClass -> getTotalSupply(), 'owner'=> $bech32EncodedAddress, 'mintable', $protoClass -> getMintable()));
    return json_encode($jsonArr);
  }

}