<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',  // Nếu bạn không muốn kiểm tra CSRF cho tất cả API, có thể thêm api/* vào đây
        'sanctum/csrf-cookie',
    ];
}
