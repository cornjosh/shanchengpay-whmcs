<?php

/**
 * Created by PhpStorm.
 * Author: Josh Zeng (zylntxx)
 * Date: 2020/1/29
 * Time: 17:29
 */


class ShanChengPayConfig
{
    private $CONFIG;

    function __construct()
    {
        global $CONFIG;
        $this->CONFIG = $CONFIG;
        $isHttps = ((($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) || (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] ))) ? true : false;
        $isUrlHttps = stristr($this->CONFIG['SystemURL'], 'https');
        if ($isHttps xor $isUrlHttps){
            if ($isUrlHttps){
                $this->CONFIG['SystemURL'] = 'http'.substr($this->CONFIG['SystemURL'], 5);
            }else{
                $this->CONFIG['SystemURL'] = 'https'.substr($this->CONFIG['SystemURL'], 4);
            }
        }
    }

    public function getWechatConfig()
    {
        $wechatConfig = [
            'img' => [
                'FriendlyName' => '微信图标',
                'Type' => 'dropdown',
                'Options' => [
                    'zh_cn' => '简体中文',
                    'en' => 'English',
                    'hide' => '不显示图标',
                ],
                'Description' => '默认简体中文',
            ],
        ];
        return $this->getBaseConfig("微信支付") + $wechatConfig;
    }

    private function getBaseConfig($typeName)
    {
        return [
            'FriendlyName' => [
                'Type' => 'System',
                'Value' => '023Pay - '.$typeName,
            ],
            'author' => [
                'FriendlyName' => '',
                'Type' => 'dropdown',
                'Options' => [
                    'zylntxx' => "</option></select><div class='alert alert-success' role='alert' id='023pay_author_$typeName' style='margin-bottom: 0px;'>接口参数请在 <a href='https://shanchengpay.cn/' target='_blank'><span class='glyphicon glyphicon-new-window'></span>023Pay</a> 获取 ，支付模块文档请参阅 <a href='https://doc.shanchengpay.cn/api/' target='_blank'><span class='glyphicon glyphicon-new-window'></span>WHMCS - 023Pay模块</a><br>您的 WebHook 通知地址:<code>".$this->CONFIG['SystemURL'].'/modules/gateways/callback/shanchengpay.php'."</code>，点击进行 <a href='".$this->CONFIG['SystemURL'].'/modules/gateways/callback/shanchengpay.php'."' target='_blank'><span class='glyphicon glyphicon-new-window'></span>测试</a><br><span class='glyphicon glyphicon-ok'></span> 支持 WHMCS 5/6/7 , 当前WHMCS 版本 ".$this->CONFIG["Version"]."<br/><span class='glyphicon glyphicon-ok'></span> 仅支持 PHP 5.4 以上的环境 , 当前PHP版本 ".phpversion()."</div><script>$('#023pay_author_$typeName').prev().hide();</script><style>* {font-family: Microsoft YaHei Light , Microsoft YaHei}</style><select style='display:none'>"
                ]
            ],
            'mch_id' => [
                'FriendlyName' => 'Key ID',
                'Type' => 'text',
                'Size' => '64',
                'Description' => '[必填]',
            ],
            'key' => [
                'FriendlyName' => 'Key Secret',
                'Type' => 'text',
                'Size' => '64',
                'Description' => '[必填]',
            ],
        ];
    }
}