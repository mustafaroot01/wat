<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait VuetifyTrait
{
    /**
     * Apply Vuetify DataTable server-side options (sort, search, paginate)
     * to any Eloquent query builder.
     *
     * Frontend sends:
     *   page, per_page, sort_by, sort_dir, search
     */
    protected function scopeDataTable(
        Builder $query,
        Request $request,
        array   $searchableColumns = [],
        array   $allowedSortColumns = [],
        int     $defaultPerPage = 25
    ) {
        // ── Search ────────────────────────────────────────────────
        $search = trim($request->get('search', ''));
        if ($search !== '' && count($searchableColumns) > 0) {
            $query->where(function (Builder $q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $col) {
                    $q->orWhere($col, 'LIKE', "%{$search}%");
                }
            });
        }

        // ── Sort ──────────────────────────────────────────────────
        $sortBy  = $request->get('sort_by', '');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy !== '' && in_array($sortBy, $allowedSortColumns, true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        // ── Paginate ──────────────────────────────────────────────
        $perPage = max(1, min((int) $request->get('per_page', $defaultPerPage), 100));

        return $query->paginate($perPage)->withQueryString();
    }
}
