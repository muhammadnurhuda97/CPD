{{-- resources/views/dashboard/peserta/commissions.blade.php --}}
@extends('layouts.admin')

@section('title', 'Laporan Komisi & Diskon Referral')

@section('content')

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Laporan Komisi & Diskon</h4>
                        {{-- Breadcrumbs --}}
                        <ul class="breadcrumbs">
                            <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Laporan</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Referral Peserta</a></li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Data Referral dari Peserta Event (Semua Status)</h4>
                                        {{-- Tombol filter/export jika perlu --}}
                                    </div>
                                </div>
                                <div class="card-body">
                                    {{-- Notifikasi Sukses/Error dari Aksi --}}
                                    @if (session('success'))
                                        <div class="alert alert-success">{{ session('success') }}</div>
                                    @endif
                                    @if (session('error'))
                                        <div class="alert alert-danger">{{ session('error') }}</div>
                                    @endif

                                    <p class="text-muted small mb-3">
                                        Laporan ini menampilkan semua peserta baru yang mendaftar melalui link referral
                                        peserta lain pada event berbayar.
                                    </p>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Tgl Daftar</th>
                                                    <th>Event</th>
                                                    <th>Peserta Baru (Invitee)</th>
                                                    <th>Diundang Oleh (Inviter)</th>
                                                    <th>Diskon Inviter</th>
                                                    <th>Affiliate Asal</th>
                                                    <th>Komisi Affiliate</th>
                                                    <th>Status Bayar</th> {{-- KOLOM BARU --}}
                                                    <th>Aksi</th> {{-- KOLOM BARU --}}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($referrals as $referral)
                                                    <tr>
                                                        <td>{{ $referral->created_at->format('d M Y H:i') }}</td>
                                                        <td>{{ optional($referral->notification)->event ?? 'N/A' }}</td>
                                                        <td>
                                                            {{ $referral->name }} <br>
                                                            <small class="text-muted">{{ $referral->whatsapp }}</small>
                                                        </td>
                                                        <td>
                                                            @if ($referral->referrer)
                                                                {{ $referral->referrer->name }} <br>
                                                                <small
                                                                    class="text-muted">{{ $referral->referrer->whatsapp }}</small>
                                                            @else
                                                                <span class="text-danger small">Data Pengundang
                                                                    Hilang</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">
                                                            Rp
                                                            {{ number_format(optional($referral->notification)->referral_discount_amount ?? 0, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            @if ($referral->referrer && $referral->referrer->affiliateUser)
                                                                {{ $referral->referrer->affiliateUser->name }}
                                                                <br><small
                                                                    class="text-muted">{{ $referral->referrer->affiliateUser->username }}</small>
                                                            @else
                                                                <span class="text-muted small">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">
                                                            Rp
                                                            {{ number_format(optional($referral->notification)->participant_referral_commission ?? 0, 0, ',', '.') }}
                                                        </td>
                                                        <td>
                                                            {{-- Tampilkan Status Pembayaran Invitee --}}
                                                            @php
                                                                $status = $referral->payment_status;
                                                                $badgeClass = 'badge-secondary'; // default
                                                                if ($status === 'paid') {
                                                                    $badgeClass = 'badge-success';
                                                                } elseif ($status === 'pending_cash_verification') {
                                                                    $badgeClass = 'badge-warning';
                                                                } elseif (
                                                                    in_array($status, ['pending', 'pending_choice'])
                                                                ) {
                                                                    $badgeClass = 'badge-info';
                                                                } elseif (in_array($status, ['failed', 'cancelled'])) {
                                                                    $badgeClass = 'badge-danger';
                                                                }
                                                            @endphp
                                                            <span
                                                                class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                                            @if ($referral->payment_method)
                                                                <br><small
                                                                    class="text-muted">({{ ucfirst($referral->payment_method) }})</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{-- ===== AWAL BLOK KONDISIONAL DIPERBAIKI ===== --}}
                                                            @if ($status === 'pending_cash_verification')
                                                                {{-- Tombol ACC untuk status menunggu verifikasi tunai --}}
                                                                <form
                                                                    action="{{ route('admin.referral.approve', $referral->id) }}"
                                                                    method="POST"
                                                                    style="display: inline-block; margin-bottom: 5px;">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-success btn-sm"
                                                                        title="Konfirmasi Bayar Tunai">
                                                                        <i class="fas fa-check"></i> ACC
                                                                    </button>
                                                                </form>
                                                                {{-- Tombol Tolak untuk status menunggu verifikasi tunai --}}
                                                                <form
                                                                    action="{{ route('admin.referral.cancel', $referral->id) }}"
                                                                    method="POST" style="display: inline-block;">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                                        title="Batalkan Referral"
                                                                        onclick="return confirm('Yakin ingin membatalkan referral ini?')">
                                                                        <i class="fas fa-times"></i> Tolak
                                                                    </button>
                                                                </form>
                                                            @elseif ($status === 'paid')
                                                                {{-- Teks jika sudah terbayar --}}
                                                                <span class="text-success fw-bold"><i
                                                                        class="fas fa-check-circle"></i> Terbayar</span>
                                                            @elseif (in_array($status, ['failed', 'cancelled']))
                                                                {{-- Teks jika gagal atau dibatalkan --}}
                                                                <span class="text-danger"><i
                                                                        class="fas fa-times-circle"></i> Batal</span>
                                                            @else
                                                                {{-- Teks jika masih pending online (pending, pending_choice) --}}
                                                                <span class="text-info small">Menunggu Pembayaran
                                                                    Online</span>
                                                                {{-- Opsional: Tombol Batal Manual untuk status pending online --}}
                                                                {{--
                                                                 <form action="{{ route('admin.referral.cancel', $referral->id) }}" method="POST" style="display: inline-block; margin-left: 5px;">
                                                                     @csrf
                                                                     <button type="submit" class="btn btn-warning btn-xs" title="Batalkan Manual" onclick="return confirm('Yakin ingin membatalkan referral yang menunggu pembayaran online ini?')">
                                                                         <i class="fas fa-ban"></i> Batal
                                                                     </button>
                                                                 </form>
                                                                 --}}
                                                            @endif {{-- Ini adalah @endif yang hilang --}}
                                                            {{-- ===== AKHIR BLOK KONDISIONAL DIPERBAIKI ===== --}}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" class="text-center py-4"> {{-- Sesuaikan colspan --}}
                                                            <span class="text-muted fw-bold">Belum ada data referral dari
                                                                peserta.</span>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Pagination Links --}}
                                    <div class="mt-4 d-flex justify-content-center">
                                        {{ $referrals->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @include('dashboard.partials.footer')
        </div>
    </div>
    @include('dashboard.partials.scripts')

@endsection
