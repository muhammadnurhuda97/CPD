<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ParticipantsExport;
use App\Exports\UsersExport;

class MemberController extends Controller
{
    // Menampilkan daftar peserta (member)
    public function index(Request $request)
    {
        $username = Auth::user()->username;
        $search = $request->input('search');  // Ambil input pencarian
        $eventType = 'webinar';  // Tipe event untuk halaman ini

        if (Auth::user()->role === 'admin') {
            $membersQuery = Participant::where('event_type', $eventType);
        } else {
            $membersQuery = Participant::where('affiliate_id', $username)
                ->where('event_type', $eventType);
        }

        // Jika ada pencarian, filter berdasarkan nama, email, atau whatsapp
        if ($search) {
            $membersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('affiliate_id', 'like', "%{$search}%");
            });
        }

        // Paginate hasil query dengan 15 per halaman
        $perPage = $request->input('perPage', 15);
        $members = $membersQuery->paginate($perPage);

        return view('dashboard.peserta.index', compact('members'));
    }

    public function indexWorkshop(Request $request)
    {
        $username = Auth::user()->username;
        $search = $request->input('search');  // Ambil input pencarian
        $eventType = 'workshop';  // Tipe event untuk halaman ini

        if (Auth::user()->role === 'admin') {
            $membersQuery = Participant::where('event_type', $eventType);
        } else {
            $membersQuery = Participant::where('affiliate_id', $username)
                ->where('event_type', $eventType);
        }

        // Jika ada pencarian, filter berdasarkan nama, email, atau whatsapp
        if ($search) {
            $membersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('affiliate_id', 'like', "%{$search}%");
            });
        }

        // Paginate hasil query dengan 15 per halaman
        $perPage = $request->input('perPage', 15);
        $members = $membersQuery->paginate($perPage);

        return view('dashboard.peserta.index', compact('members'));
    }

    public function indexMember(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('perPage', 15); // Tambahkan ini

        if ($user->role === 'admin') {
            // Admin melihat semua member
            $membersQuery = User::query();
        } else {
            // User hanya melihat member yang mereka undang berdasarkan affiliate_id
            $membersQuery = User::where('affiliate_id', $user->username);
        }

        $search = $request->input('search');
        if ($search) {
            $membersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('affiliate_id', 'like', "%{$search}%");
            });
        }

        $members = $membersQuery->paginate($perPage);

        return view('dashboard.member.index', compact('members'));
    }


    // Menghapus peserta
    public function destroy($id)
    {
        $member = Participant::find($id);

        // Cek apakah peserta ada
        if (!$member) {
            return back()->with('error', 'Peserta tidak ditemukan.');
        }

        // Ambil user yang sedang login
        $user = Auth::user();

        // Admin boleh hapus siapa saja, user hanya boleh hapus peserta yang mereka undang
        if ($user->role !== 'admin' && $member->affiliate_id !== $user->username) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus peserta ini.');
        }

        // Simpan dulu jenis event untuk redirect nanti
        $eventType = $member->event_type;

        // Hapus peserta
        $member->delete();

        // Tentukan rute redirect sesuai jenis event
        $redirectRoute = $eventType === 'workshop' ? 'members.workshop' : 'members.index';

        return redirect()->route($redirectRoute)->with('success', 'Peserta berhasil dihapus!');
    }
    // Menghapus user/member
    public function delete($id)
    {
        $member = User::find($id);

        if (!$member) {
            return back()->with('error', 'Member tidak ditemukan.');
        }

        $user = Auth::user();

        // Cegah admin menghapus dirinya sendiri
        if ($user->id === $member->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        // Admin boleh hapus siapa saja, user hanya boleh hapus yang mereka undang
        if ($user->role !== 'admin' && $member->affiliate_id !== $user->username) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus member ini.');
        }

        $member->delete();

        return redirect()->route('members.member')->with('success', 'Member berhasil dihapus!');
    }
    // Mengunduh daftar peserta dalam format Excel
    public function exportCSV(Request $request)
    {
        if (request()->routeIs('export.csv')) {
            // Cek asal halaman melalui referer atau tambahkan ?type=webinar misalnya
            $eventType = $request->query('type', 'webinar'); // default webinar
            $filename = 'lead-' . $eventType . '.csv';

            return Excel::download(new ParticipantsExport($eventType), $filename);
        }

        abort(404);
    }

    // Mengunduh daftar member dalam format Excel
    public function exportUsersCSV()
    {
        return Excel::download(new UsersExport, 'users.csv');
    }
}
