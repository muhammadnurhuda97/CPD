<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // ===== AWAL PERUBAHAN LOGIKA PENGURUTAN =====
        // Mengubah pengurutan dari 'created_at' menjadi 'updated_at'
        // Ini memastikan transaksi dengan aktivitas terbaru (termasuk update status)
        // akan selalu muncul di paling atas.
        $query = Transaction::with('user', 'participant', 'order.product')->orderBy('updated_at', 'desc');
        // ===== AKHIR PERUBAHAN LOGIKA PENGURUTAN =====

        $filterType = $request->query('type');
        $search = $request->query('search');
        $pageTitle = 'Catatan Transaksi';

        if ($user->role === 'admin') {
            $pageTitle = 'Semua Transaksi';
            if ($filterType === 'event') {
                $query->whereNotNull('participant_id');
            } elseif ($filterType === 'product') {
                $query->whereNotNull('user_id')->whereHas('order');
            }
        } else {
            $pageTitle = 'Riwayat Transaksi';
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('participant', function ($subq) use ($user) {
                        $subq->where('email', $user->email)->orWhere('whatsapp', $user->whatsapp);
                    });
            });
        }

        if ($search) {
            $query->where('order_id', 'like', '%' . $search . '%');
        }

        $transactions = $query->get();

        return view('dashboard.transaksi.index', compact('transactions', 'filterType', 'pageTitle', 'search'));
    }
}
