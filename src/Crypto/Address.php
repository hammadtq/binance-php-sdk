<?php

namespace Binance\Crypto;

use Binance\Crypto\Bech32;

class Address {

    function DecodeAddress($bech32EncodedAddress){
        $bech32 = new Bech32();
        list ($gotHRP, $data) = $bech32->decode($bech32EncodedAddress);
        $convertedBitsAgain = $bech32->convertBits($data, count($data), 5, 8, true);
        $chars = array_map("chr", $convertedBitsAgain);
        $bin = join($chars);
        $hex = bin2hex($bin);
        return $hex;
    }

    function EncodeAddress($rawAddress, $prefix){
        $chars = array_values(unpack('C*', $rawAddress));
        $bech32 = new Bech32();
        $convertedBits = $bech32->convertBits($chars, count($chars), 8, 5, true);
        $bech32EncodedAddress = $bech32->encode($prefix, $convertedBits);
        return $bech32EncodedAddress;
    }

}