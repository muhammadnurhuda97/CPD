<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ParticipantsExport;
use App\Exports\UsersExport;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $eventType = 'webinar';

        if ($user->role === 'admin') {
            $membersQuery = Participant::where('event_type', $eventType);
        } else {
            // ===== AWAL PERBAIKAN LOGIKA QUERY =====
            // 1. Filter berdasarkan event_type terlebih dahulu
            $membersQuery = Participant::where('event_type', $eventType)
                // 2. Kelompokkan kondisi 'affiliate_id' dalam satu grup
                ->where(function ($query) use ($user) {
                    $query->where('affiliate_id', $user->id)
                        ->orWhere('affiliate_id', $user->username);
                });
            // ===== AKHIR PERBAIKAN LOGIKA QUERY =====
        }

        if ($search) {
            $membersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%") // BARU: Menambahkan pencarian berdasarkan WhatsApp
                    ->orWhere('affiliate_id', 'like', "%{$search}%") // Menggunakan affiliate_id untuk data lama
                    ->orWhereHas('affiliateUser', function ($q) use ($search) { // BARU: Menambahkan pencarian berdasarkan username pengundang
                        $q->where('username', 'like', "%{$search}%");
                    })
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('perPage', 15);
        $members = $membersQuery->latest()->paginate($perPage);

        return view('dashboard.peserta.index', compact('members'));
    }

    public function indexWorkshop(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $eventType = 'workshop';

        if ($user->role === 'admin') {
            $membersQuery = Participant::where('event_type', $eventType);
        } else {
            // ===== AWAL PERBAIKAN LOGIKA QUERY =====
            // 1. Filter berdasarkan event_type terlebih dahulu
            $membersQuery = Participant::where('event_type', $eventType)
                // 2. Kelompokkan kondisi 'affiliate_id' dalam satu grup
                ->where(function ($query) use ($user) {
                    $query->where('affiliate_id', $user->id)
                        ->orWhere('affiliate_id', $user->username);
                });
            // ===== AKHIR PERBAIKAN LOGIKA QUERY =====
        }

        if ($search) {
            $membersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%") // BARU: Menambahkan pencarian berdasarkan WhatsApp
                    ->orWhere('affiliate_id', 'like', "%{$search}%") // Menggunakan affiliate_id untuk data lama
                    ->orWhereHas('affiliateUser', function ($q) use ($search) { // BARU: Menambahkan pencarian berdasarkan username pengundang
                        $q->where('username', 'like', "%{$search}%");
                    })
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('perPage', 15);
        $members = $membersQuery->latest()->paginate($perPage);

        return view('dashboard.peserta.index', compact('members'));
    }

    public function indexMember(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('perPage', 15);

        if ($user->role === 'admin') {
            $membersQuery = User::query();
        } else {
            // ===== CATATAN: Ini memfilter untuk HANYA user itu sendiri =====
            // Jika Anda ingin user melihat member yang mereka undang,
            // query-nya harus diubah ke:
            // $membersQuery = User::where('affiliate_id', $user->username);
            // Untuk saat ini, saya biarkan sesuai kode asli Anda:
            $membersQuery = User::where('id', $user->id);
            // ===============================================================
        }

        $search = $request->input('search');
        if ($search) {
            $membersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $members = $membersQuery->paginate($perPage);
        return view('dashboard.member.index', compact('members'));
    }

    public function destroy($id)
    {
        $member = Participant::find($id);
        if (!$member) {
            return back()->with('error', 'Peserta tidak ditemukan.');
        }

        $user = Auth::user();
        // ===== PERBAIKAN PENGECEKAN KEAMANAN =====
        if ($user->role !== 'admin' && $member->affiliate_id !== $user->id && $member->affiliate_id !== $user->username) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus peserta ini.');
        }
        // ==========================================

        $eventType = $member->event_type;
        $member->delete();
        $redirectRoute = $eventType === 'workshop' ? 'members.workshop' : 'members.index';

        return redirect()->route($redirectRoute)->with('success', 'Peserta berhasil dihapus!');
    }

    public function delete($id)
    {
        $member = User::find($id);
        if (!$member) {
            return back()->with('error', 'Member tidak ditemukan.');
        }

        $user = Auth::user();
        if ($user->id === $member->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        // ===== PERBAIKAN PENGECEKAN KEAMANAN =====
        if ($user->role !== 'admin' && $member->affiliate_id !== $user->username) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus member ini.');
        }
        // ==========================================

        $member->delete();
        return redirect()->route('members.member')->with('success', 'Member berhasil dihapus!');
    }

    public function exportCSV(Request $request)
    {
        $eventType = $request->query('type', 'webinar');
        $search = $request->query('search'); // Ambil parameter search
        $city = $request->query('city');     // Ambil parameter city

        $filename = 'lead-' . $eventType . '.csv';

        // Logika ini sudah benar dari langkah kita sebelumnya
        $user = Auth::user();
        $exportUser = null;

        if ($user->role !== 'admin') {
            $exportUser = $user;
        }

        // Meneruskan parameter search, city, dan user (jika bukan admin)
        return Excel::download(new ParticipantsExport($eventType, $search, $city, $exportUser), $filename);
    }

    public function exportUsersCSV()
    {
        return Excel::download(new UsersExport, 'users.csv');
    }

    public function commissionReport(Request $request)
    {
        $referrals = Participant::whereNotNull('referred_by_participant_id')
            ->whereHas('notification', function ($query) {
                $query->where('is_paid', true); // Tetap hanya dari event berbayar
            })
            ->with([
                'notification',
                'referrer' => function ($query) {
                    $query->with('affiliateUser');
                },
            ])
            ->latest('created_at')
            ->paginate(25);

        // Kirim data ke view
        return view('dashboard.peserta.commissions', compact('referrals'));
    }

    // ===== AWAL METHOD BARU UNTUK AKSI =====
    /**
     * Menyetujui referral (biasanya untuk pembayaran tunai).
     */
    public function approveReferral(Participant $participant)
    {
        // Validasi dasar: pastikan memang perlu diapprove
        if ($participant->payment_status === 'pending_cash_verification' || $participant->payment_method === 'cash') {
            $participant->payment_status = 'paid';
            $participant->is_paid = 1; // Pastikan flag is_paid juga diupdate
            $participant->save();

            Log::info('Referral approved manually (Cash Payment Confirmed)', ['participant_id' => $participant->id, 'order_id' => $participant->order_id]);

            // Opsional: Trigger notifikasi WA lunas di sini jika belum terkirim
            // $waService = app(WhatsAppNotificationService::class);
            // $waService->sendPaidConfirmation($participant);
            // $waService->sendAdminNotification($participant, $participant->event_type); // Sesuaikan jika perlu
            // ... (notif ke affiliate/referrer)

            return redirect()->route('admin.commissions.report')->with('success', 'Pembayaran tunai berhasil dikonfirmasi.');
        }

        Log::warning('Attempted to approve referral with unsuitable status', ['participant_id' => $participant->id, 'status' => $participant->payment_status]);
        return redirect()->route('admin.commissions.report')->with('error', 'Status referral tidak sesuai untuk disetujui.');
    }

    /**
     * Membatalkan referral.
     */
    public function cancelReferral(Participant $participant)
    {
        // Hanya batalkan jika belum lunas
        if ($participant->payment_status !== 'paid') {
            $participant->payment_status = 'failed'; // Atau 'cancelled', sesuaikan
            // 'is_paid' tetap 0
            $participant->save();

            Log::info('Referral cancelled manually', ['participant_id' => $participant->id, 'order_id' => $participant->order_id]);

            return redirect()->route('admin.commissions.report')->with('success', 'Referral berhasil dibatalkan.');
        }

        Log::warning('Attempted to cancel an already paid referral', ['participant_id' => $participant->id]);
        return redirect()->route('admin.commissions.report')->with('error', 'Referral yang sudah lunas tidak dapat dibatalkan.');
    }
}