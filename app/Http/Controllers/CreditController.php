<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditTransaction::orderBy('created_at', 'desc');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate(10);

        return response()->json([
            'data'         => $transactions->items(),
            'total'        => $transactions->total(),
            'current_page' => $transactions->currentPage(),
            'last_page'    => $transactions->lastPage(),
            'balances'     => [
                'otp'          => (int) Setting::get('otp_credits', 0),
                'notification' => (int) Setting::get('notification_credits', 0),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'      => 'required|in:otp,notification',
            'quantity'  => 'required|integer|min:1|max:100000',
            'price_iqd' => 'required|integer|min:0',
            'note'      => 'nullable|string|max:255',
        ]);

        $key     = $request->type === 'otp' ? 'otp_credits' : 'notification_credits';
        $current = (int) Setting::get($key, 0);
        $new     = $current + (int) $request->quantity;

        Setting::set($key, $new);

        $tx = CreditTransaction::create([
            'type'      => $request->type,
            'quantity'  => $request->quantity,
            'price_iqd' => $request->price_iqd,
            'note'      => $request->note,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'تمت إضافة الرصيد وتسجيل الفاتورة بنجاح.',
            'transaction' => $tx,
            'new_balance' => $new,
        ], 201);
    }

    public function show(CreditTransaction $creditTransaction)
    {
        return response()->json(['data' => $creditTransaction]);
    }
}
