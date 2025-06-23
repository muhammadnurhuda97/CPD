@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
    @php
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Cache;
        use Illuminate\Support\Facades\Request;
        use Illuminate\Support\Facades\Auth;

        // Menghitung jumlah visitor berdasarkan cache atau database
        $visitorFile = storage_path('app/public/visitor.txt');
        $visitorCount = file_exists($visitorFile) ? file_get_contents($visitorFile) : 0;

        // Jika visitor ingin di-reset otomatis setiap bulan, tambahkan cron job:
        // echo "0" > storage/app/public/visitor.txt

        $username = Auth::user()->username;
        // Menghitung total lead dari data peserta terdaftar
        $leadCount = DB::table('participants')->where('affiliate_id', $username)->count();
    @endphp


    <div class="wrapper">

        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')


            <div class="container">
                <div class="page-inner">
                    <div class="row">
                        {{--  Card 1 --}}
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-globe"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Visitors</p>
                                                <h4 class="card-title">{{ $visitorCount }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{--  Card 2 --}}
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Members</p>
                                                <h4 class="card-title">{{ $totalUsers }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{--  Card 3 --}}
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Leads</p>
                                                <h4 class="card-title">{{ $leadCount }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{--  Card 4 --}}
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                                <i class="far fa-check-circle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Order</p>
                                                <p class="card-category"><b>Coming Soon</b></p>
                                                <!-- <h4 class="card-title">Coming Soon</h4> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{--  <div class="row">
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">User Statistics</div>
                                        <div class="card-tools">
                                            <a href="#" class="btn btn-label-success btn-round btn-sm me-2">
                                                <span class="btn-label">
                                                    <i class="fa fa-pencil"></i>
                                                </span>
                                                Export
                                            </a>
                                            <a href="#" class="btn btn-label-info btn-round btn-sm">
                                                <span class="btn-label">
                                                    <i class="fa fa-print"></i>
                                                </span>
                                                Print
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="min-height: 375px">
                                        <canvas id="statisticsChart"></canvas>
                                    </div>
                                    <div id="myChartLegend"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-primary card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Total Komisi</div>
                                        <div class="card-tools">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-label-light dropdown-toggle" type="button"
                                                    id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    Export
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <a class="dropdown-item" href="#">Action</a>
                                                    <a class="dropdown-item" href="#">Another action</a>
                                                    <a class="dropdown-item" href="#">Something else here</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-category">March 2025</div>
                                    <div class="mb-4 mt-2">
                                        <h1>Rp 10.578.000</h1>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-round">
                                <div class="card-body pb-0">
                                    <div class="h1 fw-bold float-end text-primary">+5%</div>
                                    <h2 class="mb-2">17</h2>
                                    <p class="text-muted">Users online</p>
                                    <div class="pull-in sparkline-fix">
                                        <div id="lineChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>  --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row card-tools-still-right">
                                        <h4 class="card-title">Referral Link</h4>
                                        <div class="card-tools">
                                            <button class="btn btn-icon btn-link btn-primary btn-xs">
                                                <span class="fa fa-angle-down"></span>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-primary btn-xs btn-refresh-card">
                                                <span class="fa fa-sync-alt"></span>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-primary btn-xs">
                                                <span class="fa fa-times"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="card-category">Gunakan link ini untuk menjadi Affiliate dan mendapatkan
                                        komisi
                                    </p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="table-responsive table-hover table-sales">
                                                <table class="table referral">
                                                    <tbody>
                                                        <!-- Referral Member -->
                                                        <tr>

                                                            <td class="text-start">Referral Member</td>
                                                            <td class="text-end">
                                                                <input type="text"
                                                                    value="{{ url('/register?affiliate_id=' . Auth::user()->username) }}"
                                                                    readonly>
                                                            </td>
                                                            <td class="text-end">
                                                                <button class="btn-custom btn-success"
                                                                    onclick="copyToClipboard('{{ url('/register?affiliate_id=' . Auth::user()->username) }}')">Copy</button>
                                                            </td>
                                                        </tr>

                                                        <!-- Undangan Webinar -->
                                                        <tr>

                                                            <td class="text-start">Undangan Webinar</td>
                                                            <td class="text-end">
                                                                <input type="text"
                                                                    value="{{ url('webinar-autosmart-marketing?affiliate_id=' . Auth::user()->username) }}"
                                                                    readonly>
                                                            </td>
                                                            <td class="text-end">
                                                                <button class="btn-custom btn-success"
                                                                    onclick="copyToClipboard('{{ url('webinar-autosmart-marketing?affiliate_id=' . Auth::user()->username) }}')">Copy</button>
                                                            </td>
                                                        </tr>

                                                        <!-- Undangan Workshop -->
                                                        <tr>

                                                            <td class="text-start">Undangan Workshop</td>
                                                            <td class="text-end">
                                                                <input type="text"
                                                                    value="{{ url('workshop-autosmart-marketing?affiliate_id=' . Auth::user()->username) }}"
                                                                    readonly>
                                                            </td>
                                                            <td class="text-end">
                                                                <button class="btn-custom btn-success"
                                                                    onclick="copyToClipboard('{{ url('workshop-autosmart-marketing?affiliate_id=' . Auth::user()->username) }}')">Copy</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                <script>
                                                    function copyToClipboard(text) {
                                                        navigator.clipboard.writeText(text).then(function() {
                                                            alert('Link copied to clipboard');
                                                        });
                                                    }
                                                </script>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mapcontainer">
                                                <div id="world-map" class="w-100" style="height: 300px"></div>
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

    <script src="{{ asset('js/demo.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#lineChart').sparkline([102, 109, 120, 99, 110, 105, 115], {
                type: 'line',
                height: '70',
                width: '100%',
                lineWidth: '2',
                lineColor: '#177dff',
                fillColor: 'rgba(23, 125, 255, 0.14)'
            });

            $('#lineChart2').sparkline([99, 125, 122, 105, 110, 124, 115], {
                type: 'line',
                height: '70',
                width: '100%',
                lineWidth: '2',
                lineColor: '#f3545d',
                fillColor: 'rgba(243, 84, 93, .14)'
            });

            $('#lineChart3').sparkline([105, 103, 123, 100, 95, 105, 115], {
                type: 'line',
                height: '70',
                width: '100%',
                lineWidth: '2',
                lineColor: '#ffa534',
                fillColor: 'rgba(255, 165, 52, .14)'
            });
        });
    </script>


@endsection
