<?php

return [
    // 阿里云 oss 的 AccessKey ID
    'access_key_id' => env('OSS_ACCESS_KEY_ID', ''),

    // 阿里云 oss 的 AccessKey Secret
    'access_key_secret' => env('OSS_ACCESS_KEY_SECRET', ''),

    // 前端上传时候的 host
    // 格式为：bucket_name.endpoint，如 https://xx.oss-cn-qingdao.aliyuncs.com，xx 为 bucket 名称，后面一段为 endpoint 域名
    'upload_url' => env('OSS_UPLOAD_URL', ''),

    // policy 过期时间，单位秒，默认 10。
    // 前端需要拿过期时间跟当前时间判断，如果拿到的 policy 数据过期需要重新获取。
    'expires' => env('OSS_POLICY_EXPIRES', 10),

    // 后端回调地址
    'callback' => env('OSS_CALLBACK_URL', ''),

    // 前端上传的时候，key 必须是以下面配置的值开头的才能保存成功，可选，只是为了安全起见。
    // 不配置的时候不限制，上传的 key 随意写都可以上传成功
    'dir' => env('OSS_UPLOAD_DIR', ''),

    // 文件上传大小限制，可选。
    // 不配置表示不限制大小，单位 Byte
    'maxsize' => env('OSS_UPLOAD_MAXSIZE', ''),
];
