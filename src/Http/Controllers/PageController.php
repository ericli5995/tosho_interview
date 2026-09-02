<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * Placeholder pages for the nav items that are out of scope for this exercise.
 * Only 製品検索 (ProductController::search) is fully implemented.
 */
final class PageController extends Controller
{
    public function catalog(Request $request): Response
    {
        return $this->placeholder('製品情報', '製品カテゴリ別の一覧ページはここに入ります。');
    }

    public function technical(Request $request): Response
    {
        return $this->placeholder('技術情報', 'ギヤ技術・モータ技術に関する解説ページはここに入ります。');
    }

    public function company(Request $request): Response
    {
        return $this->placeholder('会社情報', '会社概要・沿革・アクセスはここに入ります。');
    }

    public function contact(Request $request): Response
    {
        return $this->placeholder('お問い合わせ', 'お問い合わせ先（電話・住所・受付時間）はここに入ります。');
    }

    private function placeholder(string $title, string $note): Response
    {
        return $this->view('pages/placeholder', [
            'title' => $title,
            'note' => $note,
        ]);
    }
}
