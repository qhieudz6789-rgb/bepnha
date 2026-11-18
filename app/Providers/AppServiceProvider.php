<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Dùng mẫu phân trang HTML thuần (không cần Tailwind/Bootstrap)
        Paginator::defaultView('pagination::default');
        Paginator::defaultSimpleView('pagination::simple-default');

        Carbon::setLocale('vi'); // "5 phút trước" thay cho "5 minutes ago"
    }
}
