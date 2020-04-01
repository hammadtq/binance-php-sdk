<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Crypto\Keystore;
use Binance\Client\BncClient;

$keystoreData = '{"version":1,"id":"71f30a47-0703-43e6-a1cd-dac418d2c823","crypto":{"ciphertext":"9c7e442bc468135e2c944aa1e01e4664fc40daee5776148793b6d3501943c16a","cipherparams":{"iv":"ee6b58d56c9b6fe15151b069044c3379"},"cipher":"aes-256-ctr","kdf":"pbkdf2","kdfparams":{"dklen":32,"salt":"fa17051327e6c1db5bca494324c7cacd6f9e1b3b9f8a9ba8274fe7896483d4fb","c":262144,"prf":"hmac-sha256"},"mac":"35fb27571b9666e75fa49fddd67203853d0564c29e9e45de3369b4eac9f2d09a5cfee63c83cba4096f1c5266047f53aac63301f6107582528ebea5f777e619c0"}}';
$keystore= new Keystore();
$keystore->RestoreKeyStore($keystoreData, "Abc123456@", "tbnb");
$privateKey = $keystore->getPrivateKey();   
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->initChain();
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->setPrivateKey($privateKey);

$response = $bncClient->NewOrder("BNB_USDT.B-B7C", 1, 0.001, 1, 0, 1);
var_dump($response);
?>