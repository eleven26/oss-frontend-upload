<?php

namespace Eleven26\Oss;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Config;

class UploadService
{
    /**
     * 阿里云 OSS AccessKey ID
     *
     * @var string
     */
    private $accessKeyId;

    /**
     * 阿里云 OSS AccessKey Secret
     *
     * @var string
     */
    private $accessKeySecret;

    /**
     * 前端上传时候的 url
     *
     * @var string
     */
    private $uploadUrl;

    /**
     * 上传回调地址
     *
     * @var string
     */
    private $callback;

    /**
     * 上传文件 key 的前缀
     *
     * @var string
     */
    private $dir;

    /**
     * policy 过期时间
     *
     * @var int
     */
    private $expires;

    /**
     * 上传文件大小限制
     *
     * @var int
     */
    private $maxsize;

    /**
     * UploadService constructor.
     */
    public function __construct()
    {
        $config = Config::get('oss-upload');

        $this->accessKeyId = $config['access_key_id'];
        $this->accessKeySecret = $config['access_key_secret'];
        $this->uploadUrl = $config['upload_url'];
        $this->callback = $config['callback'];
        $this->dir = $config['dir'];
        $this->expires = $config['expires'] ?: 10;
        $this->maxsize = $config['maxsize'];
    }

    /**
     * 返回前端上传需要用到的参数
     *
     * @return array
     * @throws Exception
     */
    public function getPolicy()
    {
        $key = $this->accessKeySecret;

        $now = time();
        $end = $now + $this->expires;
        $expiration = $this->gmtISO8601($end);

        $arr = ['expiration' => $expiration, 'conditions' => $this->conditions()];
        $policy = json_encode($arr);
        $base64Policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64Policy, $key, true));

        $response = [];
        $response['access_id'] = $this->accessKeyId;
        $response['url'] = $this->uploadUrl;
        $response['policy'] = $base64Policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $this->base64Callback();
        $response['dir'] = $this->dir;

        return $response;
    }

    /**
     * base64 编码的回调参数
     *
     * @return string
     */
    private function base64Callback()
    {
        if (!$this->callback) {
            return '';
        }

        // callbackBody 里面的参数是回调的时候，阿里云 oss 服务器给我们的参数
        $callbackParam = [
            'callbackUrl' => $this->callback,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];
        $callbackString = json_encode($callbackParam);

        return base64_encode($callbackString);
    }

    /**
     * 1、上传文件大小限制
     * 2、上传路径限制
     *
     * @return array
     */
    private function conditions()
    {
        $conditions = [];

        // 最大文件大小.用户可以自己设置
        if ($this->maxsize) {
            $condition = [0 => 'content-length-range', 1 => 0, 2 => $this->maxsize];
            $conditions[] = $condition;
        }

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，防止用户通过policy上传到别人的目录。
        $conditions[] = [0 => 'starts-with', 1 => '$key', 2 => $this->dir];

        return $conditions;
    }

    /**
     * 前端上传回调
     *
     * @return bool
     */
    public function callback()
    {
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = '';
        $pubKeyUrlBase64 = '';
        /*
         * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
         * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
            RewriteEngine On
            RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
         * */
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            header('http/1.1 403 Forbidden');
            exit();
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);

        if ($pubKey == '') {
            header("http/1.1 403 Forbidden");
            exit();
        }

        // 4.获取回调body
        $body = file_get_contents('php://input');

        // 5.拼接待签名字符串
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');

        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);

        if ($ok == 1) {
            return true;
        }

        header("http/1.1 403 Forbidden");
        exit();
    }

    /**
     * 时间戳格式化为 ISO8601 格式
     *
     * @param int $time
     * @return string
     * @throws Exception
     */
    private function gmtISO8601(int $time)
    {
        $dtStr = date('c', $time);
        $dateTime = new DateTime($dtStr);
        $expiration = $dateTime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);

        return $expiration . 'Z';
    }
}