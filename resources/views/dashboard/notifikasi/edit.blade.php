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
                            <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Event</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Edit Event</a></li>
                        </ul>
                    </div>

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
                                    <div class="form-group">
                                        <label for="event_type">Pilih Jenis Event</label>
                                        <input type="text" class="form-control" id="event_type" name="event_type"
                                            value="{{ $notification->event_type }}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="event">Nama Event</label>
                                        <input type="text" class="form-control" id="event" name="event"
                                            value="{{ $notification->event }}" required>
                                    </div>

                                    {{-- BARU: Field Lokasi Kota (event_city) --}}
                                    <div class="form-group" id="eventCityWrapper">
                                        <label for="eventCityField">Lokasi Kota Event</label>
                                        <input type="text" class="form-control" id="eventCityField" name="event_city"
                                            placeholder="Nama Kota"
                                            value="{{ old('event_city', $notification->event_city) }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="banner">Banner (Opsional, jika ingin ganti)</label>
                                        <span style="color: red"> * Size Banner: 1920x1080</span>
                                        <input type="file" class="form-control" id="banner" name="banner"
                                            accept="image/*">
                                        @if ($notification->banner)
                                            <small>Banner saat ini:</small><br>
                                            <img src="{{ asset('storage/' . $notification->banner) }}" alt="Banner"
                                                style="max-width: 200px; margin-top: 10px;">
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="event_date">Tanggal Acara</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date"
                                            value="{{ old('event_date', $notification->event_date) }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="event_time">Waktu Acara</label>
                                        <input type="time" class="form-control" id="event_time" name="event_time"
                                            value="{{ old('event_time', \Carbon\Carbon::parse($notification->event_time)->format('H:i')) }}"
                                            required>
                                    </div>

                                    {{-- ... (field-field lain) ... --}}
                                    <div class="form-group" id="zoomField">
                                        <label for="zoom">Link Zoom</label>
                                        <input type="text" class="form-control" id="zoom" name="zoom"
                                            value="{{ old('zoom', $notification->zoom) }}">
                                    </div>
                                    <div class="form-group" id="locationNameField">
                                        <label for="location_name">Lokasi</label>
                                        <input type="text" class="form-control" id="location_name"
                                            placeholder="Hotel Horison GKB - Gresik" name="location_name"
                                            value="{{ old('location_name', $notification->location_name) }}">
                                    </div>
                                    <div class="form-group" id="locationAddressField">
                                        <label for="location_address">Alamat Lokasi</label>
                                        <input type="text" class="form-control" id="location_address"
                                            name="location_address" placeholder="Contoh: Jl. Kalimantan No.12A, Gresik"
                                            value="{{ old('location_address', $notification->location_address) }}">
                                    </div>
                                    <div class="form-group" id="locationField">
                                        <label for="location">Link Lokasi Acara</label>
                                        <input type="text" class="form-control" id="location"
                                            placeholder="https://maps.app.goo.gl/damZp7pG8ugLYfKg8" name="location"
                                            value="{{ old('location', $notification->location) }}">
                                    </div>
                                    <div class="form-group" id="isPaidWrapper" style="display: none;">
                                        <label>
                                            <input type="checkbox" id="is_paid" name="is_paid" value="1"
                                                {{ old('is_paid', $notification->is_paid) ? 'checked' : '' }}
                                                onchange="togglePriceField()">
                                            Event Berbayar?
                                        </label>
                                    </div>
                                    <div class="form-group" id="priceField" style="display: none;">
                                        <label for="price">Harga (Rp)</label>
                                        <input type="number" class="form-control" id="price" name="price"
                                            placeholder="Contoh: 50000" value="{{ old('price', $notification->price) }}"
                                            min="0" />
                                    </div>
                                    <div class="form-group">
                                        <label for="referral_discount_amount">Diskon per Referral Peserta (Rp)</label>
                                        <input type="number" class="form-control" name="referral_discount_amount"
                                            placeholder="Contoh: 10000 (Kosongkan jika tidak ada)"
                                            value="{{ old('referral_discount_amount', $notification->referral_discount_amount) }}"
                                            min="0" />
                                        <small class="form-text text-muted">Jumlah diskon/cashback untuk peserta yang
                                            berhasil mengajak temannya.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="participant_referral_commission">Komisi Affiliate dari Referral Peserta
                                            (Rp)</label>
                                        <input type="number" class="form-control" name="participant_referral_commission"
                                            placeholder="Contoh: 5000 (Kosongkan jika tidak ada)"
                                            value="{{ old('participant_referral_commission', $notification->participant_referral_commission) }}"
                                            min="0" />
                                        <small class="form-text text-muted">Komisi tambahan untuk affiliate jika pesertanya
                                            berhasil mengajak teman.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger me-2"
                                        onclick="window.location='{{ route('notifikasi.index') }}'">Batal</button>
                                    <button type="submit" class="btn btn-success">Simpan</button>
                                </div>
                            </form>

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

                                // Fungsi untuk menampilkan/menyembunyikan field berdasarkan jenis event
                                function toggleFieldsOnLoad() {
                                    const eventType = document.getElementById("event_type").value;
                                    const zoomField = document.getElementById("zoomField");
                                    const locationFields = [
                                        document.getElementById("eventCityWrapper"),
                                        document.getElementById("locationField"),
                                        document.getElementById("locationNameField"),
                                        document.getElementById("locationAddressField")
                                    ];
                                    const isPaidWrapper = document.getElementById("isPaidWrapper");

                                    if (eventType === "webinar") {
                                        zoomField.style.display = "block";
                                        locationFields.forEach(field => field.style.display = "none");
                                        isPaidWrapper.style.display = "block";
                                    } else if (eventType === "workshop") {
                                        zoomField.style.display = "none";
                                        locationFields.forEach(field => field.style.display = "block");
                                        isPaidWrapper.style.display = "block";
                                    }
                                }

                                document.addEventListener("DOMContentLoaded", function() {
                                    toggleFieldsOnLoad(); // Panggil fungsi saat halaman dimuat
                                    togglePriceField();
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
