<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: dex.proto

namespace Binance;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>Binance.Commission</code>
 */
class Commission extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>int64 rate = 1;</code>
     */
    protected $rate = 0;
    /**
     * Generated from protobuf field <code>int64 max_rate = 2;</code>
     */
    protected $max_rate = 0;
    /**
     * Generated from protobuf field <code>int64 max_change_rate = 3;</code>
     */
    protected $max_change_rate = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $rate
     *     @type int|string $max_rate
     *     @type int|string $max_change_rate
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Dex::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>int64 rate = 1;</code>
     * @return int|string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Generated from protobuf field <code>int64 rate = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setRate($var)
    {
        GPBUtil::checkInt64($var);
        $this->rate = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 max_rate = 2;</code>
     * @return int|string
     */
    public function getMaxRate()
    {
        return $this->max_rate;
    }

    /**
     * Generated from protobuf field <code>int64 max_rate = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setMaxRate($var)
    {
        GPBUtil::checkInt64($var);
        $this->max_rate = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 max_change_rate = 3;</code>
     * @return int|string
     */
    public function getMaxChangeRate()
    {
        return $this->max_change_rate;
    }

    /**
     * Generated from protobuf field <code>int64 max_change_rate = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setMaxChangeRate($var)
    {
        GPBUtil::checkInt64($var);
        $this->max_change_rate = $var;

        return $this;
    }

}
