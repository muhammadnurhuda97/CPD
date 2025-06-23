@extends('layouts.admin')

@section('title', 'Edit Notifikasi Pendaftaran')

@section('content')

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">

            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Edit Event Pendaftaran</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Event</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Edit Event</a></li>
                        </ul>
                    </div>

                    <!-- ALERT POPUP SWEETALERT2 -->
                    @if (session('success'))
                        <script>
                            Swal.fire({
                                title: "Berhasil!",
                                text: "{{ session('success') }}",
                                icon: "success",
                                confirmButtonText: "OK"
                            });
                        </script>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('notifikasi.update', $notification->id) }}" method="POST"
                                enctype="multipart/form-data">

                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <!-- Pilihan Jenis Event (Tidak Dapat Diubah) -->
                                    <div class="form-group">
                                        <label for="event_type">Pilih Jenis Event</label>
                                        <input type="text" class="form-control" id="event_type" name="event_type"
                                            value="{{ $notification->event_type }}" readonly>
                                    </div>

                                    <!-- Nama Event (Tidak Dapat Diubah) -->
                                    <div class="form-group">
                                        <label for="event">Nama Event</label>
                                        <input type="text" class="form-control" id="event" name="event"
                                            value="{{ $notification->event }}" readonly>
                                    </div>

                                    <!-- Banner Event -->
                                    <div class="form-group">
                                        <label for="banner">Banner (Opsional, jika ingin ganti)</label> <span
                                            style="color: red"> * Size Banner: 1920x1080</span>
                                        <input type="file" class="form-control" id="banner" name="banner"
                                            accept="image/*">
                                        @if ($notification->banner)
                                            <small>Banner saat ini:</small><br>
                                            <img src="{{ asset('storage/' . $notification->banner) }}" alt="Banner"
                                                style="max-width: 200px; margin-top: 10px;">
                                        @endif
                                    </div>


                                    <!-- Tanggal Acara -->
                                    <div class="form-group">
                                        <label for="event_date">Tanggal Acara</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date"
                                            value="{{ old('event_date', $notification->event_date) }}" required>
                                    </div>

                                    <!-- Waktu Acara -->
                                    <div class="form-group">
                                        <label for="event_time">Waktu Acara</label>
                                        <input type="time" class="form-control" id="event_time" name="event_time"
                                            value="{{ old('event_time', \Carbon\Carbon::parse($notification->event_time)->format('H:i')) }}"
                                            required>
                                    </div>

                                    <!-- Link Zoom (Hanya untuk Webinar) -->
                                    <div class="form-group" id="zoomField">
                                        <label for="zoom">Link Zoom</label>
                                        <input type="text" class="form-control" id="zoom" name="zoom"
                                            value="{{ old('zoom', $notification->zoom) }}">
                                    </div>

                                    <!-- Lokasi (Hanya untuk Workshop) -->
                                    <div class="form-group" id="locationNameField">
                                        <label for="location_name">Lokasi</label>
                                        <input type="text" class="form-control" id="location_name"
                                            placeholder="Hotel Horison GKB - Gresik" name="location_name"
                                            value="{{ old('location_name', $notification->location_name) }}">
                                    </div>
                                    <!-- Alamat Lengkap Lokasi (Hanya untuk Workshop) -->
                                    <div class="form-group" id="locationAddressField">
                                        <label for="location_address">Alamat Lokasi</label>
                                        <input type="text" class="form-control" id="location_address"
                                            name="location_address" placeholder="Contoh: Jl. Kalimantan No.12A, Gresik"
                                            value="{{ old('location_address', $notification->location_address) }}">
                                    </div>
                                    <!-- Link Lokasi (Hanya untuk Workshop) -->
                                    <div class="form-group" id="locationField">
                                        <label for="location">Link Lokasi Acara</label>
                                        <input type="text" class="form-control" id="location"
                                            placeholder="https://maps.app.goo.gl/damZp7pG8ugLYfKg8" name="location"
                                            value="{{ old('location', $notification->location) }}">
                                    </div>

                                    <!-- Checkbox Event Berbayar (Hanya untuk Workshop) -->
                                    <div class="form-group" id="isPaidWrapper" style="display: none;">
                                        <label>
                                            <input type="checkbox" id="is_paid" name="is_paid" value="1"
                                                {{ old('is_paid', $notification->is_paid) ? 'checked' : '' }}
                                                onchange="togglePriceField()">
                                            Event Berbayar?
                                        </label>
                                    </div>

                                    <!-- Input Harga -->
                                    <div class="form-group" id="priceField" style="display: none;">
                                        <label for="price">Harga (Rp)</label>
                                        <input type="number" class="form-control" id="price" name="price"
                                            placeholder="Contoh: 50000" value="{{ old('price', $notification->price) }}"
                                            min="0" />
                                    </div>

                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger me-2"
                                        onclick="window.location='{{ route('notifikasi.index') }}'">Batal</button>
                                    <button type="submit" class="btn btn-success">Simpan</button>
                                </div>
                            </form>

                            <!-- JavaScript untuk Menampilkan/Menyembunyikan Input -->
                            <script>
                                function togglePriceField() {
                                    const isPaidCheckbox = document.getElementById("is_paid");
                                    const priceField = document.getElementById("priceField");

                                    if (isPaidCheckbox.checked) {
                                        priceField.style.display = "block";
                                        document.getElementById("price").setAttribute("required", "true");
                                    } else {
                                        priceField.style.display = "none";
                                        document.getElementById("price").removeAttribute("required");
                                    }
                                }

                                function toggleFields() {
                                    var eventType = document.getElementById("event_type").value;
                                    var zoomField = document.getElementById("zoomField");
                                    var locationField = document.getElementById("locationField");
                                    var locationAddressField = document.getElementById("locationAddressField");
                                    var locationNameField = document.getElementById("locationNameField");
                                    var isPaidWrapper = document.getElementById("isPaidWrapper");
                                    var priceField = document.getElementById("priceField");

                                    if (eventType === "webinar") {
                                        zoomField.style.display = "block";
                                        locationField.style.display = "none";
                                        locationNameField.style.display = "none";
                                        locationAddressField.style.display = "none";
                                        isPaidWrapper.style.display = "block"; // ✅ Tampilkan checkbox untuk webinar juga

                                        document.getElementById("zoom").setAttribute("required", "true");
                                        document.getElementById("location").removeAttribute("required");
                                        document.getElementById("location_name").removeAttribute("required");
                                        document.getElementById("location_address").removeAttribute("required");

                                        togglePriceField(); // ✅ Jalankan toggle harga
                                    } else if (eventType === "workshop") {
                                        zoomField.style.display = "none";
                                        locationField.style.display = "block";
                                        locationNameField.style.display = "block";
                                        locationAddressField.style.display = "block";
                                        isPaidWrapper.style.display = "block";

                                        document.getElementById("location").setAttribute("required", "true");
                                        document.getElementById("location_name").setAttribute("required", "true");
                                        document.getElementById("location_address").setAttribute("required", "true");
                                        document.getElementById("zoom").removeAttribute("required");

                                        togglePriceField(); // ✅ Jalankan toggle harga
                                    }
                                }


                                document.addEventListener("DOMContentLoaded", function() {
                                    toggleFields();
                                });
                            </script>

                        </div>
                    </div>

                </div>
            </div>

            @include('dashboard.partials.footer')

        </div>
    </div>

    @include('dashboard.partials.scripts')
@endsection
