<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisitorController extends Controller
{
    // Fungsi untuk mengupdate kuota
    public function updateQuota(Request $request)
    {
        $file = storage_path('app/public/kuota.txt');  // File untuk menyimpan kuota

        // Cek apakah file kuota ada, jika tidak buat baru dan set kuota awal 25
        if (!file_exists($file)) {
            file_put_contents($file, "25");
        }

        // Ambil jumlah kuota saat ini
        $remainingQuota = (int) file_get_contents($file);

        // Kalau kuota tinggal 1, langsung reset ke 25
        if ($remainingQuota <= 1) {
            $remainingQuota = 25;
        } else {
            $remainingQuota--;
        }

        // Simpan kuota baru
        file_put_contents($file, $remainingQuota);

        // Kembalikan kuota yang terbaru
        return response()->json([
            'status' => 'success',
            'remainingQuota' => $remainingQuota
        ]);
    }
    public function logVisitor()
    {
        $file = storage_path('app/public/visitor.txt');

        // Cek apakah file ada, jika tidak buat baru
        if (!file_exists($file)) {
            file_put_contents($file, "0");
        }

        // Ambil jumlah visitor saat ini
        $count = (int) file_get_contents($file);

        // Tambahkan visitor baru
        $count++;

        // Simpan kembali ke file
        file_put_contents($file, $count);
    }
}
