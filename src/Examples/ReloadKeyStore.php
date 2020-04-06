<?php

namespace Binance\Examples\Transactions;

require '../../vendor/autoload.php';

use Binance\Crypto\Keystore;
use Binance\Client\BncClient;

//$keystoreData = '{"version":1,"id":"71f30a47-0703-43e6-a1cd-dac418d2c823","crypto":{"ciphertext":"9c7e442bc468135e2c944aa1e01e4664fc40daee5776148793b6d3501943c16a","cipherparams":{"iv":"ee6b58d56c9b6fe15151b069044c3379"},"cipher":"aes-256-ctr","kdf":"pbkdf2","kdfparams":{"dklen":32,"salt":"fa17051327e6c1db5bca494324c7cacd6f9e1b3b9f8a9ba8274fe7896483d4fb","c":262144,"prf":"hmac-sha256"},"mac":"35fb27571b9666e75fa49fddd67203853d0564c29e9e45de3369b4eac9f2d09a5cfee63c83cba4096f1c5266047f53aac63301f6107582528ebea5f777e619c0"}}';
$keystoreData = '{"version":1,"id":"dae262bb-179d-4e59-afcd-c3ea84052c68","crypto":{"ciphertext":"a43b834c67a13a91b58e729a12ce0b24f1de777670060ea0ad0345d162feaa75","cipherparams":{"iv":"a79c60fa198c5b36f621ad07e9117883"},"cipher":"aes-256-ctr","kdf":"pbkdf2","kdfparams":{"dklen":32,"salt":"4482f094686271c684aa2616cf0b7164999429640153cfc2ce416d652cd94c3c","c":262144,"prf":"hmac-sha256"},"mac":"4b6ad87e66b0726d92d6462e8896900a0e25d2e7d05e40222f2696771769da47ac800e8242d8c7314e77d625b704a054679f844964e4bbad12bc93247a5cabb6"}}';
$keystore= new Keystore();
$keystore->RestoreKeyStore($keystoreData, "Abc123456@", "tbnb");
$privateKey = $keystore->getPrivateKey(); 
$publicKey = $keystore->createPublicKey($privateKey);
$address = $keystore->publicKeyToAddress($publicKey, 'tbnb');

var_dump($privateKey);
var_dump($publicKey);
var_dump($address);

?>