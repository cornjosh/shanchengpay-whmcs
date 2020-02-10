<?php

/**
 * Created by PhpStorm.
 * Author: Josh Zeng (zylntxx)
 * Date: 2020/1/29
 * Time: 17:30
 */

require_once(__DIR__ . "/vendor/autoload.php");

use ShanchengPay\ShanchengPay;
use ShanchengPay\Payment;

class ShanChengPayLink
{
    private $CONFIG;

    function __construct()
    {
        global $CONFIG;
        $this->CONFIG = $CONFIG;
    }

    public function getLink($params)
    {
        if (!empty($this->getSetUpStatus($params))){
            return $this->getSetUpStatus($params);
        }

        if (!$this->isInvoice()){
            return $this->getWechatLogo($params);
        }

        return $this->getWechatLink($params);
    }

    private function getWechatLink($params)
    {
        try{
            $sdk = new ShanchengPay($params['mch_id'], $params['key']);
            $pay = new Payment();
            $pay->out_trade_no = $params['companyname']."-".$params['invoiceid']."-".uniqid();
            $pay->total_fee = $params['amount'] * 100;
            $pay->body = $params['companyname']."订单 [# ".$params['invoiceid']." ]";
            $pay->trade_type = 'NATIVE';
            $pay->notify_url = $this->CONFIG['SystemURL'].'/modules/gateways/callback/shanchengpay.php';


            $response = $sdk->pay($pay);
            $result['qrcode'] = $response->request_data->code_url;

        }catch (Exception $exception){
            return $this->getErrorHttp($exception->getMessage());
        }

        return $this->getJavaScript($params).$this->getWechatLogo($params, "middle").$this->getWechatHttp($result);
    }

    private function getWechatHttp($result)
    {
        if ($this->isMobile()){
            return $this->getWechatMobile($result);
        }else{
            return $this->getWechatDesktop($result);
        }
    }

    /**
     * 获取错误 最终Http返回
     *
     * @param $message
     * @return string
     */
    private function getErrorHttp($message)
    {
        return '订单创建失败！请联系管理员或重试<br>错误信息：<code>'.$message."</code>";
    }

    /**
     * 获取微信Logo
     *
     * @param $params
     * @param string $size
     * @return string
     */
    private function getWechatLogo($params, $size = "big")
    {
        if ($params['img'] == 'hide'){
            return '';
        }

        $height = 9;
        if ($size != "big"){
            $height = 4;
        }

        $imgLang = $params['img'];
        $img = '/modules/gateways/shanchengpay/assets/images/WeChat/WeChat_big_'.$imgLang.'.png';
        return "<img class = 'center-block' src='".$img."' alt='欢迎使用 微信支付' style=\"height: ".$height."em;\"/>";
    }

    /**
     * 获取 微信桌面Http
     *
     * @param $result
     * @return mixed
     */
    private function getWechatDesktop($result)
    {
        $status = '<div id="qrcode" style="display: flex; justify-content: center; text-align: center;"></div><p class="text-center">请使用微信扫码支付</p><script>
window.onload = function() {
  $("#qrcode").qrcode({
  render: "canvas",
  width: 150,
  height: 150,
  text: "{$qr_code}"
  });
}
</script>';
        $status_raw = str_replace('{$qr_code}', $result['qrcode'], $status);
        return $status_raw;
    }

    /**
     * 获取 微信手机端Http
     *
     * @param $result
     * @return mixed
     */
    private function getWechatMobile($result)
    {
        $status = '<div id="qrcode"></div><br><p>请截图后使用微信相册识别支付</p><script>
window.onload = function() {
  $("#qrcode").qrcode({
  render: "canvas",
  width: 150,
  height: 150,
  text: "{$qr_code}"
  });
}
</script>';
        $status_raw = str_replace('{$qr_code}', $result['qrcode'], $status);
        return $status_raw;
    }

    /**
     * 获取通用的 JavaScript
     *
     * @param $params
     * @return mixed
     */
    private function getJavaScript($params)
    {
        $invoiceId = $params['invoiceid'];
        $checkUrl = '/modules/gateways/callback/shanchengpay.php?id='.$invoiceId;
        $javaScript = '<script src="https://cdn.staticfile.org/jquery/3.4.1/jquery.min.js"></script>
            <script src="https://cdn.staticfile.org/sweetalert/2.1.2/sweetalert.min.js"></script>
            <script src="https://cdn.staticfile.org/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
			<script>
			setTimeout(stop, 300000);
			function stop()
			{
				clearInterval(paid_timeout);
			}
			
			var paid_timeout = setInterval(go, 3000);
			function go()
			{
				$.get("{$url}",function(data)
					{
						if (data == "Paid")
						{
							clearInterval(paid_timeout);
							swal ({
							    icon:"success",
							    title:"支付完成",
							    text:"5秒后跳转至完成界面",
							    bottons:false,
							    timer:5000,
							})
							.then((value) => {
							    location.reload();
							});
						}
					}
				);
			}
			</script>';
        $javaScriptRaw = str_replace('{$url}', $checkUrl, $javaScript);
        return $javaScriptRaw;
    }

    /**
     * 判断运行环境
     *
     * @param $params
     * @return string
     */
    private function getSetUpStatus($params)
    {
        if (!function_exists("openssl_open")){
            return "PHP配置异常！openssl组件未开启";
        }

        if (empty($params['mch_id'])){
            return "接口异常！管理员未设置商家号";
        }

        if (empty($params['key'])){
            return "接口异常！管理员未设置通信秘钥";
        }

        return "";
    }

    /**
     * 获取设置
     *
     * @param $params
     * @return array
     */
    private function getConfig($params)
    {
        return [
            'id' => $params['mch_id'],
            'secret' => $params['key']
        ];
    }

    /**
     * 获取订单设置
     *
     * @param $params
     * @param string $attach
     * @return array
     */
    private function getOrderData($params, $attach = "")
    {
        return [
            'total_fee' => $params['amount'] * 100,
            'out_trade_no' => $params['companyname']."-".$params['invoiceid']."-".uniqid(),
            'body' => $params['companyname']."订单 [# ".$params['invoiceid']." ]",
            'attach' => $attach,
        ];
    }

    /**
     * 判断是否是移动端
     *
     * @return bool
     */
    private function isMobile(){
        $useragent=$_SERVER['HTTP_USER_AGENT'];
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
            return true;
        }
        return false;
    }

    /**
     * 判断是否到达账单界面
     *
     * @return bool
     */
    private function isInvoice()
    {
        if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
            return false;
        }else{
            return true;
        }
    }
}