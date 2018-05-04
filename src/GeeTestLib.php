<?php
/**
 * Created by PhpStorm.
 * User: wangl
 * Date: 2018/5/3
 * Time: 9:26
 */

namespace Swat\GeeTest;

class GeeTestLib
{
    const GT_SDK_VERSION = 'php_3.0.0';

    public static $connectTimeout = 1;

    public static $socketTimeout  = 1;

    private $response, $captchaId, $privateKey;

    public function __construct($captchaId, $privateKey)
    {
        $this->captchaId  = $captchaId;
        $this->privateKey = $privateKey;
    }

    /**
     * 判断极验服务器是否down机
     * @param array $param
     * @param int $newCaptcha
     * @return int
     */
    public function preProcess($param, $newCaptcha=1)
    {
        $data = array('gt'=>$this->captchaId,
            'new_captcha'=>$newCaptcha
        );

        $data = array_merge($data,$param);
        $query = http_build_query($data);
        $url = "http://api.geetest.com/register.php?" . $query;
        $challenge = $this->sendRequest($url);

        if (strlen($challenge) != 32) {
            $this->failBackProcess();
            return 0;
        }

        $this->successProcess($challenge);
        return 1;
    }


    /**
     * @param $challenge
     */
    private function successProcess($challenge)
    {
        $challenge      = md5($challenge . $this->privateKey);
        $result         = array(
            'success'   => 1,
            'gt'        => $this->captchaId,
            'challenge' => $challenge,
            'new_captcha'=>1
        );
        $this->response = $result;
    }


    private function failBackProcess()
    {
        $rnd1           = md5(rand(0, 100));
        $rnd2           = md5(rand(0, 100));
        $challenge      = $rnd1 . substr($rnd2, 0, 2);
        $result         = array(
            'success'   => 0,
            'gt'        => $this->captchaId,
            'challenge' => $challenge,
            'new_captcha'=>1
        );
        $this->response = $result;
    }

    /**
     * @return mixed
     */
    public function getResponseStr()
    {
        return json_encode($this->response);
    }
    /**
     * 返回数组方便扩展
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }


    /**
     * 正常模式获取验证结果
     * @param string $challenge
     * @param string $validate
     * @param string $secCode
     * @param array $param
     * @param int $jsonFormat
     * @return int
     */
    public function successValidate($challenge, $validate, $secCode, $param, $jsonFormat=1)
    {
        if (!$this->checkValidate($challenge, $validate)) {
            return 0;
        }

        $query = array(
            "seccode" => $secCode,
            "timestamp"=>time(),
            "challenge"=>$challenge,
            "captchaid"=>$this->captchaId,
            "json_format"=>$jsonFormat,
            "sdk"     => self::GT_SDK_VERSION
        );

        $query = array_merge($query,$param);
        $url = "http://api.geetest.com/validate.php";
        $codeValidate = $this->postRequest($url, $query);
        $obj = json_decode($codeValidate,true);

        if ($obj === false){
            return 0;
        }

        if ($obj['seccode'] == md5($secCode)) {
            return 1;
        } else {
            return 0;
        }
    }



    /**
     * 宕机模式获取验证结果
     * @param $challenge
     * @param $validate
     * @return int
     */
    public function failValidate($challenge, $validate)
    {
        if(md5($challenge) == $validate){
            return 1;
        } else{
            return 0;
        }
    }


    /**
     * @param $challenge
     * @param $validate
     * @return bool
     */
    private function checkValidate($challenge, $validate)
    {
        if (strlen($validate) != 32) {
            return false;
        }

        if (md5($this->privateKey . 'geetest' . $challenge) != $validate) {
            return false;
        }

        return true;
    }


    /**
     * GET 请求
     * @param $url
     * @return mixed|string
     */
    private function sendRequest($url)
    {
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            if ($curlErrno >0) {
                return 0;
            }else{
                return $data;
            }
        } else {
            $opts    = array(
                'http' => array(
                    'method'  => "GET",
                    'timeout' => self::$connectTimeout + self::$socketTimeout,
                )
            );
            $context = stream_context_create($opts);
            $data    = @file_get_contents($url, false, $context);
            if($data){
                return $data;
            }else{
                return 0;
            }
        }
    }


    /**
     * @param  string $url
     * @param  string $postData
     * @return mixed|string
     */
    private function postRequest($url, $postData = '')
    {
        if (!$postData) {
            return false;
        }
        $data = http_build_query($postData);
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);
            //不可能执行到的代码
            if (!$postData) {
                curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            } else {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                $err = sprintf("curl[%s] error[%s]", $url, curl_errno($ch) . ':' . curl_error($ch));
                $this->triggerError($err);
            }
            curl_close($ch);
        } else {
            if ($postData) {
                $opts    = array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($data) . "\r\n",
                        'content' => $data,
                        'timeout' => self::$connectTimeout + self::$socketTimeout
                    )
                );
                $context = stream_context_create($opts);
                $data    = file_get_contents($url, false, $context);
            }
        }
        return $data;
    }

    /**
     * @param $err
     */
    private function triggerError($err)
    {
        trigger_error($err);
    }
}