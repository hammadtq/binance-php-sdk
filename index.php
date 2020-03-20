<?php

namespace Binance;

require 'vendor/autoload.php';
//require 'Hash.php';
//require 'Bech32.php';
//require 'Keystore.php';

use Binance\Bech32;
use Binance\Keystore;
use Binance\Hash;
use Binance\Bech32Exception;
use Binance\Types\Buffer;
use BitWasp\Buffertools\Parser;

//use Transaction\AppAccount;

//use GuzzleHttp\Client;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\Point\CompressedPointSerializer;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;

new Bech32Exception();

// $client = new GuzzleHttp\Client();
// $res = $client->get('https://testnet-dex.binance.org/api/v1/time', [
//     'auth' =>  ['user', 'pass']
// ]);

// echo $res->getBody();                 // {"type":"User"...'

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

// $keystoreData = '{"version":1,"id":"71f30a47-0703-43e6-a1cd-dac418d2c823","crypto":{"ciphertext":"9c7e442bc468135e2c944aa1e01e4664fc40daee5776148793b6d3501943c16a","cipherparams":{"iv":"ee6b58d56c9b6fe15151b069044c3379"},"cipher":"aes-256-ctr","kdf":"pbkdf2","kdfparams":{"dklen":32,"salt":"fa17051327e6c1db5bca494324c7cacd6f9e1b3b9f8a9ba8274fe7896483d4fb","c":262144,"prf":"hmac-sha256"},"mac":"35fb27571b9666e75fa49fddd67203853d0564c29e9e45de3369b4eac9f2d09a5cfee63c83cba4096f1c5266047f53aac63301f6107582528ebea5f777e619c0"}}';

// $keystore = new Keystore($keystoreData, "Abc123456@");

// var_dump($keystore);
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

use Graze\GuzzleHttp\JsonRpc\Client;

// Create the client
$client = Client::factory('https://data-seed-pre-2-s1.binance.org');

// Send a notification
// $client->send($client->notification('method', ['key'=>'value']));

// Send a request that expects a response
$res = $client->send($client->request(123, 'abci_query', ['path'=>'/account/tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r']));

$json = json_decode($res->getBody());

var_dump($json->result->response->value);

//$decoded = bin2hex(base64_decode($json->result->response->value));

$decodedIn64 = base64_decode($json->result->response->value);

$buffer = new Buffer($json->result->response->value);
$parser = new Parser($buffer);

$protoClass = new AppAccount();

$response = substr($decodedIn64, 4);

$protoClass -> mergeFromString($response);

// WE HAVE DECODED THE MESSAGE
// NOW ENCODE THE ADDRESS IN BECH32
$base = $protoClass -> getBase() -> getAddress();

var_dump(bin2hex($base));

$chars = array_values(unpack('C*', $base));

$bech32 = new Bech32();
$convertedBits = $bech32->convertBits($chars, count($chars), 8, 5, true);
echo "<br/>";
$bech32EncodedAddress = $bech32->encode("tbnb", $convertedBits);

echo $bech32EncodedAddress;


// NOW DECODE FROM BECH32
list ($gotHRP, $data) = $bech32->decode($bech32EncodedAddress);
$convertedBitsAgain = $bech32->convertBits($data, count($data), 5, 8, true);
$chars = array_map("chr", $convertedBitsAgain);
$bin = join($chars);
$hex = bin2hex($bin);
var_dump($hex);


?>