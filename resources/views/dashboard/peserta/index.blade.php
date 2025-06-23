@extends('layouts.admin')

@section('title', 'Daftar Peserta')

@section('content')

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                         @if(Auth::check() && Auth::user()->role === 'admin')
                        <h4 class="page-title">Daftar Peserta</h4>
                        @elseif (Auth::check() && Auth::user()->role === 'user')
                        <h4 class="page-title">My Lead</h4>
                        @endif
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                             @if(Auth::check() && Auth::user()->role === 'admin')
                            <li class="nav-item"><a href="#">Peserta</a></li>
                            @elseif (Auth::check() && Auth::user()->role === 'user')
                            <li class="nav-item"><a href="#">{{ Request::is('lead-workshop') ? 'My Lead Workshop' : 'My Lead Webinar' }}</a></li>
                            @endif
                        </ul>
                    </div>
                    <div class="page-category">
                        <div class="page-inner">
                            <div class="row">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center w-100">
                                            <h4 class="card-title me-auto">{{ Request::is('lead-workshop') ? 'Daftar Lead Workshop' : 'Daftar Lead Webinar' }}</h4>

                                            <!-- Form pencarian -->
                                            <form method="GET"
                                                action="{{ Request::is('lead-workshop') ? route('members.workshop') : route('members.index') }}"
                                                class="d-flex align-items-center">
                                                <div class="input-group input-group-sm w-auto me-2">
                                                    <span class="input-group-text bg-white border-end-0 px-2">
                                                        <i class="fa fa-search"></i>
                                                    </span>
                                                    <input type="text" name="search" value="{{ request('search') }}"
                                                        class="form-control form-control-sm border-start-0"
                                                        placeholder="Cari..." id="search">
                                                </div>

                                                <!-- Jika tidak ada pencarian, tampilkan tombol submit -->
                                                @if (!request('search'))
                                                    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                                                @endif

                                                <!-- Jika ada pencarian, tampilkan tombol reset -->
                                                @if (request('search'))
                                                    <a href="{{ Request::is('lead-workshop') ? route('members.workshop') : route('members.index') }}"
                                                        class="btn btn-sm btn-outline-secondary ms-2">Reset</a>
                                                @endif
                                            </form>

                                            <div class="dropdown ms-2">
                                                <button class="btn btn-icon btn-clean me-0" type="button"
                                                    id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end"
                                                    aria-labelledby="dropdownMenuButton">
                                                    @php
                                                        if (request()->routeIs('members.index')) {
                                                            $exportRoute = route('export.csv', ['type' => 'webinar']);
                                                        } elseif (request()->routeIs('members.workshop')) {
                                                            $exportRoute = route('export.csv', ['type' => 'workshop']);
                                                        } else {
                                                            $exportRoute = route('users.csv');
                                                        }
                                                    @endphp

                                                    <a class="dropdown-item" href="{{ $exportRoute }}">Export CSV</a>
                                                    <a class="dropdown-item" href="#">Print PDF</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">

                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Nama</th>
                                                        <th>Email</th>
                                                        <th>WhatsApp</th>
                                                        <th>Kota</th>
                                                        <th>Bisnis</th>
                                                        <th>Pengundang</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        // Menghitung offset berdasarkan halaman saat ini
                                                        $offset = ($members->currentPage() - 1) * $members->perPage();
                                                    @endphp

                                                    @forelse($members as $index => $member)
                                                        <tr>
                                                            <td>{{ $index + $offset + 1 }}</td>
                                                            <td>{{ $member->name }}</td>
                                                            <td>{{ $member->email }}</td>
                                                            <td>{{ $member->whatsapp }}</td>
                                                            <td>{{ $member->city }}</td>
                                                            <td>{{ $member->business }}</td>
                                                            <td>{{ $member->affiliate_id }}</td>
                                                            <td>
                                                                <!-- Delete button -->
                                                                <form id="deleteForm_{{ $member->id }}"
                                                                    action="{{ route('members.destroy', $member->id) }}"
                                                                    method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="button" class="btn btn-danger btn-sm"
                                                                        onclick="confirmDelete({{ $member->id }})">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="8" class="text-center fw-bold text-muted">
                                                                Anda tidak memiliki lead saat ini.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>


                                        <!-- Menampilkan navigasi halaman -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <!-- Dropdown jumlah per halaman -->
                                            <form method="GET" action="" class="d-flex align-items-center">
                                                <input type="hidden" name="search" value="{{ request('search') }}">
                                                <label for="perPage" class="me-2 mb-0">Tampilkan:</label>
                                                <select name="perPage" id="perPage"
                                                    class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                                    @foreach ([15, 25, 50, 100] as $size)
                                                        <option value="{{ $size }}"
                                                            {{ request('perPage', 15) == $size ? 'selected' : '' }}>
                                                            {{ $size }}</option>
                                                    @endforeach
                                                </select>
                                            </form>

                                            <!-- Pagination -->
                                            <div>
                                                {{ $members->appends(['search' => request('search'), 'perPage' => request('perPage', 15)])->links() }}
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
    <script>
        function confirmDelete(id) {
            swal({
                title: "Yakin ingin menghapus?",
                text: "Data yang sudah dihapus tidak bisa dikembalikan!",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Batal",
                        visible: true,
                        className: "btn btn-secondary"
                    },
                    confirm: {
                        text: "Ya, hapus!",
                        className: "btn btn-danger"
                    }
                },
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    // Submit form jika user konfirmasi
                    document.getElementById('deleteForm_' + id).submit();
                } else {
                    swal("Data tidak jadi dihapus.", {
                        icon: "info",
                        buttons: {
                            confirm: {
                                className: "btn btn-success"
                            }
                        }
                    });
                }
            });
        }
    </script>
    @include('dashboard.partials.scripts')

@endsection
