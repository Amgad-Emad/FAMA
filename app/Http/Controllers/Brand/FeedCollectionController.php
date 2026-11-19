<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\TalentType;
use App\Services\FeedCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The brand's private SAVED collection (5X, ADR-F9) — scoped to the
 * authenticated brand.
 */
class FeedCollectionController extends Controller
{
    public function __construct(private readonly FeedCollectionService $collections) {}

    /**
     * The SAVED page shell.
     */
    public function saved(): View
    {
        return view('brand.feed.collection', [
            'mode' => 'saved',
            'skills' => TalentType::query()->alphabetical()->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Collection rows (Ajax): ?skill=&q=&page=.
     */
    public function data(Request $request): JsonResponse
    {
        $posts = $this->collections->saved(
            $request->user('brand'),
            $request->query('skill'),
            $request->query('q'),
        );

        return response()->paginated($posts, $this->collections->present($posts));
    }
}
