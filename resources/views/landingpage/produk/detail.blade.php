<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
    <link rel="stylesheet" href="{{ asset('css/kaiadmin.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        /* Menghilangkan garis tepi dari accordion */
        .accordion-button {
            border: none;
            /* Menghilangkan border pada button */
        }

        .accordion-item {
            border: none;
            /* Menghilangkan border pada item accordion */
        }

        .accordion-button:not(.collapsed) {
            border-color: transparent;
            /* Menghilangkan border saat accordion dibuka */
        }

        .accordion-body {
            background-color: #c1d9f1;
            /* Warna latar belakang body accordion */
        }
    </style>
</head>

<body>

    <main>
        <div class="container mt-5">
            <div class="row">
                <div class="col-md-8 mt-5">
                    <div class="card">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card-body">
                                    <div class="img-produk mb-3">
                                        <img id="mainImage" src="{{ asset('storage/' . $product->image) }}"
                                            class="card-img-center img-fluid" alt="{{ $product->name }}">
                                    </div>

                                    <div class="row row-cols-4 g-2">
                                        <div class="col">
                                            <img src="{{ asset('storage/' . $product->image) }}"
                                                class="img-thumbnail thumb-image" style="cursor: pointer;"
                                                onclick="changeImage('{{ asset('storage/' . $product->image) }}')">
                                        </div>
                                        <div class="col">
                                            <img src="{{ asset('storage/' . $product->image) }}"
                                                class="img-thumbnail thumb-image" style="cursor: pointer;"
                                                onclick="changeImage('{{ asset('storage/' . $product->image) }}')">
                                        </div>
                                        <div class="col">
                                            <img src="{{ asset('storage/' . $product->image) }}"
                                                class="img-thumbnail thumb-image" style="cursor: pointer;"
                                                onclick="changeImage('{{ asset('storage/' . $product->image) }}')">
                                        </div>
                                        <div class="col">
                                            <img src="{{ asset('storage/' . $product->image) }}"
                                                class="img-thumbnail thumb-image" style="cursor: pointer;"
                                                onclick="changeImage('{{ asset('storage/' . $product->image) }}')">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $product->name }}</h5>
                                    {{-- @php
                                        $reviewCount = $product->reviews->count();
                                        $averageRating = $product->reviews->avg('rating'); // asumsinya rating dari 1-5
                                    @endphp

                                    <p class="card-text">
                                        @if ($reviewCount > 0)
                                            <small class="text-muted">
                                                {!! str_repeat('<i class="bi bi-star-fill text-warning"></i>', floor($averageRating)) !!}
                                                {!! str_repeat('<i class="bi bi-star text-warning"></i>', 5 - floor($averageRating)) !!}
                                                ({{ number_format($averageRating, 1) }} dari {{ $reviewCount }}
                                                ulasan)
                                            </small>
                                        @else
                                            <small class="text-muted">(Belum ada review)</small>
                                        @endif
                                    </p> --}}
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-half text-warning"></i>
                                            <i class="bi bi-star text-warning"></i>
                                            (4.5 dari 23 ulasan)
                                        </small>
                                    </p>


                                    <ul class="nav nav-tabs" id="productTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="desc-tab" data-bs-toggle="tab"
                                                data-bs-target="#desc" type="button" role="tab"
                                                aria-controls="desc" aria-selected="true">
                                                Deskripsi
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="review-tab" data-bs-toggle="tab"
                                                data-bs-target="#review" type="button" role="tab"
                                                aria-controls="review" aria-selected="false">
                                                Ulasan
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content mt-3" id="productTabContent">
                                        <div class="tab-pane fade show active" id="desc" role="tabpanel"
                                            aria-labelledby="desc-tab">
                                            <p class="card-text">{{ $product->description }}</p>
                                        </div>

                                        <div class="tab-pane fade" id="review" role="tabpanel"
                                            aria-labelledby="review-tab">
                                            <p class="card-text text-muted">Belum ada ulasan untuk produk ini.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


                <div class="col-md-4 mt-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td>Subtotal</td>
                                        <td class="text-end">{{ $product->formatted_price }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total</td>
                                        <td class="text-end"><strong>{{ $product->formatted_price }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                            <form
                                action="{{ route('payment.initiate', ['type' => 'product', 'identifier' => $product->slug]) }}"
                                method="GET">
                                <input type="hidden" name="affiliate_id"
                                    value="{{ request()->query('affiliate_id') }}">
                                <button type="submit" class="btn btn-primary w-100">Proceed to Checkout</button>
                            </form>

                            <div class="progress mt-3" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: 50%;" aria-valuenow="50"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="mt-2 text-center">Checkout Progress 2 of 4</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5 text-center">
        <div class="container-fluid footer justify-content-between">
            <div class="copyright">&copy; {{ date('Y') }} <a href="">Cipta Pemuda Digital</a>. All
                Rights Reserved.</div>
        </div>
    </footer>

    <script>
        function changeImage(src) {
            document.getElementById('mainImage').src = src;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>

</html>
