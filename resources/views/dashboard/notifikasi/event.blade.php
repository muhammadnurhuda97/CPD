@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
    \Carbon\Carbon::setLocale('id');

    // Mengambil data pengguna yang sedang login
    $user = Auth::user();
@endphp

@extends('layouts.admin')

@section('title', 'Funnel Pendaftaran Event')

@section('content')

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Landing Page</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Follow Up</a></li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Funnel Link Afiliasi</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nama Event</th>
                                                    <th>Hari</th>
                                                    <th>Tanggal</th>
                                                    <th>Jam</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($notifications as $notification)
                                                    <tr>
                                                        <td>{{ $notification->id }}</td>
                                                        <td>{{ $notification->event }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($notification->event_date)->translatedFormat('l') }}
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::parse($notification->event_date)->translatedFormat('d F Y') }}
                                                        </td>
                                                        <td>{{ date('H.i', strtotime($notification->event_time)) }} WIB</td>
                                                        <td>
                                                            @if ($notification->event_type === 'webinar')
                                                                <button type="button"
                                                                    class="btn btn-primary btn-sm copy-link"
                                                                    data-link="{{ route('webinar.index', ['notification' => $notification->id, 'affiliate_id' => Auth::user()->username]) }}">
                                                                    Copy Link
                                                                </button>
                                                            @elseif($notification->event_type === 'workshop')
                                                                <button type="button"
                                                                    class="btn btn-primary btn-sm copy-link"
                                                                    data-link="{{ route('workshop.index', ['notification' => $notification->id, 'affiliate_id' => Auth::user()->username]) }}">
                                                                    Copy Link
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center">Tidak ada Event yang
                                                            tersedia.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Script untuk Copy Link --}}
                    <script>
                        document.querySelectorAll('.copy-link').forEach(function(button) {
                            button.addEventListener('click', function() {
                                var link = this.getAttribute('data-link');
                                navigator.clipboard.writeText(link).then(function() {
                                    swal({
                                        title: "Berhasil Disalin!",
                                        text: "Link afiliasi telah berhasil disalin.",
                                        icon: "success",
                                        buttons: {
                                            confirm: {
                                                text: "OK",
                                                className: "btn btn-success"
                                            }
                                        }
                                    });
                                }).catch(function() {
                                    swal("Gagal", "Link tidak bisa disalin.", "error");
                                });
                            });
                        });
                    </script>
                </div>
            </div>
            @include('dashboard.partials.footer')
        </div>
    </div>
    @include('dashboard.partials.scripts')


@endsection
