<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;

class AboutController
{
    use RendersPage;

    public function index(Request $request): Response
    {
        return $this->renderPage('pages/about', [
            'pageTitle' => 'О нас',
        ]);
    }
}
