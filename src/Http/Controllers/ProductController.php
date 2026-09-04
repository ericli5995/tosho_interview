<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Services\Product\ProductSearchService;

/** Public, read-only product API. Only published products are ever returned. */
final class ProductController extends Controller
{
    private ProductRepository $products;

    public function __construct()
    {
        $this->products = new ProductRepository(App::db());
    }

    /** GET /api/products?q=&sort=&page= */
    public function index(Request $request): Response
    {
        $result = $this->products->search((new ProductSearchService())->fromInput($request->query));
        $result['items'] = array_map(static fn (Product $p): array => $p->toArray(), $result['items']);

        return $this->json($result);
    }

    /** GET /api/products/featured */
    public function featured(): Response
    {
        return $this->json(['product' => $this->products->featured()?->toArray()]);
    }

    /** GET /api/products/{slug} */
    public function show(Request $request, array $params): Response
    {
        $product = $this->products->findBySlug((string) $params['slug']);

        return $product === null || !$product->isPublished
            ? $this->notFound()
            : $this->json(['product' => $product->toArray()]);
    }
}
