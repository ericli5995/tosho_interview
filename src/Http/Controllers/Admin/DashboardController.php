<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repository\ProductRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $products = new ProductRepository(App::db());

        return $this->view('admin/dashboard', [
            'title' => 'ダッシュボード',
            'totalProducts' => $products->countAll(),
            'publishedProducts' => $products->countPublished(),
        ], 'layouts/admin');
    }
}
