<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;

/**
 * Route table: [method, path, [Controller::class, 'action'], [middleware...]].
 * More specific paths must come before `{param}` catch-alls.
 */

return [
    ['GET',  '/',                      [HomeController::class, 'index']],

    // Public product search (the only fully-built public feature).
    ['GET',  '/products/search',       [ProductController::class, 'search']],
    ['GET',  '/products/search.json',  [ProductController::class, 'searchJson']],
    ['GET',  '/products/{slug}',       [ProductController::class, 'show']],

    // Remaining nav items are placeholders for this exercise.
    ['GET',  '/products',              [PageController::class, 'catalog']],
    ['GET',  '/technical',             [PageController::class, 'technical']],
    ['GET',  '/company',              [PageController::class, 'company']],
    ['GET',  '/contact',              [PageController::class, 'contact']],

    // Admin authentication.
    ['GET',  '/admin/login',           [SessionController::class, 'showLogin']],
    ['POST', '/admin/login',           [SessionController::class, 'login'], ['csrf']],
    ['POST', '/admin/logout',          [SessionController::class, 'logout'], ['auth', 'csrf']],

    // Admin dashboard.
    ['GET',  '/admin',                 [DashboardController::class, 'index'], ['auth']],

    // Admin product CRUD.
    ['GET',  '/admin/products',              [AdminProductController::class, 'index'], ['auth']],
    ['GET',  '/admin/products/create',       [AdminProductController::class, 'create'], ['auth']],
    ['POST', '/admin/products',              [AdminProductController::class, 'store'], ['auth', 'csrf']],
    ['GET',  '/admin/products/{id}/edit',    [AdminProductController::class, 'edit'], ['auth']],
    ['POST', '/admin/products/{id}',         [AdminProductController::class, 'update'], ['auth', 'csrf']],
    ['POST', '/admin/products/{id}/delete',  [AdminProductController::class, 'destroy'], ['auth', 'csrf']],

    // Admin product images.
    ['POST', '/admin/products/{id}/images',                  [ProductImageController::class, 'store'], ['auth', 'csrf']],
    ['POST', '/admin/products/{id}/images/primary',          [ProductImageController::class, 'setPrimary'], ['auth', 'csrf']],
    ['POST', '/admin/products/{id}/images/{imageId}/delete', [ProductImageController::class, 'destroy'], ['auth', 'csrf']],
];
