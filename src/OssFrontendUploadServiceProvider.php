<?php

namespace Eleven26\Oss;

use Illuminate\Support\ServiceProvider;

class OssFrontendUploadServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 配置
        $this->mergeConfigFrom(realpath(__DIR__.'/../config/oss-upload.php'), 'oss-upload');

        // 路由
        $this->app['router']->group(['prefix' => 'oss-upload', 'namespace' => 'Eleven26\Oss'], function ($router) {
            $router->get('policy', 'UploadController@policy');
            $router->post('callback', 'UploadController@callback');
        });
    }
}
