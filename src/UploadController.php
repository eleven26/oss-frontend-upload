<?php

namespace Eleven26\Oss;

class UploadController
{
    public function policy(UploadService $uploadService)
    {
        return $uploadService->getPolicy();
    }

    public function callback(UploadService $uploadService)
    {
        return $uploadService->callback();
    }
}
