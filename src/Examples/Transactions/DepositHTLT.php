<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;
use Binance\Swap\Swap;

$privateKey = 'd84899c0012ebc48b50b9ea93b9c8d911078f11530d08fb271769b7e8124d1ae';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->initChain();
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->setPrivateKey($privateKey);

$coins = array("denom" => "BNB", "amount" => 100);

$timestamp = time();

$bytes = random_bytes(32);
$randomNumberHash = bin2hex($bytes);


$swapClient = new Swap($bncClient);
$response = $swapClient->depositHTLT("tbnb1upk2usj02frqhhw9c23789vd027awyzyl2mfpg", "593868f7392b40e905370deb67e4a15031692403cef43089f7b1eb9a965d0d1e", $coins);

var_dump($response);
?>