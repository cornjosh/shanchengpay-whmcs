<?php

/**
 * Created by PhpStorm.
 * Author: Josh Zeng (zylntxx)
 * Date: 2020/1/29
 * Time: 17:06
 */

require_once(__DIR__ . "/../class/shanchengpay/vendor/autoload.php");

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use ShanchengPay\ShanchengPay;

if ($_SERVER['REQUEST_METHOD'] == 'GET' ? true : false){
    // Is user check status request
    $data = $_GET;
    $invoiceId = (int)$data['id'];
    echo getInvoiceStatus($invoiceId);
}else{
    if (!count($_POST)) {
        $_POST = json_decode(file_get_contents('php://input'), true);
    }

    logModuleCall('shanchengpay', 'callback', $_POST,'', $_POST['out_trade_no']);
    $gateway = getGatewayVariables('shanchengpay');
    $sdk = new ShanchengPay($gateway['mch_id'], $gateway['key']);
    $resource = $_SERVER['REQUEST_URI'];

    return $sdk->callback($resource, $_POST, function () {
        $amount = convertAmount(getInvoiceID($_POST), $_POST['total_fee']);
        $invoiceId = checkCbInvoiceID(getInvoiceID($_POST), 'shanchengpay');
        checkCbTransID($_POST['out_trade_no']);
        logTransaction('shanchengpay', $_POST, "异步回调入账 #".$invoiceId);
        addInvoicePayment(
            $invoiceId,
            $_POST['out_trade_no'],
            $amount,
            0,
            'shanchengpay'
        );
        return 'SUCCESS';
    });
}

function getInvoiceStatus($id)
{
    if (!is_numeric($id)){
        $payment = null;
    }else{
        $payment = Capsule::table('tblinvoices')->where('id', $id)->first();
    }
    if (empty($payment)){
        return 'WHMCS - 023Pay 模块安装成功！';
    }else{
        return $payment->status;
    }
}

function getInvoiceID($data)
{
    return explode("-",$data['out_trade_no'])[1];
}

function convertAmount($invoiceId, $amount)
{
    // 将分转换成元
    $amount = $amount / 100;

    $setting = Capsule::table("tblpaymentgateways")->where("gateway","shanchengpay")->where("setting","convertto")->first();
    // 系统没多货币 , 直接返回
    if (empty($setting)){ return $amount; }


    // 获取用户ID 和 用户使用的货币ID
    $data = Capsule::table("tblinvoices")->where("id",$invoiceId)->get()[0];
    $userid = $data->userid;
    $currency = getCurrency($userid);

    // 返回转换后的
    return  convertCurrency($amount, $setting->value, $currency["id"] );
}