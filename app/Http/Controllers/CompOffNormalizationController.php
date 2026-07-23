<?php

namespace App\Http\Controllers;

use App\Services\CompOffNormalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffNormalizationController extends Controller
{
    /**
     * Preview or apply comp_off_leaves normalization for one calendar year (tenant-scoped).
     *
     * POST JSON or form: year (default current year), dry_run (default true), confirm (required false for apply).
     */
    public function run(Request $request)
    {
        if (!Auth::user()->can('Manage Leave')) {
            return response()->json(['ok' => false, 'message' => __('Permission denied.')], 403);
        }

        $type = strtolower((string) Auth::user()->type);
        if (!in_array($type, ['company', 'hr', 'director'], true)) {
            return response()->json(['ok' => false, 'message' => __('Only company, HR, or director can run this action.')], 403);
        }

        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > (int) now()->year + 1) {
            return response()->json(['ok' => false, 'message' => __('Invalid year.')], 422);
        }

        $dryRun = filter_var($request->input('dry_run', true), FILTER_VALIDATE_BOOLEAN);
        $confirm = filter_var($request->input('confirm', false), FILTER_VALIDATE_BOOLEAN);

        if (!$dryRun && !$confirm) {
            return response()->json([
                'ok' => false,
                'message' => __('Applying changes requires confirm=1 (or form field confirm=1). Use dry_run=1 to preview without writing.'),
            ], 422);
        }

        $creatorId = (int) Auth::user()->creatorId();

        $service = new CompOffNormalizationService();
        $report = $service->normalizeYearForTenant($year, $creatorId, $dryRun);

        return response()->json([
            'ok' => true,
            'message' => $dryRun
                ? __('Dry run completed. No database changes were made.')
                : __('Normalization applied successfully.'),
            'report' => $report,
        ]);
    }
}
