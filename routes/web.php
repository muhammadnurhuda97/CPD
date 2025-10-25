<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Notification; // Pastikan model Notification di-import

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebinarController;
use App\Http\Controllers\WorkshopController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==============================
// AUTH ROUTES (Laravel Breeze)
// ==============================
require __DIR__ . '/auth.php';

// ==============================
// PUBLIC ROUTES (No Auth Required)
// ==============================

// Halaman root
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('user.dashboard');
    }
    return view('auth.login');
});

// DIUBAH: Rute landing page sekarang menerima model Notifikasi berdasarkan ID-nya
Route::get('/webinar_id={notification}', [WebinarController::class, 'index'])->name('webinar.index');
Route::get('/workshop_id={notification}', [WorkshopController::class, 'index'])->name('workshop.index');

// DIUBAH: Rute form pendaftaran sekarang menerima model Notifikasi berdasarkan ID-nya
Route::get('/registrasi/webinar_id={notification}', function (Notification $notification) {
    (new VisitorController)->logVisitor();
    // Pastikan hanya event dengan tipe 'webinar' yang bisa diakses melalui rute ini
    abort_if($notification->event_type !== 'webinar', 404, 'Halaman webinar tidak tersedia.');
    return view('participants.form', compact('notification'));
})->name('webinar.form');

Route::get('/registrasi/workshop_id={notification}', function (Notification $notification) {
    // Pastikan hanya event dengan tipe 'workshop' yang bisa diakses melalui rute ini
    abort_if($notification->event_type !== 'workshop', 404, 'Halaman workshop tidak tersedia.');
    if ($notification->is_paid) {
        session()->flash('warning', 'Workshop ini berbayar. Silakan siapkan pembayaran sebesar Rp ' . number_format($notification->price, 0, ',', '.'));
    }
    return view('participants.workshop', compact('notification'));
})->name('workshop.form');

// Rute untuk mengupdate kuota, dipindahkan ke area publik
Route::post('/update-kuota', [VisitorController::class, 'updateQuota'])->name('update.kuota'); //


// Detail produk (publik)
Route::get('/produk/{slug}', [ProductController::class, 'show'])->name('products.detail');

// Submit form pendaftaran peserta
Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');

// Zoom link
Route::get('/link-zoom', fn() => view('link-zoom'));

// ==============================
// PAYMENT ROUTES
// ==============================
Route::get('/payment/initiate/{type}/{identifier}', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/check-status/{orderId}', [PaymentController::class, 'checkPaymentStatus'])->name('payment.checkStatus');
Route::get('/payment/error', [PaymentController::class, 'paymentErrorPage'])->name('payment.error.page');
Route::get('/payment/cancel/{orderId}', [PaymentController::class, 'cancelAndRetry'])->name('payment.cancel');


// Webhook Midtrans
Route::post('/webhook/midtrans', [PaymentController::class, 'handleMidtransCallback'])
    // ->middleware('verify.midtrans.ip')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ==============================
// USER ROUTES (Authenticated Users)
// ==============================
Route::middleware('auth')->group(function () {

    // Profil
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/transaksi', [TransactionController::class, 'index'])->name('transactions.index');

    // Cek kelengkapan profil
    Route::get('/check-profile-completion', function (Request $request) {
        Log::info("🟪 [ProfileCheck] Rute 'check.profile.completion' diakses.");
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        if (!$user->address || !$user->city || !$user->zip || !$user->whatsapp || !$user->date_of_birth) {
            Log::warning("🟪 [ProfileCheck] Profil '{$user->name}' TIDAK LENGKAP.");
            $intendedUrl = session('url.intended');
            if ($intendedUrl) session()->put('url.intended', $intendedUrl);
            return redirect()->route('profile.edit')->with('error', 'Mohon lengkapi data profil Anda sebelum melanjutkan.');
        }

        Log::info("🟪 [ProfileCheck] Profil '{$user->name}' SUDAH LENGKAP.");
        $intendedUrl = session()->pull('url.intended', route('user.dashboard'));
        return redirect($intendedUrl)->with('success', 'Profil Anda lengkap. Silakan lanjutkan.');
    })->name('check.profile.completion');

    // Notifikasi & Leads
    Route::get('/event-pendaftaran', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/funnel-pendaftaran', [NotificationController::class, 'funnel'])->name('notifikasi.event');
    Route::get('/lead-webinar', [MemberController::class, 'index'])->name('members.index');
    Route::get('/lead-workshop', [MemberController::class, 'indexWorkshop'])->name('members.workshop');
    Route::get('/member', [MemberController::class, 'indexMember'])->name('members.member');
    Route::delete('/lead/{id}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Produk
    Route::get('/produk', [ProductController::class, 'showProducts'])->name('products.index');

    // Flyer download
    Route::get('/download-flyer/{filename}', function ($filename) {
        $path = storage_path("app/public/images/flyer/{$filename}");
        abort_unless(File::exists($path), 404);
        return response()->download($path);
    })->where('filename', '.*');

    Route::get('/export-csv', [MemberController::class, 'exportCSV'])->name('export.csv');
});


Route::get('/get-copywriting', function (Request $request) {
    $target = $request->query('target');
    abort_unless(preg_match('/^flyer\d+\.txt$/', $target), 403);
    $path = storage_path('app/public/copywriting/' . $target);
    return response(File::exists($path) ? File::get($path) : '', 200)->header('Content-Type', 'text/plain');
});
// ==============================
// ADMIN ROUTES
// ==============================
Route::middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Produk admin
    Route::get('/produk-admin', fn() => view('dashboard.produk.index'))->name('admin.products.index');
    Route::get('/add-produk', [ProductController::class, 'create'])->name('products.create');
    Route::post('/add-produk', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Event & Notifikasi
    Route::get('/event-pendaftaran/edit/{id}', [NotificationController::class, 'edit'])->name('notifikasi.edit');
    Route::post('/event-pendaftaran', [NotificationController::class, 'store'])->name('notifikasi.store');
    Route::put('/event-pendaftaran/{id}', [NotificationController::class, 'update'])->name('notifikasi.update');
    Route::delete('/event-pendaftaran/{id}', [NotificationController::class, 'destroy'])->name('notifikasi.destroy');

    // Leads/member admin
    Route::delete('/member/{id}', [MemberController::class, 'delete'])->name('members.delete');
    Route::delete('/lead-webinar/{id}', [MemberController::class, 'destroy'])->name('admin.members.destroy.webinar');
    Route::delete('/lead-workshop/{id}', [MemberController::class, 'destroy'])->name('admin.members.destroy.workshop');
    Route::get('/export-members', [MemberController::class, 'exportUsersCSV'])->name('users.csv');

    // Upload flyer dan copywriting
    Route::post('/upload-flyer', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'target' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $image = $request->file('image');
        $filename = '';

        if ($request->filled('target')) {
            $target = basename($request->input('target'));
            $image->storeAs('public/images/flyer', $target);
            $filename = $target;
        } else {
            $files = File::files(storage_path('app/public/images/flyer'));
            $numbers = array_map(function ($file) {
                preg_match('/flyer(\d+)\.(jpg|jpeg|png)/', $file->getFilename(), $matches);
                return isset($matches[1]) ? (int)$matches[1] : 0;
            }, $files);
            $nextNumber = $numbers ? max($numbers) + 1 : 1;
            $filename = 'flyer' . $nextNumber . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/flyer', $filename);
            File::ensureDirectoryExists(storage_path('app/public/copywriting'));
            File::put(storage_path('app/public/copywriting/flyer' . $nextNumber . '.txt'), "");
        }

        return response()->json(['success' => true, 'new_image_url' => asset('storage/images/flyer/' . $filename)]);
    });

    Route::post('/save-copywriting', function (Request $request) {
        $request->validate([
            'text' => 'required|string',
            'target' => 'required|regex:/^flyer\d+\.txt$/',
        ]);
        File::ensureDirectoryExists(storage_path('app/public/copywriting'));
        File::put(storage_path('app/public/copywriting/' . $request->target), $request->text);
        return response()->json(['success' => true, 'message' => 'File successfully updated']);
    });
});
