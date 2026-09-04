<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductSearchService;

/** Public, read-only product API. Only published products are ever returned. */
final class ProductController extends Controller
{
    private ProductRepository $products;

    public function __construct()
    {
        $this->products = new ProductRepository(App::db());
    }

    /** GET /api/products?q=&motor_type=&diameter[]=&voltage[]=&sort=&page= */
    public function index(Request $request): Response
    {
        $result = $this->products->search((new ProductSearchService())->fromInput($request->query));
        $result['items'] = array_map(static fn (Product $p): array => $p->toArray(), $result['items']);

        return $this->json($result);
    }

    /** GET /api/products/options - distinct filter values for the search UI */
    public function options(): Response
    {
        return $this->json([
            'diameters' => $this->products->distinctDiameters(),
            'voltages' => $this->products->distinctVoltages(),
        ]);
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

    /** GET /api/categories */
    public function categories(): Response
    {
        $categories = (new CategoryRepository(App::db()))->all();

        return $this->json(['categories' => array_map(static fn (Category $c): array => $c->toArray(), $categories)]);
    }
}
