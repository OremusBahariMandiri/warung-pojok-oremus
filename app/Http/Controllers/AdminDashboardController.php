<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\ReportDetails;
use App\Models\Reports;
use App\Models\Restock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $now = Carbon::now();
        $todayStr = $now->toDateString();

        // 1. KPI Metrics
        $totalProducts = Products::count();
        $newProductsThisMonth = Products::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lowStockProductsCount = Products::whereColumn('current_stock', '<=', 'min_stock')->count();

        // Today's Sales & Margin
        $todayReport = Reports::whereDate('report_date', $todayStr)->first();
        $todaySales = (float)($todayReport->total_sales ?? 0);
        $todayMargin = (float)($todayReport->total_margin ?? 0);
        $todayMarginPct = $todaySales > 0 ? round(($todayMargin / $todaySales) * 100, 1) : 0;

        // Yesterday's Sales for Growth comparison
        $yesterdayStr = $now->copy()->subDay()->toDateString();
        $yesterdayReport = Reports::whereDate('report_date', $yesterdayStr)->first();
        $yesterdaySales = (float)($yesterdayReport->total_sales ?? 0);
        $salesGrowth = null;
        if ($yesterdaySales > 0) {
            $salesGrowth = round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1);
        }

        // 2. Sales Chart Data (7 Days & 30 Days Bar Chart)
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $dStr = $d->toDateString();
            $rep = Reports::whereDate('report_date', $dStr)->first();

            $last7Days->push([
                'date' => $dStr,
                'label' => $d->isoFormat('dd, D MMM'),
                'sales' => (float)($rep->total_sales ?? 0),
                'margin' => (float)($rep->total_margin ?? 0),
                'qty' => (int)($rep->total_quantity ?? 0),
            ]);
        }

        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $dStr = $d->toDateString();
            $rep = Reports::whereDate('report_date', $dStr)->first();

            $last30Days->push([
                'date' => $dStr,
                'label' => $d->format('d/m'),
                'sales' => (float)($rep->total_sales ?? 0),
                'margin' => (float)($rep->total_margin ?? 0),
                'qty' => (int)($rep->total_quantity ?? 0),
            ]);
        }

        // 3. Top 5 Best Selling Products (Today or recent 30 days fallback)
        $isTodayBestSeller = false;
        $topProductsQuery = ReportDetails::with('product.unit');
        if ($todayReport) {
            $topProductsQuery->where('report_id', $todayReport->id);
            $isTodayBestSeller = true;
        } else {
            $topProductsQuery->whereHas('report', function ($q) use ($now) {
                $q->where('report_date', '>=', $now->copy()->subDays(30)->toDateString());
            });
        }

        // FIX: Using total_price instead of subtotal (as total_price exists on report_details table)
        $topProducts = $topProductsQuery->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total_price) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $maxQty = $topProducts->max('total_qty') ?: 1;

        // 4. Recent Restock Activities (Latest 4) with computed total_value
        $recentRestocks = Restock::with(['creator', 'items.product.unit'])
            ->orderBy('restock_date', 'desc')
            ->take(4)
            ->get()
            ->map(function ($restock) {
                $totalValue = 0.0;
                foreach ($restock->items as $item) {
                    $prod = $item->product;
                    $priceUsed = $prod ? (float)($prod->current_hpp ?? $prod->unit_price ?? 0) : 0.0;
                    $totalValue += ((int)$item->quantity * $priceUsed);
                }
                $restock->total_value = $totalValue;
                return $restock;
            });

        // 5. Critical / Lowest Stock Products (5 items)
        $criticalStockProducts = Products::with('unit')
            ->orderByRaw('CASE WHEN current_stock <= min_stock THEN 0 ELSE 1 END')
            ->orderBy('current_stock', 'asc')
            ->take(5)
            ->get();

        // Dynamic Greeting based on server hour
        $hour = (int)$now->format('H');
        if ($hour >= 4 && $hour < 11) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $greeting = 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }

        $formattedToday = $now->isoFormat('dddd, D MMMM Y');

        return view('admin.dashboard', compact(
            'totalProducts',
            'newProductsThisMonth',
            'lowStockProductsCount',
            'todaySales',
            'todayMargin',
            'todayMarginPct',
            'salesGrowth',
            'last7Days',
            'last30Days',
            'topProducts',
            'maxQty',
            'isTodayBestSeller',
            'recentRestocks',
            'criticalStockProducts',
            'greeting',
            'formattedToday'
        ));
    }
}