<?php

namespace Binance\Examples\ABCIQuery;

require '../../../vendor/autoload.php';

use Binance\RPC\RPC;
use Binance\Crypto\Address;

$server = "https://data-seed-pre-2-s1.binance.org";
$address = "tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r";


$rpc = new RPC();
$rpc->chooseNetwork("testnet");
$result = $rpc->GetTokenInfo($server, 'BNB');
var_dump($result);


?>