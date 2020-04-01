<?php

namespace Binance;

require 'vendor/autoload.php';
//require 'Hash.php';
//require 'Bech32.php';
//require 'Keystore.php';

use Binance\Crypto\Bech32;
use Binance\Crypto\Address;
use Binance\Crypto\Keystore;
use Binance\Hash;
use Binance\Bech32Exception;
use Binance\Types\Buffer;
use BitWasp\Buffertools\Parser;

use Binance\Client\BncClient;

//use Transaction\AppAccount;

use GuzzleHttp;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\Point\CompressedPointSerializer;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;

use Brick\Math\BigDecimal;

new Bech32Exception();

// $client = new GuzzleHttp\Client();
// $res = $client->get('https://testnet-dex.binance.org/api/v1/node-info');

//echo $res->getBody();                 // {"type":"User"...'

    // $response = json_decode($res->getBody(), true);
    // var_dump($response["node_info"]["network"]);

// $adapter = EccFactory::getAdapter();
// $generator = EccFactory::getSecgCurves()->generator256k1();
// $private = $generator->createPrivateKey();

// $public = $private->getPublicKey();

// $point = $private->getPoint();
// var_dump($private);

// $compressingSerializer = new CompressedPointSerializer($adapter);
// $compressed = $compressingSerializer->serialize($public->getPoint());
// $parsed = $compressingSerializer->unserialize($generator->getCurve(), $compressed);

// echo $compressed;

// echo "<br/>";

// $sha256 = hash('sha256', $compressed);
// $ripemd60 = hash('ripemd160', $sha256);
// echo $ripemd60;


// $chars = array_values(unpack('C*', $ripemd60));

// $convertedBits = convertBits(array_slice($chars, 1), count($chars) - 1, 8, 5, true);
// echo "<br/>";
// $bech32EncodedAddress = encode("testbnb", $convertedBits);

// echo $bech32EncodedAddress;

$keystoreData = '{"version":1,"id":"71f30a47-0703-43e6-a1cd-dac418d2c823","crypto":{"ciphertext":"9c7e442bc468135e2c944aa1e01e4664fc40daee5776148793b6d3501943c16a","cipherparams":{"iv":"ee6b58d56c9b6fe15151b069044c3379"},"cipher":"aes-256-ctr","kdf":"pbkdf2","kdfparams":{"dklen":32,"salt":"fa17051327e6c1db5bca494324c7cacd6f9e1b3b9f8a9ba8274fe7896483d4fb","c":262144,"prf":"hmac-sha256"},"mac":"35fb27571b9666e75fa49fddd67203853d0564c29e9e45de3369b4eac9f2d09a5cfee63c83cba4096f1c5266047f53aac63301f6107582528ebea5f777e619c0"}}';

$keystore= new Keystore();
$keystore->RestoreKeyStore($keystoreData, "Abc123456@", "tbnb");

var_dump($keystore->getPrivateKey()->getHex());
$privateKey = $keystore->getPrivateKey();
// $der = $derSerializer->serialize($private);
// echo sprintf("DER encoding:\n%s\n\n", base64_encode($der));

// $derSerializer = new DerPublicKeySerializer($adapter);
// $pemSerializer = new PemPublicKeySerializer($derSerializer);
// $serialized = $pemSerializer->serialize($public);
// $key = $pemSerializer->parse($serialized);
// //var_dump($key);
// echo sprintf("PEM PublicKey encoding:\n%s\n\n", $serialized);

// $derSerializer = new DerPrivateKeySerializer($adapter);

// $pemSerializer = new PemPrivateKeySerializer($derSerializer);
// $pem = $pemSerializer->serialize($private);
// echo sprintf("PEM encoding:\n%s\n\n", $pem);

//echo Hash::sha256ripe160($public->getBuffer())

// use Graze\GuzzleHttp\JsonRpc\Client;
// use Psr\Http\Message\ResponseInterface;
// use GuzzleHttp\Exception\RequestException;

// // Create the client
// $client = Client::factory('https://data-seed-pre-2-s1.binance.org');

// Send a notification
// $client->send($client->notification('method', ['key'=>'value']));

// Send a request that expects a response
//$promise = $client->sendAsync($client->request(123, 'abci_query', ['path'=>'/account/tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r']));

//$promise->wait();

//var_dump($res);

//$promise->then(
    //function (ResponseInterface $res) {

// use Binance\Utils\Request;

// $request = new Request('https://data-seed-pre-2-s1.binance.org');
// $json = $request->AsyncRequest('abci_query', ['path'=>'/account/tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r'])->wait(function($results){
//     return $results;
// });;

// var_dump($json);

// $decodedIn64 = base64_decode($json->result->response->value);

// $protoClass = new AppAccount();

// $response = substr($decodedIn64, 4);

// $protoClass -> mergeFromString($response);

// // WE HAVE DECODED THE MESSAGE
// // NOW ENCODE THE ADDRESS IN BECH32
// $rawAddress = $protoClass -> getBase() -> getAddress();

// $sequence = $protoClass -> getBase() -> getSequence();

// var_dump("sequence".$sequence);

// // NOW DECODE FROM BECH32
// $address = new Address();

// $bech32EncodedAddress = $address->EncodeAddress($rawAddress);
// var_dump($bech32EncodedAddress);

// $rawAddress = $address->DecodeAddress($bech32EncodedAddress);
// var_dump($rawAddress);


$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');

$bncClient->initChain();

$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"

$bncClient->setPrivateKey($privateKey);

//$response = $bncClient->transfer("tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r", "tbnb1hgm0p7khfk85zpz5v0j8wnej3a90w709zzlffd", 0.001, "BNB", "3423423");

$response = $bncClient->NewOrder("BNB_USDT.B-B7C", 1, 0.001, 1, 0, 1);
var_dump($response);
?>