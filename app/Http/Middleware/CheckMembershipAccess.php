<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Membership; // Pastikan model Membership diimport

class CheckMembershipAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $requiredType  (contoh: 'premium', 'purchased', 'affiliate_only_view_free_course_as_promo')
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $requiredType = null)
    {
        if (!Auth::check()) {
            // Jika user belum login, arahkan ke halaman login
            // Simpan intended URL agar user kembali setelah login
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('login')->with('error', 'Anda harus login untuk mengakses konten ini.');
        }

        $user = Auth::user();

        // Ambil membership user
        $membership = Membership::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$membership || $membership->status !== 'active') {
            return redirect('/dashboard')->with('error', 'Anda tidak memiliki keanggotaan aktif untuk mengakses konten ini.');
        }

        // Logika kontrol akses berdasarkan $requiredType:
        switch ($requiredType) {
            case 'premium':
                // Jika konten membutuhkan 'premium' atau lebih tinggi
                // (Anda bisa menambahkan logika untuk 'ultimate' di sini juga jika 'ultimate' > 'premium')
                if ($membership->membership_type === 'basic') {
                    return redirect('/member/upgrade')->with('error', 'Konten ini hanya untuk anggota Premium atau lebih tinggi. Silakan upgrade keanggotaan Anda.');
                }
                break;
            case 'purchased':
                // Ini untuk kasus "akses setelah membeli", misal e-course gratis setelah beli produk berbayar
                // Logikanya: basic member TIDAK boleh akses, user yang sudah beli (premium/ultimate, atau status lain yang menandakan pembelian) BOLEH
                // Jika membership type basic, berarti user belum melakukan pembelian yang meningkatkan level membership.
                if ($membership->membership_type === 'basic') {
                    // Redirect ke halaman yang menjelaskan cara mendapatkan akses (misal, beli produk tertentu)
                    return redirect('/product-list')->with('error', 'Konten ini tersedia setelah Anda melakukan pembelian produk tertentu. Silakan jelajahi produk kami.');
                }
                break;
            case 'affiliate_only_view_free_course_as_promo':
                // Skenario ini bisa lebih kompleks.
                // Jika maksudnya "Basic member (affiliate) boleh melihat halaman free course sebagai promosi,
                // tapi tidak dapat mengakses full content", maka Anda mungkin perlu:
                // 1. Membiarkan mereka lewat middleware ini (tidak di-redirect)
                // 2. Kemudian di dalam controller atau view dari e-course gratis, cek lagi $user->membership_type
                //    untuk menampilkan konten terbatas atau full content.
                // Untuk contoh ini, saya asumsikan 'basic' TIDAK BOLEH akses full content e-course gratis
                if ($membership->membership_type === 'basic') {
                    return redirect('/product-list')->with('error', 'Anda tidak memiliki akses penuh ke e-course gratis ini. Konten ini tersedia setelah Anda melakukan pembelian produk tertentu.');
                }
                break;
            // Anda bisa menambahkan case lain sesuai kebutuhan (misal: 'admin_only', 'editor', dll.)
            default:
                // Default: jika $requiredType tidak dispesifikasikan (null),
                // maka semua yang punya membership aktif (termasuk basic) boleh akses.
                break;
        }

        // Jika semua pengecekan lolos, lanjutkan ke request berikutnya
        return $next($request);
    }
}
