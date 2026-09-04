<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionController;

/**
 * JSON API routes: [method, path, [Controller::class, 'action'], [middleware...]].
 * Static pages under public/ are served by Apache directly and never reach here.
 * More specific paths must precede `{param}` catch-alls.
 */
return [
    // session / auth
    ['GET',    '/api/session',       [SessionController::class, 'show']],
    ['POST',   '/api/admin/login',   [SessionController::class, 'login'],  ['csrf']],
    ['POST',   '/api/admin/logout',  [SessionController::class, 'logout'], ['auth', 'csrf']],

    // public catalogue (published products only)
    ['GET',    '/api/products/featured', [ProductController::class, 'featured']],
    ['GET',    '/api/products/options',  [ProductController::class, 'options']],
    ['GET',    '/api/products',          [ProductController::class, 'index']],
    ['GET',    '/api/products/{slug}',   [ProductController::class, 'show']],
    ['GET',    '/api/categories',        [ProductController::class, 'categories']],

    // admin catalogue management
    ['GET',    '/api/admin/products',                                [AdminProductController::class, 'index'],   ['auth']],
    ['GET',    '/api/admin/products/{id}',                           [AdminProductController::class, 'show'],    ['auth']],
    ['POST',   '/api/admin/products',                                [AdminProductController::class, 'store'],   ['auth', 'csrf']],
    ['POST',   '/api/admin/products/{id}',                           [AdminProductController::class, 'update'],  ['auth', 'csrf']],
    ['DELETE', '/api/admin/products/{id}',                           [AdminProductController::class, 'destroy'], ['auth', 'csrf']],
    ['POST',   '/api/admin/products/{id}/images',                    [ProductImageController::class, 'store'],      ['auth', 'csrf']],
    ['DELETE', '/api/admin/products/{id}/images/{imageId}',          [ProductImageController::class, 'destroy'],    ['auth', 'csrf']],
    ['PUT',    '/api/admin/products/{id}/images/{imageId}/primary',  [ProductImageController::class, 'setPrimary'], ['auth', 'csrf']],
];
