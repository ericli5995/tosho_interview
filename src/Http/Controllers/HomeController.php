<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repository\ProductRepository;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $products = new ProductRepository(App::db());

        return $this->view('home/index', [
            'featured' => $products->featured(),
        ]);
    }
}
