<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class NotificationController extends Controller
{
    // Menampilkan semua notifikasi
    public function index()
    {
        $notifications = Notification::all();
        return view('dashboard.notifikasi.index', compact('notifications'));
    }

    // Menampilkan form untuk membuat notifikasi
    public function store(Request $request)
    {
        // Validasi input form
        // dd($request->all());
        $validated = $request->validate([
            'event_type' => 'required|string',
            'event' => 'required|string',
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            'zoom' => 'nullable|required_if:event_type,webinar|url',  // hanya wajib jika webinar
            'location' => 'nullable|required_if:event_type,workshop|url',  // hanya wajib jika workshop
            'location_name' => 'nullable|required_if:event_type,workshop|string',  // hanya wajib jika workshop
            'location_address' => 'nullable|required_if:event_type,workshop|string',
            'price' => 'nullable|required_if:is_paid,1|numeric|min:0', // harga wajib jika event berbayar
            'banner' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('banners', 'public');
        } else {
            $bannerPath = null;
        }
        // Menyimpan data notifikasi
        $notification = new Notification();
        $notification->user_id = auth()->user()->id;  // Menyimpan ID pengguna yang sedang login
        $notification->event_type = $validated['event_type'];
        $notification->event = $validated['event'];
        $notification->event_date = $validated['event_date'];
        $notification->event_time = $validated['event_time'];
        $notification->banner = $bannerPath; // Menyimpan path banner
        $notification->zoom = $validated['zoom'] ?? null;  // Jika zoom null, simpan null
        $notification->location = $validated['location'] ?? null;  // Jika location null, simpan null
        $notification->location_name = $validated['location_name'] ?? null;  // Jika location null, simpan null
        $notification->location_address = $validated['location_address'] ?? null;  // Jika location null, simpan null
        $notification->is_paid = $request->has('is_paid') ? 1 : 0; // Menyimpan apakah event berbayar
        $notification->price = $request->is_paid == 1 ? $validated['price'] : null; // Menyimpan harga jika event berbayar
        $notification->save();

        return redirect()->route('notifikasi.index')->with('success', 'Notifikasi berhasil ditambahkan.');
    }


    // Menampilkan form edit untuk notifikasi
    public function edit($id)
    {
        $notification = Notification::findOrFail($id);
        return view('dashboard.notifikasi.edit', compact('notification'));
    }

    // Mengupdate data notifikasi
     public function update(Request $request, $id)
    {
        // dd($request->all());
        $rules = [
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            // Banner tidak wajib, tapi kalau diupload harus valid image
            'banner' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if ($request->event_type === 'webinar') {
            $rules['zoom'] = 'required|url';
        }

        if ($request->event_type === 'workshop') {
            $rules['location'] = 'required|url';
            $rules['location_name'] = 'required|string|max:255';
            $rules['location_address'] = 'required|string|max:255';
        }

        if ($request->has('is_paid')) {
            $rules['price'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);

        $notification = Notification::findOrFail($id);

        $notification->event_date = $validated['event_date'];
        $notification->event_time = $validated['event_time'];

        $notification->zoom = $request->event_type === 'webinar' ? $validated['zoom'] : null;
        $notification->location = $request->event_type === 'workshop' ? $validated['location'] : null;
        $notification->location_name = $request->event_type === 'workshop' ? $validated['location_name'] : null;
        $notification->location_address = $request->event_type === 'workshop' ? $validated['location_address'] : null;

        $notification->is_paid = $request->has('is_paid') ? 1 : 0;
        $notification->price = $notification->is_paid ? $validated['price'] : null;

        // Jika ada file banner baru diupload
        if ($request->hasFile('banner')) {
            // Hapus banner lama jika ada
            if ($notification->banner) {
                Storage::disk('public')->delete($notification->banner);
            }

            // Simpan banner baru
            $bannerPath = $request->file('banner')->store('banners', 'public');
            $notification->banner = $bannerPath;
        }

        $notification->save();

        return redirect()->route('notifikasi.index')->with('success', 'Notifikasi berhasil diperbarui!');
    }

    // Menghapus notifikasi
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);

        if ($notification->banner && Storage::disk('public')->exists($notification->banner)) {
            Storage::disk('public')->delete($notification->banner);
        }

        $notification->delete();

        return redirect()->route('notifikasi.index')->with('success', 'Notifikasi berhasil dihapus!');
    }

    public function showRegistrationForm()
    {
        // Ambil notifikasi terbaru
        $notification = \App\Models\Notification::latest()->first();

        return view('form', compact('notification'));
    }
}
