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
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;

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
    /**
     * Menyetujui referral (konfirmasi pembayaran tunai).
     */
    public function approveReferral(Participant $participant, WhatsAppNotificationService $waService) // Inject WA Service
    {
        // 1. Validasi Status Awal
        if (!in_array($participant->payment_status, ['pending_cash_verification', 'pending_choice']) || $participant->payment_method !== 'cash') {
            Log::warning('Attempted to approve referral with unsuitable status/method', [
                'participant_id' => $participant->id,
                'status' => $participant->payment_status,
                'method' => $participant->payment_method
            ]);
            return redirect()->back()->with('error', 'Status atau metode pembayaran peserta tidak sesuai untuk konfirmasi tunai.');
        }

        // 2. Load Relasi yang Dibutuhkan
        $participant->loadMissing(['notification', 'affiliateUser', 'referrer']); // Load semua relasi

        if (!$participant->notification) {
            Log::error('Approve Referral Error: Notification data missing.', ['participant_id' => $participant->id]);
            return redirect()->back()->with('error', 'Data event tidak ditemukan untuk peserta ini.');
        }

        // 3. Update Status Peserta
        DB::beginTransaction(); // Gunakan transaksi database
        try {
            $participant->payment_status = 'paid';
            $participant->is_paid = 1;
            // participant->payment_method sudah 'cash'
            // Set flag notifikasi agar tidak dikirim ulang oleh service WA
            $participant->notified_paid = false; // Reset flag agar notif lunas dikirim
            $participant->paid_reminder_scheduled = false; // Reset flag agar reminder dijadwalkan ulang
            $participant->save();

            DB::commit(); // Simpan perubahan status
            Log::info('Referral approved manually (Cash Confirmed)', ['participant_id' => $participant->id, 'order_id' => $participant->order_id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update participant status on approveReferral', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal memperbarui status peserta. Coba lagi.');
        }


        // --- KIRIM SEMUA NOTIFIKASI WA (setelah status berhasil disimpan) ---
        try {
            // 1. Konfirmasi Lunas ke Peserta (dengan link referral H-2 & H+6)
            $waService->sendPaidConfirmation($participant);

            // 2. KEMBALIKAN NOTIF ADMIN (LUNAS)
            $waService->sendAdminNotification($participant, $participant->event_type);

            // 3. KEMBALIKAN NOTIF AFFILIATE (LUNAS)
            if ($participant->affiliate_id) {
                if ($participant->affiliateUser) {
                    $waService->sendAffiliateNotification($participant->affiliate_id, $participant, $participant->event_type);
                } else {
                    Log::warning("Affiliate user data not found for participant {$participant->id} on approveReferral", ['affiliate_id' => $participant->affiliate_id]);
                }
            }

            // 4. Notifikasi ke Referrer (LUNAS)
            if ($participant->referrer) {
                $waService->sendReferrerNotification($participant, $participant->referrer);
            }

            // Simpan ulang participant untuk update flag notifikasi dari service WA
            $participant->save();

            Log::info('All WA notifications sent for approved referral', ['participant_id' => $participant->id]);
            return redirect()->route('admin.commissions.report')->with('success', 'Pembayaran tunai berhasil dikonfirmasi & notifikasi lunas dikirim.');
        } catch (\Exception $e) {
            // Tangani jika pengiriman WA gagal (status DB sudah terupdate)
            Log::error('Failed to send WA notifications after approving referral', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);
            // Beri pesan sukses tapi dengan catatan error WA
            return redirect()->route('admin.commissions.report')->with('success', 'Pembayaran tunai dikonfirmasi, namun terjadi masalah saat mengirim notifikasi WhatsApp.');
        }
    }

    /**
     * Membatalkan referral.
     */
    public function cancelReferral(Participant $participant)
    {
        // Hanya batalkan jika belum lunas
        if ($participant->payment_status !== 'paid') {
            $participant->payment_status = 'failed'; // Atau 'cancelled'
            // 'is_paid' tetap 0
            $participant->save();

            Log::info('Referral cancelled manually', ['participant_id' => $participant->id, 'order_id' => $participant->order_id]);

            // Opsional: Kirim notifikasi pembatalan ke peserta/admin?

            return redirect()->route('admin.commissions.report')->with('success', 'Referral berhasil dibatalkan.');
        }

        Log::warning('Attempted to cancel an already paid referral', ['participant_id' => $participant->id]);
        return redirect()->route('admin.commissions.report')->with('error', 'Referral yang sudah lunas tidak dapat dibatalkan.');
    }
}
