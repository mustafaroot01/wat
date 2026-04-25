<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return Cache::remember('dashboard_data', 60, function () {
            return $this->buildDashboard();
        });
    }

    private function buildDashboard()
    {
        $today     = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart= Carbon::now()->startOfMonth();

        // ── Order counts by status ──────────────────────────
        $statusCounts = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ── Order stats ─────────────────────────────────────
        $ordersToday  = Order::whereDate('created_at', $today)->count();
        $ordersWeek   = Order::where('created_at', '>=', $weekStart)->count();
        $ordersMonth  = Order::where('created_at', '>=', $monthStart)->count();
        $ordersTotal  = Order::count();

        // ── Revenue ─────────────────────────────────────────
        $revenueMonth = Order::where('created_at', '>=', $monthStart)
            ->where('status', Order::STATUS_DELIVERED)
            ->sum('final_amount');

        // ── Users ────────────────────────────────────────────
        $usersTotal   = User::count();
        $usersToday   = User::whereDate('created_at', $today)->count();

        // ── Last 10 orders ───────────────────────────────────
        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->take(10)
            ->get(['id','invoice_code','customer_name','customer_phone','final_amount','status','created_at']);

        // ── Top 5 ordered products ───────────────────────────
        $topProducts = DB::table('order_items')
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // ── Top 5 districts by order count ───────────────────
        $topDistricts = DB::table('orders')
            ->select('district', DB::raw('COUNT(*) as order_count'))
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->groupBy('district')
            ->orderByDesc('order_count')
            ->take(5)
            ->get();

        // ── Revenue chart: last 30 days ───────────────────────
        $revenueRaw = Order::selectRaw('DATE(created_at) as date, SUM(final_amount) as total')
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->where('status', Order::STATUS_DELIVERED)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $revenueChart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $revenueChart[] = [
                'date'  => Carbon::now()->subDays($i)->format('d/m'),
                'total' => (float) ($revenueRaw[$date]->total ?? 0),
            ];
        }

        // ── Orders chart: last 30 days ────────────────────────
        $ordersRaw = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $ordersChart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $ordersChart[] = [
                'date'  => Carbon::now()->subDays($i)->format('d/m'),
                'count' => (int) ($ordersRaw[$date]->count ?? 0),
            ];
        }

        return response()->json([
            'status_counts' => $statusCounts,
            'orders' => [
                'today'  => $ordersToday,
                'week'   => $ordersWeek,
                'month'  => $ordersMonth,
                'total'  => $ordersTotal,
            ],
            'revenue_month'  => $revenueMonth,
            'revenue_chart'  => $revenueChart,
            'orders_chart'   => $ordersChart,
            'users' => [
                'total' => $usersTotal,
                'today' => $usersToday,
            ],
            'credits' => [
                'otp'          => (int) Setting::get('otp_credits', 0),
                'notification' => (int) Setting::get('notification_credits', 0),
            ],
            'recent_orders' => $recentOrders,
            'top_products'  => $topProducts,
            'top_districts' => $topDistricts,
        ]);
    }
}
