<!-- resources/views/dashboard/produk/index.blade.php -->

@extends('layouts.admin')

@section('title', 'Daftar Produk')

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
                        <h4 class="page-title">Daftar Produk</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Produk</a></li>
                        </ul>
                    </div>
                    <div class="page-category">
                        <div class="page-inner">
                            <div class="card-body">
                                <!-- Grid for displaying products -->
                                <div class="row">
                                    @foreach ($products as $product)
                                        <div class="col-md-4 mb-4">
                                            <div class="card">
                                                <!-- Menampilkan gambar produk -->
                                                <img src="{{ asset('storage/' . $product->image) }}" class="card-img-top"
                                                    alt="{{ $product->name }}">
                                                <div class="card-body">
                                                    <h5 class="card-title">{{ $product->name }}</h5>
                                                    <p class="card-text">{{ $product->short_description }}</p>
                                                    <p class="card-text"><strong>Harga: Rp
                                                            {{ number_format($product->price, 0, ',', '.') }}</strong></p>
                                                    {{--  <a href="{{ url('/produk/' . $product->slug) }}" class="btn btn-link">Read More</a>  --}}
                                                    <!-- Tombol untuk melihat halaman penjualan -->
                                                    <button type="button" class="btn btn-primary view-btn">Sales
                                                        Page</button>
                                                    <!-- Tombol untuk checkout -->
                                                    <button type="button" class="btn btn-secondary aff-btn"
                                                        data-slug="{{ $product->slug }}">Link Checkout</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
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

        // Saat tombol aff-btn diklik
        $(".aff-btn").click(function() {
            // Ambil slug dari atribut data
            const slug = $(this).data("slug");

            // Buat URL lengkap checkout
            const checkoutUrl = `${window.location.origin}/produk/${slug}?affiliate_id={{ $user->username }}`;

            // Salin ke clipboard
            navigator.clipboard.writeText(checkoutUrl).then(function() {
                // Tampilkan pesan sukses
                swal({
                    title: "Copied!",
                    text: "Link berhasil di-copy!",
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
            }).catch(function(error) {
                // Jika gagal menyalin
                swal("Gagal menyalin link", error.message, "error");
            });
        });
    </script>


    @include('dashboard.partials.scripts')

@endsection
