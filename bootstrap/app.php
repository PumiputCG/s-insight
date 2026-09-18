<?php

use App\Http\Middleware\AuthenticateEmployee;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureArea5sRoundOpen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Env;

// ⚠️ ห้ามลบ — Apache บนเซิร์ฟเวอร์เป็น process เดียวหลาย thread และรันหลายแอป (Insight + QuoteCompare)
// putenv() ทำให้ค่าใน .env ของแอปนี้รั่วไปให้อีกแอปอ่านเจอ จนอีกแอปต่อฐานข้อมูลผิดตัว
// ปิดไว้เพื่อให้แต่ละแอปอ่าน .env ของตัวเองเท่านั้น
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'insight.auth' => AuthenticateEmployee::class,
            'insight.admin' => EnsureAdmin::class,
            'area5s.round' => EnsureArea5sRoundOpen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
