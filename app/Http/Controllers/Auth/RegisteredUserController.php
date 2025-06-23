<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Membership;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Mengirim notifikasi menggunakan cURL dengan cara yang aman untuk produksi.
     * Fungsi ini sekarang mengambil URL dan Kunci API dari config secara internal.
     *
     * @param array $messageData Data pesan yang akan dikirim.
     */
    private function sendToStarsender(array $messageData)
    {
        $curl = curl_init();

        $apiKey = config('services.starsender.api_key');
        $apiUrl = config('services.starsender.url');

        if (empty($apiKey) || empty($apiUrl)) {
            Log::error('RegisteredUserController: URL atau Kunci API Starsender tidak dikonfigurasi.');
            return;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($messageData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $apiKey,
            ],
        ]);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            Log::error('CURL Error di RegisteredUserController: ' . curl_error($curl));
        } else {
            Log::info('Starsender API Response from RegisteredUserController: ' . $response);
        }
        curl_close($curl);
    }

    public function create(Request $request)
    {
        $affiliateId = $request->session()->get('affiliate_id_from_product_url', 'admin');
        return view('auth.register', compact('affiliateId'));
    }

    /**
     * Menangani permintaan pendaftaran yang masuk.
     */
    public function store(Request $request)
    {
        $whatsapp = preg_replace('/\D/', '', $request->whatsapp);
        if (substr($whatsapp, 0, 2) == '62') {
            $whatsapp = '0' . substr($whatsapp, 2);
        }
        $request->merge(['whatsapp' => $whatsapp]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'whatsapp' => ['required', 'string', 'unique:users,whatsapp'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'whatsapp.unique' => 'Nomor WhatsApp sudah terdaftar.'
        ]);

        $affiliateIdFromRequest = $request->affiliate_id;
        $finalAffiliateId = $affiliateIdFromRequest;
        if (empty($affiliateIdFromRequest)) {
            $finalAffiliateId = 'admin';
        }

        $username = strtolower(str_replace(' ', '', $request->name));
        if (User::where('username', $username)->exists()) {
            $username .= rand(100, 999);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'password' => Hash::make($request->password),
            'username' => $username,
            'affiliate_id' => $finalAffiliateId,
        ]);

        $request->session()->forget('affiliate_id_from_product_url');

        Membership::create([
            'user_id' => $user->id,
            'membership_type' => 'basic',
            'commission_rate' => 30.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Kirim semua notifikasi
        $this->sendAdminNotification($user);
        $this->sendWhatsAppNotification($user);
        if ($finalAffiliateId && $finalAffiliateId !== 'admin') {
            $this->sendAffiliateNotification($finalAffiliateId, $user);
        }

        $intendedUrl = $request->session()->pull('url.intended');
        if ($intendedUrl) {
            session(['url.intended' => $intendedUrl]);
        }

        return redirect()->route('check.profile.completion');
    }

    private function sendAffiliateNotification($affiliateId, $user)
    {
        $affiliate = User::where('username', $affiliateId)->first();
        if ($affiliate && $affiliate->whatsapp) {
            $affiliateMessage = "🎉 Wah, Selamat *{$affiliate->name}*👋\n\n" .
                "Anda telah berhasil mengundang *{$user->name}* untuk menjadi *Member Autosmart*\n\n" .
                "Whatsapp: *{$user->whatsapp}*\n" .
                "Email: *{$user->email}*\n\n" .
                "Semoga langkah ini membawa keberkahan dan membuka peluang rezeki yang lebih besar. " .
                "Semoga member baru ini dapat menjadi lead yang berpotensi dan memberikan peluang lebih besar untuk komisi Anda. " .
                "Terima kasih atas usaha dan partisipasinya! Jangan lupa follow up ya!";

            $messageData = [
                'messageType' => 'text',
                'to' => $affiliate->whatsapp,
                'body' => $affiliateMessage,
            ];
            $this->sendToStarsender($messageData);
        }
    }

    private function sendAdminNotification($user)
    {
        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array (INI PERBAIKAN)
        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        $affiliateUsername = $user->affiliate_id;
        $affiliate = User::where('username', $affiliateUsername)->first();
        $affiliateName = $affiliate->name ?? 'Tidak Diketahui';
        $affiliateWhatsapp = $affiliate->whatsapp ?? 'Tidak Diketahui';

        $adminMessage = "Peserta baru telah mendaftar jadi Member!\n\n" .
            "Nama: *{$user->name}*\n" .
            "No. WhatsApp: *{$user->whatsapp}*\n" .
            "Pengundang: *{$affiliateName}* - *{$affiliateWhatsapp}*\n\n" .
            "Segera verifikasi dan tindak lanjuti pendaftaran ini.";

        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $messageData = [
                    'messageType' => 'text',
                    'to' => trim($number),
                    'body' => $adminMessage,
                ];
                $this->sendToStarsender($messageData);
            }
        }
    }

    private function sendWhatsAppNotification($user)
    {
        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array (INI PERBAIKAN)
        $adminNumbersArray = config('services.admin.whatsapp', ['082245342997']);
        // Menggabungkan untuk tampilan pesan (format "nomor1 atau nomor2")
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminNumbersArray)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997'; // Fallback jika array kosong
        }

        $whatsappMessage = "🎉 Selamat Bergabung {$user->name}! Pendaftaran akun Anda di *Pemuda Digital* Berhasil.\n\n" .
            "Berikut adalah data diri Anda yang terdaftar:\n" .
            "- Nama: *{$user->name}*\n" .
            "- Email: *{$user->email}*\n" .
            "- Nomer Hp: *{$user->whatsapp}*\n\n" .
            "Pastikan Anda menyimpan nomor ini untuk kemudahan komunikasi dengan kami di masa depan. Jika ada pertanyaan, hubungi kami di {$adminPhoneNumberForDisplay}\n\n" .
            "Hormat kami,\n" .
            "Admin Pemuda Digital";

        $messageData = [
            'messageType' => 'text',
            'to' => $user->whatsapp,
            'body' => $whatsappMessage,
        ];
        $this->sendToStarsender($messageData);
    }
}
