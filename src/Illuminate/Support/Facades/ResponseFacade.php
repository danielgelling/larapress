<?php

namespace Larapress\Support\Facades;

use Illuminate\Support\Facades\Response as BaseResponse;

class Response extends BaseResponse
{
    public static function create($content = '', $status = 200, $headers = array())
    {
        return new static($content, $status, $headers);
    }

    public function doSomething()
    {
        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'message' => 'joy'
        ]);
    }
}
