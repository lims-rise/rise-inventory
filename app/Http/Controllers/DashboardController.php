<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\DashboardService;
use App\Services\LocationContext;
use App\Support\ConditionStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Pengganti controller CI3 Welcome::index (URL tetap /welcome). */
class DashboardController extends Controller
{

    public function index(Request $request, LocationContext $locations): View
    {
        $locationId = $locations->currentId($request->user());
        $dashboard = new DashboardService($locationId);

        $summary = $dashboard->conditionSummary();
        $percent = fn (int $n) => $summary['total'] > 0 ? round($n / $summary['total'] * 100, 1) : 0;

        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 11 => 'Good morning',
            $hour < 15 => 'Good afternoon',
            $hour < 19 => 'Good evening',
            default => 'Good night',
        };

        $locationName = $locationId ? (string) Location::whereKey($locationId)->value('name') : 'All locations';

        return view('dashboard', [
            'greeting' => $greeting,
            'locationLabel' => LocationContext::label($locationName),
            'locationFull' => $locationName,
            'today' => now()->locale('en')->translatedFormat('l, j F Y'),

            'summary' => $summary,
            'percent' => array_map($percent, array_diff_key($summary, ['total' => true])),
            'totalItems' => $dashboard->totalItems(),
            'categoryCount' => $dashboard->categoryCount(),
            'staffCount' => $dashboard->activeStaffCount(),

            'chartData' => $dashboard->inventoryBySubCategory()
                ->map(fn ($row) => ['name' => $row->name, 'y' => (int) $row->val])
                ->values(),
            'donutData' => $dashboard->conditionBreakdown()
                ->map(fn ($row) => [
                    'name' => $row->name,
                    'y' => $row->val,
                    'color' => ConditionStyle::color($row->id),
                ])
                ->values(),

            'staff' => $dashboard->staffActivity(10),
            'transactions' => $dashboard->recentTransactions(6)->map(function ($t) {
                $t->color = ConditionStyle::color($t->condition_id);
                $t->ago = $t->happened_at ? Carbon::parse($t->happened_at)->locale('en')->diffForHumans() : '-';

                return $t;
            }),
        ]);
    }
}
