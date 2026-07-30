<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Service\StatsService;

class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        // StatsService::dashboard() отдаёт единый агрегат; во вью решаем,
        // какую часть показывать — модератору выручка и товары не нужны.
        $stats = (new StatsService())->dashboard(30);

        return $this->render('dashboard/index', [
            'stats' => $stats,
        ]);
    }
}
