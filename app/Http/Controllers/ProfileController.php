<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil pengguna.
     */
    public function index()
    {
        // Mendapatkan data pengguna yang sedang login
        $user = Auth::user();

        // Mengembalikan view dan mengirimkan data pengguna
        return view('dashboard.profile.index', compact('user'));
    }

    /**
     * Tampilkan halaman edit profil pengguna.
     */
    public function edit()
    {
        // Mendapatkan data pengguna yang sedang login
        $user = Auth::user();

        // Mengembalikan view edit profil dengan data pengguna
        return view('dashboard.profile.edit', compact('user'));
    }

    /**
     * Simpan perubahan profil pengguna.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        Log::info("🟩 [ProfileController] Memulai proses update profil untuk pengguna: '{$user->name}'.");

        // Cek user valid
        if (!($user instanceof User)) {
            return redirect()->route('profile.edit')->with('error', 'Pengguna tidak ditemukan.');
        }

        // Validasi data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'whatsapp' => 'required|string|max:20',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'affiliate_id' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'zip' => 'required|string|max:10',
            'country' => 'required|string|max:100',
            'photo' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date' => 'Format tanggal lahir tidak valid.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 6 karakter.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
            'address.required' => 'Alamat wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'zip.required' => 'Kode pos wajib diisi.',
            'country.required' => 'Negara wajib diisi.',
        ]);

        // Isi data ke user
        $user->fill([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'whatsapp' => $validatedData['whatsapp'],
            'username' => $validatedData['username'] ?? $user->username,
            'affiliate_id' => $validatedData['affiliate_id'] ?? $user->affiliate_id,
            'date_of_birth' => $validatedData['date_of_birth'] ?? null,
            'gender' => $validatedData['gender'] ?? null,
            'address' => $validatedData['address'] ?? null,
            'city' => $validatedData['city'] ?? null,
            'zip' => $validatedData['zip'] ?? null,
            'country' => $validatedData['country'] ?? null,
        ]);

        // Update password jika dikirim
        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        // Update foto jika dikirim
        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::exists($user->photo)) {
                Storage::delete($user->photo);
            }

            $path = $request->file('photo')->store('public/images/profile');
            $user->photo = $path;
        }

        // Jika user memilih hapus foto
        if ($request->remove_photo == "1") {
            if ($user->photo && Storage::exists($user->photo)) {
                Storage::delete($user->photo);
            }
            $user->photo = null;
        }


        $user->save();
        Log::info("🟩 [ProfileController] Profil berhasil disimpan ke database.");

        // Cek apakah ada 'url.intended' di session (dari CheckoutController::pay)
        if ($request->session()->has('url.intended')) {
            $intendedUrl = $request->session()->pull('url.intended'); // Ambil dan hapus dari session
            Log::info("🟩 [ProfileController] Ditemukan intendedUrl: '{$intendedUrl}'. Mengarahkan pengguna kembali ke alur checkout.");
            return redirect($intendedUrl)->with('success', 'Profil berhasil diperbarui dan Anda dapat melanjutkan proses pembayaran.');
        }

        // Jika tidak ada intended URL, kembali ke index profil
        Log::info("🟩 [ProfileController] Tidak ada intendedUrl. Mengarahkan ke halaman profil utama.");
        return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
    }
}
