<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;
use Binance\Swap\Swap;

$privateKey = 'afccb5311dc836f631aa6e86c5f4ff53b4de8580b1bd2f7da79f1cba910e5bff';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->initChain();
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->setPrivateKey($privateKey);

$coins = array("denom" => "BNB", "amount" => 100);

$timestamp = time();

$bytes = random_bytes(32);
$randomNumberHash = bin2hex($bytes);


$swapClient = new Swap($bncClient);
$response = $swapClient->HTLT("tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r", "tbnb1hgm0p7khfk85zpz5v0j8wnej3a90w709zzlffd", "", "", $randomNumberHash, $timestamp, $coins, "0.01:USDT.B-B7C", 1000, false);

var_dump($response);
?>