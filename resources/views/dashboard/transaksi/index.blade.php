@extends('layouts.admin')

@section('title', $pageTitle) {{-- Menggunakan variabel $pageTitle dari controller --}}

@section('content')

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        {{-- Judul Halaman Dinamis berdasarkan role --}}
                        <h4 class="page-title">{{ $pageTitle }}</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a
                                    href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard') }}"><i
                                        class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Transaksi</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">{{ $pageTitle }}</a></li>
                        </ul>
                    </div>
                    <div class="page-category">
                        <div class="page-inner">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="d-flex align-items-center w-100">
                                                <h4 class="card-title me-auto">Daftar {{ $pageTitle }}</h4>

                                                @if (Auth::user()->role === 'admin')
                                                    {{-- Filter Dropdown hanya untuk Admin --}}
                                                    <div class="form-group mb-0 me-2">
                                                        <select class="form-control" id="filterType">
                                                            <option value="">Semua Jenis Transaksi</option>
                                                            <option value="event"
                                                                {{ $filterType == 'event' ? 'selected' : '' }}>Event
                                                            </option>
                                                            <option value="product"
                                                                {{ $filterType == 'product' ? 'selected' : '' }}>Produk
                                                            </option>
                                                        </select>
                                                    </div>
                                                @endif

                                                <div class="dropdown">
                                                    <button class="btn btn-icon btn-clean me-0" type="button"
                                                        id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end"
                                                        aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Export CSV</a>
                                                        <a class="dropdown-item" href="#">Print PDF</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="basic-datatables"
                                                    class="display table table-striped table-hover">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>Tanggal</th>
                                                            <th>Order ID</th>
                                                            @if (Auth::user()->role === 'admin')
                                                                <th>Nama Customer</th>
                                                            @endif
                                                            <th>Jenis Transaksi</th>
                                                            <th>Nama Item</th>
                                                            <th>Jumlah</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($transactions as $transaction)
                                                            <tr>
                                                                <td>{{ $transaction->created_at->format('d M Y H:i') }}
                                                                </td>
                                                                <td>{{ $transaction->order_id }}</td>
                                                                @if (Auth::user()->role === 'admin')
                                                                    <td>
                                                                        @if ($transaction->participant)
                                                                            {{ $transaction->participant->name }}
                                                                        @elseif ($transaction->user)
                                                                            {{ $transaction->user->name }}
                                                                        @else
                                                                            N/A
                                                                        @endif
                                                                    </td>
                                                                @endif
                                                                <td>
                                                                    @php
                                                                        $transactionType = 'Lainnya';
                                                                        // Logika deteksi yang sudah kita perbaiki, dengan prioritas:
                                                                        // 1. Format PROD- (baru)
                                                                        // 2. Format prod-order- (lama)
                                                                        // 3. Format ORD- (event)
                                                                        if (
                                                                            Str::startsWith(
                                                                                $transaction->order_id,
                                                                                'PROD-',
                                                                            )
                                                                        ) {
                                                                            $transactionType = 'Produk';
                                                                        } elseif (
                                                                            Str::startsWith(
                                                                                $transaction->order_id,
                                                                                'prod-order-',
                                                                            )
                                                                        ) {
                                                                            $transactionType = 'Produk';
                                                                        } elseif (
                                                                            Str::startsWith(
                                                                                $transaction->order_id,
                                                                                'ORD-',
                                                                            )
                                                                        ) {
                                                                            $transactionType = 'Event';
                                                                        }
                                                                    @endphp
                                                                    {{ $transactionType }}
                                                                </td>
                                                                <td>
                                                                    @if ($transaction->participant)
                                                                        {{ ucfirst($transaction->participant->event_type) }}
                                                                        Event
                                                                    @elseif ($transaction->order && $transaction->order->product)
                                                                        {{ $transaction->order->product->name }}
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td>Rp
                                                                    {{ number_format($transaction->amount, 0, ',', '.') }}
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $badgeClass = '';
                                                                        switch ($transaction->status) {
                                                                            case 'paid':
                                                                                $badgeClass = 'badge-success';
                                                                                break;
                                                                            case 'pending':
                                                                                $badgeClass = 'badge-warning';
                                                                                break;
                                                                            case 'failed':
                                                                                $badgeClass = 'badge-danger';
                                                                                break;
                                                                            default:
                                                                                $badgeClass = 'badge-info';
                                                                                break;
                                                                        }
                                                                    @endphp
                                                                    <span
                                                                        class="badge {{ $badgeClass }}">{{ ucfirst($transaction->status) }}</span>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="{{ Auth::user()->role === 'admin' ? '7' : '6' }}"
                                                                    class="text-center fw-bold text-muted">
                                                                    Tidak ada catatan transaksi yang ditemukan.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
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

    <script>
        $(document).ready(function() {
            $('#basic-datatables').DataTable({
                // Mengatur pengurutan awal berdasarkan kolom 'Tanggal' (kolom pertama)
                "order": [
                    [0, "desc"]
                ],
                // Aktifkan fitur dasar
                "paging": true,
                "searching": true,
                "info": true,
                // Pastikan DataTables mengerti format tanggal (opsional jika perlu)
                "columnDefs": [{
                    "targets": 0,
                    "type": "date" // pastikan DataTables tahu bahwa kolom ini adalah tanggal
                }]
            });

            // Dropdown filter (jika digunakan)
            $('#filterType').on('change', function() {
                var selectedType = $(this).val();
                var currentUrl = new URL(window.location.href);

                currentUrl.searchParams.delete('page');

                if (selectedType) {
                    currentUrl.searchParams.set('type', selectedType);
                } else {
                    currentUrl.searchParams.delete('type');
                }

                window.location.href = currentUrl.toString();
            });
        });
    </script>

@endsection
