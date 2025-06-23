<!-- resources/views/dashboard/produk/index.blade.php -->

@extends('layouts.admin')

@section('title', 'Landing Page Management')

@section('content')

    @php
        use App\Models\Product;
        use Illuminate\Support\Facades\Auth;

        // Mengambil semua produk dari database
        $products = Product::all();

        // Mengambil data pengguna yang sedang login
        $user = Auth::user();
    @endphp

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
                    <div class="container">
                        <div class="page-category">
                            <div class="page-inner">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center">
                                                    <h4 class="card-title">Follow Up</h4>
                                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal"
                                                        data-bs-target="#addRowModal">
                                                        <i class="fa fa-plus"></i>
                                                        Add Row
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <!-- Modal -->
                                                <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header border-0">
                                                                <h5 class="modal-title">
                                                                    <span class="fw-mediumbold"> New</span>
                                                                    <span class="fw-light"> Row </span>
                                                                </h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="small">
                                                                    Create a new row using this form, make sure you
                                                                    fill them all
                                                                </p>
                                                                <form action="submit" method="POST">
                                                                    @csrf
                                                                    <div class="modal-body">
                                                                                                                                                <!-- Nama Event -->
                                                                        <div class="form-group">
                                                                            <label for="name">Follow Up</label>
                                                                            <input type="text" class="form-control" id="event" name="event"
                                                                                placeholder="Nama Follow up" required />
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label for="name">Pesan</label>
                                                                            <input type="text" class="form-control" id="event" name="event"
                                                                                placeholder="Masukkan Pesan Follow up" required />
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label for="event_time">Hari Follow Up</label>
                                                                            <input type="number" class="form-control" id="event_time"
                                                                                name="event_time" placeholder="Follow up in Days" required />
                                                                        </div>
                                                                    </div>

                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary"
                                                                            data-bs-dismiss="modal">Tutup</button>
                                                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table id="add-row" class="display table table-striped table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Nama Reminder</th>
                                                                <th>Hari</th>
                                                                <th>Pesan Follow up</th>
                                                                <th style="width: 10%">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>

                                                            <tr>
                                                                <td>Colleen Hurst</td>
                                                                <td>Javascript Developer</td>
                                                                <td>San Francisco</td>
                                                                <td>
                                                                    <div class="form-button-action">
                                                                        <button type="button" data-bs-toggle="tooltip"
                                                                            title=""
                                                                            class="btn btn-link btn-primary btn-lg"
                                                                            data-original-title="Edit Task">
                                                                            <i class="fa fa-edit"></i>
                                                                        </button>
                                                                        <button type="button" data-bs-toggle="tooltip"
                                                                            title="" class="btn btn-link btn-danger"
                                                                            data-original-title="Remove">
                                                                            <i class="fa fa-times"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Sonya Frost</td>
                                                                <td>Software Engineer</td>
                                                                <td>Edinburgh</td>
                                                                <td>
                                                                    <div class="form-button-action">
                                                                        <button type="button" data-bs-toggle="tooltip"
                                                                            title=""
                                                                            class="btn btn-link btn-primary btn-lg"
                                                                            data-original-title="Edit Task">
                                                                            <i class="fa fa-edit"></i>
                                                                        </button>
                                                                        <button type="button" data-bs-toggle="tooltip"
                                                                            title="" class="btn btn-link btn-danger"
                                                                            data-original-title="Remove">
                                                                            <i class="fa fa-times"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
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
            </div>
            @include('dashboard.partials.footer')
        </div>
    </div>
    <script>
        // Menargetkan semua tombol dengan kelas .view-btn
        $(".view-btn").click(function(e) {
            swal("This modal will disappear soon!", {
                buttons: false,
                timer: 3000, // Waktu modal akan hilang setelah 3 detik
            });
        });

        $(".aff-btn").click(function(e) {
            swal({
                title: "Copied!",
                text: "Link berhasil di copy!",
                icon: "success",
                buttons: {
                    confirm: {
                        text: "Oke",
                        value: true,
                        visible: true,
                        className: "btn btn-success",
                        closeModal: true,
                    },
                },
            });
        });
    </script>


    @include('dashboard.partials.scripts')

@endsection
