@php
    // DIHAPUS: Blok kode ini tidak lagi diperlukan.
    // Variabel $notification, $formattedEventDate, dan $eventDatetime
    // sekarang dikirim langsung dari controller.
    // \Carbon\Carbon::setLocale('id');
    // $notification = \App\Models\Notification::where('event_type', 'webinar')->latest()->first();

    // Variabel affiliateId kini juga dikirim dari controller,
    // namun kita bisa mengambilnya lagi di sini untuk memastikan link tetap benar.
    $affiliateId = request()->query('affiliate_id');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Webinar Autosmart Marketing</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />


    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ asset('css/flaticon.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome-all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/themify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <style>
        .pricing-card-area .single-card .card-top p {
            text-transform: uppercase;
        }

        .blink {
            animation: blink-animation 1s steps(2, start) infinite;
        }

        @keyframes blink-animation {
            to {
                visibility: hidden;
            }
        }
    </style>
</head>

<body>
    <div id="preloader-active">
        <div class="preloader d-flex align-items-center justify-content-center">
            <div class="preloader-inner position-relative">
                <div class="preloader-circle"></div>
                <div class="preloader-img pere-text">
                    <img src="{{ asset('img/kaiadmin/favicon.ico') }}" alt="" />
                </div>
            </div>
        </div>
    </div>
    <main>
        <div class="slider-area position-relative">
            <div class="slider-active">
                <div class="single-slider slider-height d-flex align-items-center">
                    <div class="container">
                        <div class="row">
                            <div class="col-xl-8 col-lg-8 col-md-9 col-sm-10">
                                <div class="hero__caption">
                                    <span data-animation="fadeInLeft" data-delay=".1s">{{ $notification->event }}</span>
                                    <h1 data-animation="fadeInLeft" data-delay=".5s">
                                        Solusi Mendapatkan 10.000 Calon Pelanggan dengan Cepat, Mudah & Otomatis!
                                    </h1>
                                    <div class="slider-btns">
                                        <a data-animation="fadeInLeft" data-delay="1.0s" href="#daftar"
                                            class="btn hero-btn">Daftar Sekarang</a>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="counter-section d-none d-sm-block">
                <div class="cd-timer" id="countdown">
                    <div class="cd-item">
                        <span>00</span>
                        <p>Days</p>
                    </div>
                    <div class="cd-item">
                        <span>00</span>
                        <p>Hrs</p>
                    </div>
                    <div class="cd-item">
                        <span>00</span>
                        <p>Min</p>
                    </div>
                    <div class="cd-item">
                        <span>00</span>
                        <p>Sec</p>
                    </div>
                </div>
            </div>
        </div>
        <section class="about-low-area section-padding2">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <div class="about-caption mb-50">
                            <div class="section-tittle mb-35">
                                <h2>Kuasai Strategi Marketing yang Efektif!</h2>
                            </div>
                            <p>
                                <span>💥 Siapa saja yang cocok ikut acara ini?</span>


                            <div class="container mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul>
                                            <li><i class="fas fa-check-circle"></i> Pebisnis MLM</li>
                                            <li><i class="fas fa-check-circle"></i> Sales Mobil/Motor</li>
                                            <li><i class="fas fa-check-circle"></i> Koperasi, LPK/LKP, UMKM</li>
                                            <li><i class="fas fa-check-circle"></i> Affiliate Marketing dll.</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul>
                                            <li><i class="fas fa-check-circle"></i> Sales & Marketing</li>
                                            <li><i class="fas fa-check-circle"></i> Bisnis Owner</li>
                                            <li><i class="fas fa-check-circle"></i> Agen/Pemilik Travel Umroh</li>
                                            <li><i class="fas fa-check-circle"></i> Agen Property</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            </p>
                            <p>Saatnya mengubah cara Anda berbisnis dengan strategi Autosmart Marketing yang
                                <strong>TERBUKTI</strong> berhasil!
                            </p>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                                <div class="single-caption mb-20">
                                    <div class="caption-icon">
                                        <span class="flaticon-communications-1"></span>
                                    </div>
                                    <div class="caption">
                                        <p style="font-size:14px" class="mb-1">Where</p>
                                        <h5>Online via Zoom</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10">
                                <div class="single-caption mb-20">
                                    <div class="caption-icon">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                    <div class="caption">
                                        <p style="font-size:14px" class="mb-1">When</p>
                                        {{-- Variabel $formattedEventDate dikirim dari controller --}}
                                        <h5>{{ $formattedEventDate }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="#daftar" class="btn mt-50">Get Ticket</a>
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <div class="about-img">
                            <div class="about-font-img d-none d-lg-block">
                                <img src="{{ asset('img/kaiadmin/about2.png') }}" alt="" />
                            </div>
                            <div class="about-back-img">
                                <img src="{{ asset('img/kaiadmin/about1.png') }}" alt="" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        {{-- Sisa konten file tetap sama --}}
        <section class="team-area pt-180 pb-100 section-bg"
            data-background="{{ asset('img/kaiadmin/section_bg02.png') }}">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-9">
                        <div class="section-tittle section-tittle2 mb-50">
                            <h2>Mengapa Harus Ikut Webinar Ini?</h2>
                            <p>100% praktek tanpa teori, dapatkan strategi siap pakai yang langsung bisa Anda terapkan
                                dan lihat hasilnya! Daftar sekarang sebelum kuota penuh dan jangan lewatkan kesempatan
                                emas yang bisa mengubah arah bisnis Anda selamanya!</p>
                            <a href="#daftar" class="btn white-btn mt-30">Daftar Sekarang!</a>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-6"></div>
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-team mb-30">
                            <div class="team-img">
                                <img src="{{ asset('img/kaiadmin/team2.webp') }}" alt="" />
                                <ul class="team-social">
                                    <li>
                                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    </li>
                                    <li>
                                        <a href="#"><i class="fab fa-twitter"></i></a>
                                    </li>
                                    <li>
                                        <a href="#"><i class="fas fa-globe"></i></a>
                                    </li>
                                </ul>
                            </div>
                            <div class="team-caption">
                                <h3><a href="#">Moch. Fiki</a></h3>
                                <p>Digitals Marketing Spesialis</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
        <section class="accordion fix section-padding30">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-6 col-md-6">
                        <div class="section-tittle text-center mb-50">
                            <h2>Apa yang Akan Anda Pelajari?</h2>
                            <p>
                                Strategi real dan terbukti: Bangun bisnis, dapatkan ribuan pelanggan, promosikan
                                otomatis, dan raih 10 juta pertama — tanpa iklan dan tanpa modal!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                        aria-labelledby="nav-home-tab">
                        <div class="row">
                            <div class="col-lg-11">
                                <div class="accordion-wrapper">
                                    <div class="accordion" id="accordionExample">
                                        <div class="card">
                                            <div class="card-header" id="headingOne">
                                                <h2 class="mb-0">
                                                    <a href="#" class="btn-link" data-toggle="collapse"
                                                        data-target="#collapseOne" aria-expanded="true"
                                                        aria-controls="collapseOne">
                                                        <span>🎯 3 Point Penting yang Wajib Diketahui Para Pelaku
                                                            Bisnis</span>
                                                    </a>
                                                </h2>
                                            </div>
                                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                                                data-parent="#accordionExample">
                                                <div class="card-body">
                                                    Pahami 3 pondasi utama dalam menjalankan bisnis agar tidak salah
                                                    langkah. Ini adalah dasar yang sering dilewatkan tapi sangat krusial
                                                    untuk pertumbuhan usaha.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-header" id="headingTwo">
                                                <h2 class="mb-0">
                                                    <a href="#" class="btn-link collapsed"
                                                        data-toggle="collapse" data-target="#collapseTwo"
                                                        aria-expanded="false" aria-controls="collapseTwo">
                                                        <span>🎯 Praktek Langsung Cara Mendapatkan 10.000 Calon
                                                            Pelanggan</span>
                                                    </a>
                                                </h2>
                                            </div>
                                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                                data-parent="#accordionExample">
                                                <div class="card-body">
                                                    Belajar step by step strategi jitu dan praktek nyata untuk
                                                    menjangkau ribuan calon pelanggan potensial secara organik dan
                                                    tertarget.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-header" id="headingThree">
                                                <h2 class="mb-0">
                                                    <a href="#" class="btn-link collapsed"
                                                        data-toggle="collapse" data-target="#collapseThree"
                                                        aria-expanded="false" aria-controls="collapseThree">
                                                        <span>🎯 Praktek Langsung Promosi Otomatis (BUKAN FB ADS)</span>
                                                    </a>
                                                </h2>
                                            </div>
                                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                                data-parent="#accordionExample">
                                                <div class="card-body">
                                                    Otomatiskan promosi tanpa harus mengandalkan iklan berbayar seperti
                                                    Facebook Ads. Gunakan tools dan trik promosi yang efisien namun
                                                    tetap powerful.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-header" id="headingFour">
                                                <h2 class="mb-0">
                                                    <a href="#" class="btn-link collapsed"
                                                        data-toggle="collapse" data-target="#collapseFour"
                                                        aria-expanded="false" aria-controls="collapseFour">
                                                        <span>🎯 Cara Mendapatkan 10 Juta Pertama dari Internet TANPA
                                                            MODAL</span>
                                                    </a>
                                                </h2>
                                            </div>
                                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour"
                                                data-parent="#accordionExample">
                                                <div class="card-body">
                                                    Temukan strategi dan jalur yang realistis untuk meraih penghasilan
                                                    pertamamu dari internet, tanpa perlu modal besar atau alat mahal.
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
        </section>
        <section class="testi">

            <div class="section-padding">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8 col-md-10 text-center">
                            <h2 class="mb-4">Apa Kata Mereka?</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card p-3 shadow-sm">
                                <div class="card-body">
                                    <p class="card-text">"Layanan ini sangat membantu bisnis saya! Kini saya bisa
                                        menjangkau lebih banyak pelanggan tanpa repot."</p>
                                    <h5 class="card-title mt-3">- Andi, Pengusaha</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 shadow-sm">
                                <div class="card-body">
                                    <p class="card-text">"Strategi yang diajarkan sangat efektif. Open rate meningkat
                                        dan pelanggan lebih responsif!"</p>
                                    <h5 class="card-title mt-3">- Siti, Digital Marketer</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 shadow-sm">
                                <div class="card-body">
                                    <p class="card-text">"Saya bisa mengotomatiskan banyak tugas. Waktu lebih efisien
                                        dan hasil lebih optimal!"</p>
                                    <h5 class="card-title mt-3">- Budi, Pemilik Toko Online</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pricing-card-area section-padding2">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-8 col-sm-10">
                        <div class="section-tittle text-center">
                            <h2 id="daftar">Jangan Sampai Ketinggalan</h2>
                            <p>Webinar ini bisa jadi titik balik kesuksesan bisnis Anda. Strategi yang terbukti efektif
                                sudah membantu banyak orang, sekarang saatnya Anda meraih hasil yang sama!</p>
                        </div>
                    </div>
                </div>
                <div class="pricing-card-area">
                    <div class="single-card active text-center">
                        <div class="card-top">
                            <h4 class="text-warning">Hanya untuk 25 Orang Terpilih Rp <span
                                    style="color: #ffffff;"><s>499.000</s>!</span>⚡</h4>
                            <span class="small-text text-light">
                                Sisa kuota tinggal <strong class="text-warning blink"
                                    id="remainingQuota">Memuat...</strong>
                            </span><br>
                            <p class="card-price display-6 fw-bold mt-2"
                                style="text-transform: capitalize; color: #00FFB2;">
                                @if ($notification->is_paid)
                                    Rp<span>{{ number_format($notification->price, 0, ',', '.') }}</span>
                                @else
                                    <span>Gratis</span>
                                @endif
                            </p>

                            <p class="text-light mt-2">Investasi sekali, manfaat berkali-kali.</p>
                        </div>
                        <div class="card-bottom">
                            <ul>
                                <li>FREE CERTIFICATE FOR CV</li>
                                <li>Grup Mentorship Eksklusif</li>
                                <li>Sesi Q&A Interaktif </li>
                                <li>Pelajari Strategi Sukses dari Internet Millionaire</li>
                            </ul>
                            {{-- DIUBAH: Link pendaftaran sekarang menyertakan ID notifikasi yang spesifik --}}
                            @php
                                // Siapkan parameter dasar untuk rute form
                                $formRouteParams = [
                                    'notification' => $notification->id,
                                    'affiliate_id' => $affiliateId, // Sertakan affiliate_id jika perlu
                                ];
                                // Jika $ref_p ada dari controller landing page, tambahkan ke parameter
                                if (isset($ref_p) && !empty($ref_p)) {
                                    $formRouteParams['ref_p'] = $ref_p;
                                }
                            @endphp
                            <a href="{{ route('webinar.form', $formRouteParams) }}" class="black-btn">Daftar
                                Sekarang</a>
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <section class="work-company section-padding30" style="background: #2e0e8c">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-5 col-md-8">
                        <div class="section-tittle section-tittle2 mb-50">
                            <h2>Our Top Genaral Patners.</h2>
                            <p>Bersama mitra terbaik, kami menghadirkan solusi pemasaran yang lebih efektif dan
                                inovatif.</p>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="logo-area">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-logo mb-30">
                                        <img src="/img/kaiadmin/cisco_brand.png" alt="" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-logo mb-30">
                                        <img src="/img/kaiadmin/cisco_brand2.png" alt="" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-logo mb-30">
                                        <img src="/img/kaiadmin/cisco_brand3.png" alt="" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-logo mb-30">
                                        <img src="/img/kaiadmin/cisco_brand4.png" alt="" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-logo mb-30">
                                        <img src="/img/kaiadmin/cisco_brand5.png" alt="" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-logo mb-30">
                                        <img src="/img/kaiadmin/cisco_brand6.png" alt="" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <div class="footer-area footer-padding">
            <div class="container">
                <div class="row d-flex justify-content-between">
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                        <div class="single-footer-caption mb-50">
                            <div class="single-footer-caption mb-30">
                                <div class="footer-tittle">
                                    <h4>About Us</h4>
                                    <div class="footer-pera">
                                        <p>Solusi cerdas untuk pemasaran WhatsApp otomatis, efisien, dan tepat sasaran.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-5">
                        <div class="single-footer-caption mb-50">
                            <div class="footer-tittle">
                                <h4>Contact Info</h4>
                                <ul>
                                    <li>
                                        <p>Address : Dusun segunting, Tambak Beras, Kec. Cerme, Kab. Gresik, Jawa Timur
                                            61171.</p>
                                    </li>
                                    <li><a href="#">Phone : +62 822-4534-2997</a></li>
                                    <li><a href="#">Email : info@pemudadigital.com</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-5">
                        <div class="single-footer-caption mb-50">
                            <div class="footer-tittle">
                                <h4>Important Link</h4>
                                <ul>
                                    <li><a href="#">View Event</a></li>
                                    <li><a href="#">Contact Us</a></li>
                                    <li><a href="#">Testimonial</a></li>
                                    <li><a href="#">Support</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-5">
                        <div class="single-footer-caption mb-50">
                            <div class="footer-tittle">
                                <h4>Newsletter</h4>
                                <div class="footer-pera footer-pera2">
                                    <p>Dapatkan insight eksklusif dan strategi pemasaran cerdas langsung ke inbox.</p>
                                </div>
                                <div class="footer-form">
                                    <div id="mc_embed_signup">
                                        <form target="_blank"
                                            action="https://spondonit.us12.list-manage.com/subscribe/post?u=1462626880ade1ac87bd9c93a&amp;id=92a4423d01"
                                            method="get" class="subscribe_form relative mail_part">
                                            <input type="email" name="email" id="newsletter-form-email"
                                                placeholder="Email Address" class="placeholder hide-on-focus"
                                                onfocus="this.placeholder = ''"
                                                onblur="this.placeholder = ' Email Address '" />
                                            <div class="form-icon">
                                                <button type="submit" name="submit" id="newsletter-submit"
                                                    class="email_icon newsletter-submit button-contactForm"><img
                                                        src="{{ asset('img/kaiadmin/form.png') }}"
                                                        alt="" /></button>
                                            </div>
                                            <div class="mt-10 info"></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom-area footer-bg">
            <div class="container">
                <div class="footer-border">
                    <div class="row d-flex justify-content-between align-items-center">
                        <div class="col-xl-10 col-lg-8">
                            <div class="footer-copy-right">
                                <p>
                                    Copyright &copy;
                                    <script>
                                        document.write(new Date().getFullYear());
                                    </script>
                                    Cipta Pemuda Digital. All rights reserved</a>
                                </p>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4">
                            <div class="footer-social f-right">
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="https://www.facebook.com/sai4ull"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fas fa-globe"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <div id="back-top">
        <a title="Go to Top" href="#"> <i class="fas fa-level-up-alt"></i></a>
    </div>

    <script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.slicknav.min.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/slick.min.js') }}"></script>
    <script src="{{ asset('js/wow.min.js') }}"></script>
    <script src="{{ asset('js/animated.headline.js') }}"></script>
    <script src="{{ asset('js/jquery.magnific-popup.js') }}"></script>
    <script src="{{ asset('js/gijgo.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('js/jquery.sticky.js') }}"></script>
    <script src="{{ asset('js/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('js/waypoints.min.js') }}"></script>
    <script src="{{ asset('js/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('js/contact.js') }}"></script>
    <script src="{{ asset('js/jquery.form.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('js/mail-script.js') }}"></script>
    <script src="{{ asset('js/jquery.ajaxchimp.min.js') }}"></script>
    <script src="{{ asset('js/plugins.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Ambil kuota awal saat halaman dibuka
            function fetchQuota() {
                $.ajax({
                    url: '{{ route('update.kuota') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#remainingQuota').text(res.remainingQuota + ' peserta lagi');
                            if (res.remainingQuota === 0) {
                                $('#registerButton').attr('disabled', true).text('Kuota Penuh');
                            }
                        }
                    }
                });
            }

            // Saat tombol diklik, kurangi kuota
            $('#registerButton').on('click', function(e) {
                e.preventDefault();
                // Redirect or handle registration click
                window.location.href = $(this).attr('href');
            });

            // Load kuota di awal
            fetchQuota();
        });
    </script>

    <script>
        // Mengirimkan tanggal dari Laravel ke JavaScript
        // Variabel eventDatetime dikirim dari controller
        var eventDatetime = "{{ $eventDatetime }}";

        // Inisialisasi Countdown
        if (eventDatetime) {
            $('#countdown').countdown(eventDatetime, function(event) {
                $(this).html(event.strftime(
                    '<div class="cd-item"><span>%D</span> <p>Days</p> </div>' +
                    '<div class="cd-item"><span>%H</span> <p>Hrs</p> </div>' +
                    '<div class="cd-item"><span>%M</span> <p>Min</p> </div>' +
                    '<div class="cd-item"><span>%S</span> <p>Sec</p> </div>'
                ));
            });
        }
    </script>
</body>

</html>
