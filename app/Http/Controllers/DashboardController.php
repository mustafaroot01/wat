<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
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

        return response()->json([
            'status_counts' => $statusCounts,
            'orders' => [
                'today'  => $ordersToday,
                'week'   => $ordersWeek,
                'month'  => $ordersMonth,
                'total'  => $ordersTotal,
            ],
            'revenue_month' => $revenueMonth,
            'users' => [
                'total' => $usersTotal,
                'today' => $usersToday,
            ],
            'recent_orders' => $recentOrders,
            'top_products'  => $topProducts,
            'top_districts' => $topDistricts,
        ]);
    }
}
