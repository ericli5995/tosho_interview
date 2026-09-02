<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repository\ProductRepository;
use App\Service\ProductSearchService;

final class ProductController extends Controller
{
    private ProductRepository $products;
    private ProductSearchService $searchService;

    public function __construct()
    {
        $this->products = new ProductRepository(App::db());
        $this->searchService = new ProductSearchService();
    }

    /** GET /products/search - renders the page shell; the Vue app calls searchJson(). */
    public function search(Request $request): Response
    {
        return $this->view('products/search', [
            'title' => '製品検索',
            'diameterOptions' => $this->products->distinctDiameters(),
            'voltageOptions' => $this->products->distinctVoltages(),
        ]);
    }

    /** GET /products/search.json - the data endpoint behind the search UI. */
    public function searchJson(Request $request): Response
    {
        $criteria = $this->searchService->fromInput($request->query);
        $result = $this->products->search($criteria);

        return $this->json([
            'items' => array_map(
                static fn ($product) => $product->toArray(),
                $result['items']
            ),
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'pages' => $result['pages'],
        ]);
    }

    /** GET /products/{slug} - product detail. */
    public function show(Request $request, array $params): Response
    {
        $product = $this->products->findBySlug((string) ($params['slug'] ?? ''));

        if ($product === null || !$product->isPublished) {
            return $this->notFound();
        }

        return $this->view('products/show', [
            'title' => $product->name,
            'product' => $product,
        ]);
    }
}
