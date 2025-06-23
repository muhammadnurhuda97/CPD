@php
    use Carbon\Carbon;
    \Carbon\Carbon::setLocale('id');
@endphp

@extends('layouts.admin')

@section('title', 'Notifikasi Pendaftaran')

@section('content')
    <style>
        .card-img-top {
            height: 350px;
            /* atau sesuai kebutuhan kamu */
            object-fit: cover;
            width: 100%;
        }
    </style>

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Dashboard</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Event</a></li>
                        </ul>
                    </div>
                    <div class="page-category">
                        <div class="page-inner">
                            @if (Auth::check() && Auth::user()->role === 'admin')
                                <div class="mb-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#notificationModal">Tambah Event</button>
                                </div>
                            @endif
                            {{--  <!-- Modal untuk tambah notifikasi -->  --}}
                            <div class="modal fade" id="notificationModal" tabindex="-1"
                                aria-labelledby="notificationModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="notificationModalLabel">Tambah Event</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>

                                        <form action="{{ route('notifikasi.store') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-body">
                                                <!-- Pilihan Jenis Event -->
                                                <div class="form-group">
                                                    <label for="event_type">Pilih Jenis Event</label>
                                                    <select class="form-control" id="event_type" name="event_type" required
                                                        onchange="toggleFields(); autoFillEventName();">
                                                        <option value="" disabled selected>Pilih Jenis Event</option>
                                                        <option value="webinar">Webinar</option>
                                                        <option value="workshop">Workshop</option>
                                                    </select>
                                                </div>

                                                <!--  Upload Banner Event  -->
                                                <div class="form-group mt-2">
                                                    <label for="banner">Upload Banner Event (PNG/JPG)</label> <span
                                                        style="color: red"> * Size Banner: 1920x1080</span>
                                                    <input type="file" class="form-control" id="banner" name="banner"
                                                        accept="image/*" required>
                                                </div>

                                                <!-- Nama Event -->
                                                <div class="form-group">
                                                    <label for="event">Nama Event</label>
                                                    <input type="text" class="form-control" id="event" name="event"
                                                        placeholder="Judul Event" required />
                                                </div>

                                                <!-- Tanggal Acara -->
                                                <div class="form-group">
                                                    <label for="event_date">Tanggal Acara</label>
                                                    <input type="date" class="form-control" id="event_date"
                                                        name="event_date" required />
                                                </div>

                                                <!-- Waktu Acara -->
                                                <div class="form-group">
                                                    <label for="event_time">Waktu Acara</label>
                                                    <input type="time" class="form-control" id="event_time"
                                                        name="event_time" required />
                                                </div>

                                                <!-- Link Zoom (Hanya untuk Webinar) -->
                                                <div class="form-group" id="zoomField" style="display: none;">
                                                    <label for="zoom">Link Zoom</label>
                                                    <input type="text" class="form-control" id="zoom" name="zoom"
                                                        placeholder="Masukkan link Zoom" />
                                                </div>

                                                <!-- Checkbox Event Berbayar -->
                                                <!-- Event Berbayar -->
                                                <div class="form-group" id="is_paid_field" style="display: none;">
                                                    <label for="is_paid">
                                                        <input type="checkbox" id="is_paid" name="is_paid" value="1"
                                                            onchange="togglePriceField()"> Event Berbayar?
                                                    </label>
                                                </div>

                                                <!-- Input Harga -->
                                                <div class="form-group" id="priceField" style="display: none;">
                                                    <label for="price">Harga (Rp)</label>
                                                    <input type="number" class="form-control" id="price" name="price"
                                                        placeholder="Contoh: 50000" min="0" />
                                                </div>

                                                <!-- Nama Lokasi (Hanya untuk Workshop) -->
                                                <div class="form-group" id="locationNameField" style="display: none;">
                                                    <label for="location_name">Nama Tempat</label>
                                                    <input type="text" class="form-control" id="location_name"
                                                        name="location_name" value="{{ old('location_name') }}"
                                                        placeholder="Contoh: Hotel Horison GKB" />
                                                </div>

                                                <!-- Alamat (Hanya untuk Workshop) -->
                                                <div class="form-group" id="locationAddressField" style="display: none;">
                                                    <label for="location_address">Alamat</label>
                                                    <input type="text" class="form-control" id="location_address"
                                                        name="location_address" value="{{ old('location_address') }}"
                                                        placeholder="Jl. Kalimantan No.12A, Gresik, Jawa Timur" />
                                                </div>

                                                <!-- Link Google Maps (Hanya untuk Workshop) -->
                                                <div class="form-group" id="locationField" style="display: none;">
                                                    <label for="location">Link Google Maps</label>
                                                    <input type="text" class="form-control" id="location"
                                                        name="location"
                                                        placeholder="https://maps.app.goo.gl/damZp7pG8ugLYfKg8" />
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>

                                        <!-- Script untuk Menampilkan Input dan Mengisi Nama Event secara otomatis -->
                                        <script>
                                            function toggleFields() {
                                                const eventType = document.getElementById("event_type").value;
                                                const zoomField = document.getElementById("zoomField");
                                                const locationField = document.getElementById("locationField");
                                                const locationAddressField = document.getElementById("locationAddressField");
                                                const locationNameField = document.getElementById("locationNameField");
                                                const isPaidField = document.getElementById("is_paid_field");

                                                // Reset tampilan
                                                zoomField.style.display = "none";
                                                locationField.style.display = "none";
                                                locationNameField.style.display = "none";
                                                locationAddressField.style.display = "none";
                                                isPaidField.style.display = "none";

                                                // Reset required
                                                document.getElementById("zoom").required = false;
                                                document.getElementById("location").required = false;
                                                document.getElementById("location_name").required = false;
                                                document.getElementById("location_address").required = false;

                                                if (eventType === "webinar") {
                                                    zoomField.style.display = "block";
                                                    isPaidField.style.display = "block"; // Tampilkan juga checkbox event berbayar untuk webinar
                                                    document.getElementById("zoom").required = true;
                                                } else if (eventType === "workshop") {
                                                    locationField.style.display = "block";
                                                    locationNameField.style.display = "block";
                                                    locationAddressField.style.display = "block";
                                                    isPaidField.style.display = "block";
                                                    document.getElementById("location").required = true;
                                                    document.getElementById("location_name").required = true;
                                                    document.getElementById("location_address").required = true;
                                                }
                                            }


                                            function autoFillEventName() {
                                                const eventType = document.getElementById("event_type").value;
                                                const eventInput = document.getElementById("event");

                                                if (eventType === "webinar") {
                                                    eventInput.value = "Webinar Autosmart Marketing";
                                                } else if (eventType === "workshop") {
                                                    eventInput.value = "Workshop Autosmart Marketing";
                                                } else {
                                                    eventInput.value = "";
                                                }
                                            }

                                            function togglePriceField() {
                                                const isPaid = document.getElementById("is_paid").checked;
                                                const priceField = document.getElementById("priceField");
                                                priceField.style.display = isPaid ? "block" : "none";
                                            }

                                            document.addEventListener("DOMContentLoaded", function() {
                                                toggleFields();
                                                togglePriceField();
                                            });
                                        </script>

                                    </div>
                                </div>
                            </div>

                            {{--  <!-- Daftar Notifikasi -->  --}}
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">Daftar Event</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
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
                                                                <td>{{ $notification->event }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($notification->event_date)->translatedFormat('l') }}
                                                                </td>
                                                                <td>{{ \Carbon\Carbon::parse($notification->event_date)->translatedFormat('d F Y') }}
                                                                </td>
                                                                <td>{{ date('H.i', strtotime($notification->event_time)) }}
                                                                    WIB</td>
                                                                <td>
                                                                    @if (Auth::check() && Auth::user()->role === 'admin')
                                                                        <!-- Button Edit -->
                                                                        <a href="{{ route('notifikasi.edit', $notification->id) }}"
                                                                            class="btn btn-warning btn-sm">Edit</a>

                                                                        <!-- Form untuk Hapus -->
                                                                        <form id="deleteForm_{{ $notification->id }}"
                                                                            action="{{ route('notifikasi.destroy', $notification->id) }}"
                                                                            method="POST" style="display:inline;">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="button"
                                                                                class="btn btn-danger btn-sm"
                                                                                onclick="confirmDelete({{ $notification->id }});">
                                                                                Hapus
                                                                            </button>
                                                                        </form>
                                                                    @else
                                                                        <!-- Copy Link Button with Data Attribute -->
                                                                        @if ($notification->event_type === 'webinar')
                                                                            <button type="button"
                                                                                class="btn btn-primary btn-sm copy-link"
                                                                                data-link="{{ url('webinar-autosmart-marketing?affiliate_id=' . Auth::user()->username) }}">Copy
                                                                                Link</button>
                                                                        @elseif($notification->event_type === 'workshop')
                                                                            <button type="button"
                                                                                class="btn btn-primary btn-sm copy-link"
                                                                                data-link="{{ url('workshop-autosmart-marketing?affiliate_id=' . Auth::user()->username) }}">Copy
                                                                                Link</button>
                                                                        @endif
                                                                    @endif

                                                                    <script>
                                                                        // Event Listener for Copy Link buttons
                                                                        document.querySelectorAll('.copy-link').forEach(function(button) {
                                                                            button.addEventListener('click', function() {
                                                                                var link = this.getAttribute('data-link'); // Get the link from data attribute
                                                                                copyToClipboard(link);
                                                                            });
                                                                        });

                                                                        // Function to Copy Link to Clipboard
                                                                        function copyToClipboard(link) {
                                                                            navigator.clipboard.writeText(link).then(function() {
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
                                                                            }).catch(function(error) {
                                                                                swal({
                                                                                    title: "Error!",
                                                                                    text: "Gagal menyalin link.",
                                                                                    icon: "error",
                                                                                    buttons: {
                                                                                        confirm: {
                                                                                            text: "Oke",
                                                                                            value: true,
                                                                                            visible: true,
                                                                                            className: "btn btn-danger",
                                                                                            closeModal: true,
                                                                                        },
                                                                                    },
                                                                                });
                                                                            });
                                                                        }

                                                                        // Confirm Delete Function
                                                                        function confirmDelete(notificationId) {
                                                                            Swal.fire({
                                                                                title: 'Apakah Anda yakin?',
                                                                                text: 'Anda tidak dapat mengembalikan data ini setelah dihapus!',
                                                                                icon: 'warning',
                                                                                showCancelButton: true,
                                                                                confirmButtonText: 'Ya, hapus!',
                                                                                cancelButtonText: 'Batal',
                                                                                confirmButtonClass: 'btn btn-success',
                                                                                cancelButtonClass: 'btn btn-danger',
                                                                                reverseButtons: true
                                                                            }).then((result) => {
                                                                                if (result.isConfirmed) {
                                                                                    // If confirmed, submit the delete form
                                                                                    document.getElementById('deleteForm_' + notificationId).submit();
                                                                                }
                                                                            });
                                                                        }
                                                                    </script>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center">Tidak ada Event
                                                                    yang tersedia.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>

                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="mt-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="mb-0">Landing Page Management</h3>
                                @if (Auth::check() && Auth::user()->role === 'admin')
                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#flyerModal">Tambah Flyer</button>
                                @endif
                            </div>
                            {{--  <!-- Modal Tambah Flyer -->  --}}
                            <div class="modal fade" id="flyerModal" tabindex="-1" aria-labelledby="flyerModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <form id="uploadFlyerForm" action="/upload-flyer" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="flyerModalLabel">Unggah Flyer Baru</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Tutup"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="image" class="form-label">Pilih Gambar Flyer</label>
                                                    <input type="file" class="form-control" name="image"
                                                        id="image" accept="image/*" required>
                                                </div>
                                                <div class="alert alert-danger d-none" id="uploadError"></div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Unggah</button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Batal</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <hr class="mb-5">

                            {{--  <!-- Daftar Flyer -->  --}}
                            <div class="row">
                                @php
                                    $flyerFiles = File::files(storage_path('app/public/images/flyer'));
                                    $flyers = collect($flyerFiles)->filter(function ($file) {
                                        return preg_match('/^flyer.*\.(jpg|jpeg|png)$/', $file->getFilename());
                                    });
                                @endphp
                                @foreach ($flyers as $file)
                                    @php $filename = $file->getFilename(); @endphp
                                    <div class="col-md-4 mb-4">
                                        <div class="card">
                                            <img src="{{ asset('storage/images/flyer/' . $filename) }}"
                                                class="card-img-top" alt="Flyer Image">
                                            <div class="card-body text-center">
                                                @if (Auth::check() && Auth::user()->role === 'admin')
                                                    <button type="button" class="btn btn-primary view-btn"
                                                        data-target="{{ $filename }}">
                                                        <i class="fas fa-upload me-1"></i> Replace
                                                    </button>
                                                    <input type="file" accept="image/*" class="d-none img-input">
                                                    <button type="button" class="btn btn-secondary aff-btn"
                                                        data-target="{{ preg_replace('/\.(jpg|jpeg|png)$/', '.txt', $filename) }}">
                                                        <i class="fas fa-edit me-1"></i> Copywriting
                                                    </button>
                                                @else
                                                    <a href="{{ url('/download-flyer/' . $filename) }}"
                                                        class="btn btn-primary" download>
                                                        <i class="fas fa-arrow-alt-circle-down me-1"></i> Flyer
                                                    </a>
                                                    <button type="button" class="btn btn-secondary copy-btn"
                                                        data-target="{{ preg_replace('/\.(jpg|jpeg|png)$/', '.txt', $filename) }}">
                                                        <i class="fas fa-copy me-1"></i> Copywriting
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Modal untuk Copywriting --}}
                            <div class="modal fade" id="copyModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Copywriting</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                onclick="$('#copyModal').modal('hide');">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <textarea id="copyText" class="form-control" rows="15"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <input type="hidden" id="copyTarget">
                                            @if (Auth::check() && Auth::user()->role === 'admin')
                                                <button type="button" class="btn btn-primary"
                                                    id="saveCopy">Simpan</button>
                                            @else
                                                <button type="button" class="btn btn-success"
                                                    id="copyToClipboard">Salin</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- End Modal --}}
                        </div>
                    </div>
                </div>
            </div>

            @include('dashboard.partials.footer')
        </div>
    </div>
    <script>
        document.querySelectorAll('.view-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const card = this.closest('.card');
                const input = card.querySelector('.img-input');
                const targetFile = this.getAttribute('data-target'); // ambil file tujuan

                input.click();

                input.onchange = function(event) {
                    const file = event.target.files[0];
                    if (file && targetFile) {
                        const formData = new FormData();
                        formData.append('image', file);
                        formData.append('target', targetFile); // kirim ke server

                        fetch('/upload-flyer', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    card.querySelector('.card-img-top').src = data.new_image_url +
                                        '?' + new Date().getTime();
                                    alert('Gambar berhasil diunggah!');
                                } else {
                                    alert('Upload gagal: ' + (data.errors ? data.errors.join(', ') :
                                        ''));
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                alert('Terjadi kesalahan saat upload.');
                            });
                    }
                };
            });
        });

        document.querySelectorAll('.aff-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                document.getElementById('copyTarget').value = target;

                fetch(`/get-copywriting?target=${target}`)
                    .then(res => res.text())
                    .then(text => {
                        document.getElementById('copyText').value = text;
                        new bootstrap.Modal(document.getElementById('copyModal')).show(); // Bootstrap 5
                    })
                    .catch(err => alert('Gagal mengambil teks.'));
            });
        });

        document.getElementById('saveCopy')?.addEventListener('click', function() {
            const text = document.getElementById('copyText').value;
            const target = document.getElementById('copyTarget').value;

            fetch('/save-copywriting', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        text,
                        target
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Copywriting berhasil disimpan!');
                        bootstrap.Modal.getInstance(document.getElementById('copyModal')).hide();
                    } else {
                        alert('Gagal menyimpan.');
                    }
                })
                .catch(() => alert('Error saat menyimpan.'));
        });

        document.querySelectorAll('.copy-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                document.getElementById('copyTarget').value = target;

                fetch(`/get-copywriting?target=${target}`)
                    .then(res => res.text())
                    .then(text => {
                        document.getElementById('copyText').value = text;
                        new bootstrap.Modal(document.getElementById('copyModal'))
                            .show(); // Tampilkan modal
                    })
                    .catch(err => alert('Gagal mengambil teks.'));
            });
        });

        document.getElementById('uploadFlyerForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Mencegah submit default
            const form = this;
            const formData = new FormData(form);

            fetch('/upload-flyer', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Flyer berhasil diunggah!');
                        location.reload(); // reload halaman untuk menampilkan flyer baru
                    } else {
                        const errorBox = document.getElementById('uploadError');
                        errorBox.textContent = data.errors ? data.errors.join(', ') : 'Terjadi kesalahan.';
                        errorBox.classList.remove('d-none');
                    }
                })
                .catch(() => {
                    alert('Upload gagal karena kesalahan jaringan.');
                });
        });

        const copyButton = document.getElementById('copyToClipboard');
        if (copyButton) {
            copyButton.addEventListener('click', function() {
                const text = document.getElementById('copyText').value;

                navigator.clipboard.writeText(text)
                    .then(() => {
                        alert('Copywriting berhasil disalin ke clipboard!');
                        bootstrap.Modal.getInstance(document.getElementById('copyModal')).hide();
                    })
                    .catch(() => {
                        alert('Gagal menyalin teks ke clipboard.');
                    });
            });
        }
    </script>
    @include('dashboard.partials.scripts')
@endsection
