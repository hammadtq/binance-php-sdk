<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;
use Binance\Swap\Swap;

$privateKey = 'afccb5311dc836f631aa6e86c5f4ff53b4de8580b1bd2f7da79f1cba910e5bff';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->initChain();
$bncClient->setPrivateKey($privateKey);

$swapClient = new Swap($bncClient);
$response = $swapClient->claimHTLT("tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r", "00ccd50c9182aeeae64e1fd81362ef32e5140bbb22ad0a851cceb6709483ac5e", "2d120bb85c004c6f3b603218a59bc0e56b7e9a808f3debcd0502f23d43be4594");

var_dump($response);
?>