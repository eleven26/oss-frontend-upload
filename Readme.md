# 阿里云 OSS 前端上传

## 安装

```
composer require eleven26/oss-frontend-upload
```

## 使用

Lumen 需要在 `bootstrap/app.php` 里面加上下面这一行，Laravel 不需要这一步：

```
$app->register(Eleven26\Oss\OssFrontendUploadServiceProvider::class);
```

## 配置

在 config 文件夹下面添加一个 oss-upload.php 文件，内容如下：

```
<?php

return [
    // 阿里云 oss 的 AccessKey ID
    'access_key_id' => env('OSS_ACCESS_KEY_ID', ''),

    // 阿里云 oss 的 AccessKey Secret
    'access_key_secret' => env('OSS_ACCESS_KEY_SECRET', ''),

    // 前端上传时候的 url
    // 格式为：bucket_name.endpoint，如 https://xx.oss-cn-qingdao.aliyuncs.com，xx 为 bucket 名称，后面一段为 endpoint 域名
    'upload_url' => env('OSS_UPLOAD_URL', ''),

    // policy 过期时间，单位秒，默认 10。
    // 前端需要拿过期时间跟当前时间判断，如果拿到的 policy 数据过期需要重新获取。
    'expires' => env('OSS_POLICY_EXPIRES', 10),

    // 后端回调地址
    'callback' => env('OSS_CALLBACK_URL', ''),

    // 前端上传的时候，key 必须是以下面配置的值开头的才能保存成功。
    'dir' => env('OSS_UPLOAD_DIR', ''),

    // 文件上传大小限制，可选。
    // 不配置表示不限制大小，单位 Byte
    'maxsize' => env('OSS_UPLOAD_MAXSIZE', ''),
];
```

修改 env 文件，添加必要的几个环境变量：

```
# 阿里云 oss 的 AccessKey ID
OSS_ACCESS_KEY_ID=
# 阿里云 oss 的 AccessKey Secret
OSS_ACCESS_KEY_SECRET=
# 前端上传时候的 url
OSS_UPLOAD_URL=
```

## 其他配置

* policy 过期时间

```
OSS_POLICY_EXPIRES=10
```

单位秒，默认为 10 秒，前端在拿到 policy 来上传图片的时候，需要先判断返回的 policy 是否已经到期，如果已经到期，则需要重新获取 policy 数据。

* 上传回调

```
OSS_CALLBACK_URL=
```

在前端上传的时候，如果没有传递 callback 的参数，则不会触发回调，如果有传递，则阿里云 oss 服务器先调用回调，拿到响应后返回给前端。也就是说，如果使用回调的时候，前端调用阿里云 oss 服务器的上传接口的时候，拿到的返回是我们回调的返回。

* 上传文件名的前缀限制

```
OSS_UPLOAD_DIR=
```

如果我们设置了这一个参数，在前端上传的时候，key 必须以这个 OSS_UPLOAD_DIR 开头，否则上传失败。

* 上传文件大小限制

```
OSS_UPLOAD_MAXSIZE=
```

单位为 byte，不设置则不限制大小，设置的时候，最大大小为设置的大小。


## 前端使用步骤

### 获取 policy

前端上传之前，需要先获取 policy 数据，也就是服务端签名数据，接口 uri 为 `/oss-upload/policy`，如 http://xx.example.com/oss-upload/policy

响应格式大致如下：

```
{
    "access_id": "LTAIS1a62F0b3J1lXj",
    "url": "http://xx.oss-cn-shenzhen.aliyuncs.com",
    "policy": "eyJleHBpcmF0aW9uIjoiMjAyMS0wMi0yMSDxNDozMjo1MVoiLCJjb25kaXRpb25zIjpbWyJzdGFydHMtd2l0aCIsIiRrZXkiLCJmcm9udGVuZFwvIl1dfQ==",
    "signature": "9B8i7t1SpYMVORgQaNBmPv14+60o=",
    "expire": 1614061971,
    "callback": "eyJjYWxsYmFja1VybCI6Imh0dHBzOlwvXC9zd29vbGUuYmFpZ3VpcmVuLmNvbVwvYXBpXC91cGxvYWRcL2NhbGxiYWNrIiwiY2FsbGJhY2tCb2R5IjoiZmlsZW5hbWU9JHtvYmplY3R9JnNpemU9JHtzaXplfSZtaW1lVHlwZT0ke21pbWVUeXBlfSZoZWlnaHQ9JHtpbWFnZUluZm8uaGVpZ2h0fSZ3aWR0aD0ke2lsstYWdlSW5mby53aWR0aH0iLCJjYWxsYmFja0JvZHlUeXBlIjoiYXBwbGljYXRpb25cL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCJ9",
    "dir": "frontend/"
}
```

返回字段说明：

|  字段名   | 说明  |
|  ----  | ----  |
| access_id  | 阿里云 oss 的 AccessKey ID |
| url  | 前端上传时用的 url，请求方法为 POST |
| policy  | 前端上传时候需要传这个参数 |
| signature  | 前端上传需要的参数 |
| expire  | 给前端判断这个签名是否已经过期，如果已经过期需要重新调用这个接口获取新的签名 |
| callback  | 回调相关参数的 base64 编码字符串，如果我们不需要回调的话，在上传的时候不传这个参数即可 |
| dir  | 上传的时候，key 的前缀必须是这里指定的才能上传成功，安全起见 |

### 调用上传接口

上传接口需要的参数如下：
          
|  参数名   | 是否必须  | 说明  |
|  ----  | ----  | ----  |
| key  | 是 | oss 保存的 key，就是前缀 + 文件名，如 xx/yy.png |
| policy  | 是 | 后端返回的过期时间等参数的 base64 编码字符串 |
| OSSAccessKeyId  | 是 | 阿里云 oss 的 AccessKey ID |
| success_action_status | 否 | 指定返回状态码，可以指定为 200，不指定的时候，响应状态码为 204 |
| signature  | 是 | 后端返回的签名字符串 |
| callback  | 否 | 后端返回的 callback 字符串，不传的话，不触发回调 |
