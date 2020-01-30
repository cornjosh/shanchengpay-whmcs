<?php

/**
 * Created by PhpStorm.
 * Author: Josh Zeng (zylntxx)
 * Date: 2020/1/29
 * Time: 17:06
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function shanchengpay_MetaData()
{
    return [
        'DisplayName' => '山城支付 - 微信支付',
        'APIVersion' => '1.1'
    ];
}

function shanchengpay_config()
{
    require_once __DIR__ . '/class/shanchengpay/ShanChengPayConfig.php';
    $config = new ShanChengPayConfig();
    return $config->getWechatConfig();
}

function shanchengpay_link($params)
{
    require_once __DIR__ . '/class/shanchengpay/ShanChengPayLink.php';
    $link = new ShanChengPayLink();
    return $link->getLink($params);
}