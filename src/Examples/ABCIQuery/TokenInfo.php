<?php

namespace Binance\Examples\ABCIQuery;

require '../../../vendor/autoload.php';

use Binance\RPC\RPC;
use Binance\Crypto\Address;

$server = "https://data-seed-pre-2-s1.binance.org";

$rpc = new RPC();
$rpc->chooseNetwork("testnet");
$result = $rpc->GetTokenInfo($server, 'BNB');
var_dump($result);


?>