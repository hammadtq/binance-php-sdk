<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Client\AbciRequest;
use Binance\Crypto\Address;

$server = "https://data-seed-pre-2-s1.binance.org";
$address = "tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r";


$request = new AbciRequest();
$result = $request->GetAppAccount($server, $address);
$sequence = $result->getBase()->getSequence();
echo "<p>Sequence: ".$sequence."</p>";

// WE HAVE DECODED THE MESSAGE
// NOW ENCODE THE ADDRESS IN BECH32
$rawAddress = $result -> getBase() -> getAddress();

$address = new Address();
$bech32EncodedAddress = $address->EncodeAddress($rawAddress, "tbnb");
echo "<p>Bech32 Encoded Address: ".$bech32EncodedAddress."</p>";


$DecodedAddress = $address->DecodeAddress($bech32EncodedAddress);
echo "<p>Bech32 Decoded Address: ".$DecodedAddress."</p>";


?>