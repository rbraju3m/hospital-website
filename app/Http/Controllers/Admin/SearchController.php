<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PanelSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the palette asks when the menu has no answer.
 *
 * One endpoint, one whitelist behind it (App\Support\PanelSearch), and no page
 * of its own: a search screen would be a fourth place to look for a record,
 * after the palette and the listing's own filters.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = $request->validate([
            // Capped because it is a LIKE argument, and nothing anybody types
            // to find a booking is longer than this.
            'q' => ['nullable', 'string', 'max:80'],
        ])['q'] ?? null;

        return response()->json(['results' => PanelSearch::run($term)]);
    }
}
