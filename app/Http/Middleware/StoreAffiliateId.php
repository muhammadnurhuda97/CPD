<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session; // Import Session Facade

class StoreAffiliateId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Jika ada affiliate_id di URL query string DAN belum ada di session,
        // simpan ke session. Ini akan berlaku untuk kunjungan pertama user dari link affiliate.
        if ($request->has('affiliate_id') && !$request->session()->has('affiliate_id_from_product_url')) {
            $affiliateId = $request->query('affiliate_id');
            // Anda bisa tambahkan validasi di sini untuk affiliateId jika diperlukan
            // Contoh: apakah affiliateId ini benar-benar ada di tabel users?
            // use App\Models\User;
            // if (User::where('username', $affiliateId)->exists()) {
            $request->session()->put('affiliate_id_from_product_url', $affiliateId);
            // }
        }

        return $next($request);
    }
}
