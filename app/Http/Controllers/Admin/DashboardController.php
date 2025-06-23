<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
         $username = Auth::user()->username;
        // Mengambil total user

        $totalUsers = User::where('affiliate_id', $username)->count();

        // Cek role user, jika admin maka tampilkan dashboard admin
        if (Auth::user()->role === 'admin') {
            $totalUsers = User::count();
            return view('dashboard.dashboard', compact('totalUsers')); // Dashboard untuk admin
        }

        // Jika bukan admin, tampilkan dashboard untuk user
        return view('dashboard.dashboarduser', compact('totalUsers')); // Dashboard untuk user biasa
    }
}
