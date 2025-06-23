@section('title', 'My Profile')
<style>
    .profile-picture {
        object-fit: cover;
        border: 3px solid #fff;
    }

    #changePhotoBtn {
        background-color: #007bff;
        /* Solid warna biru Bootstrap */
        border: none;
    }

    #changePhotoBtn:hover {
        background-color: #0056b3;
        /* Warna saat hover */
    }
</style>
@section('content')

    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">My Profile</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Profile</a></li>
                        </ul>
                    </div>
                    <div class="page-category">
                        <div class="page-inner">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">My Profile</div>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('profile.update') }}" method="POST"
                                                enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')

                                                <div class="row">
                                                    <div class="col-md-12 d-flex justify-content-center mb-4">
                                                        <div class="position-relative">
                                                            <img src="{{ $user->photo ? Storage::url($user->photo) : asset('img/profile.jpg') }}"
                                                                alt="Profile Picture" class="rounded-circle profile-picture"
                                                                width="150" height="150" id="profilePreview" />

                                                            <input type="file" name="photo" id="photoInput"
                                                                accept="image/*" class="d-none" />

                                                            <div class="dropdown position-absolute"
                                                                style="bottom: 0; right: 0;">
                                                                <button
                                                                    class="btn btn-primary rounded-circle shadow d-flex align-items-center justify-content-center"
                                                                    type="button" id="photoDropdown"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                                    style="width: 40px; height: 40px; padding: 0;">
                                                                    <i class="fas fa-pencil-alt text-white m-0"
                                                                        style="font-size: 14px;"></i>
                                                                </button>
                                                                <ul class="dropdown-menu" aria-labelledby="photoDropdown">
                                                                    <li><a class="dropdown-item" href="#"
                                                                            id="btnChangePhoto">Ganti Foto</a></li>
                                                                    <li><a class="dropdown-item text-danger" href="#"
                                                                            id="btnRemovePhoto">Hapus
                                                                            Foto</a></li>
                                                                </ul>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    <input type="hidden" name="remove_photo" id="removePhotoFlag"
                                                        value="0" />


                                                    <script>
                                                        // Klik tombol "Ganti Foto"
                                                        document.getElementById("btnChangePhoto").addEventListener("click", function(e) {
                                                            e.preventDefault();
                                                            document.getElementById("photoInput").click(); // buka file explorer
                                                        });

                                                        // Preview foto saat dipilih
                                                        document.getElementById("photoInput").addEventListener("change", function(event) {
                                                            const file = event.target.files[0];
                                                            if (file) {
                                                                const reader = new FileReader();
                                                                reader.onload = function(e) {
                                                                    document.getElementById("profilePreview").src = e.target.result;
                                                                    document.getElementById("removePhotoFlag").value = "0"; // reset hapus
                                                                };
                                                                reader.readAsDataURL(file);
                                                            }
                                                        });

                                                        // Klik tombol "Hapus Foto"
                                                        document.getElementById("btnRemovePhoto").addEventListener("click", function(e) {
                                                            e.preventDefault();

                                                            // Ganti ke default
                                                            document.getElementById("profilePreview").src = "{{ asset('img/profile.jpg') }}";
                                                            document.getElementById("photoInput").value = ''; // clear file input
                                                            document.getElementById("removePhotoFlag").value = "1"; // flag hapus
                                                        });
                                                    </script>


                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="form-group">
                                                            <label for="name">Nama Lengkap</label>
                                                            <input type="text" class="form-control" name="name"
                                                                value="{{ old('name', $user->name) }}" required />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="date_of_birth">Tanggal Lahir</label>
                                                            <input type="date" class="form-control" name="date_of_birth"
                                                                value="{{ old('date_of_birth', $user->date_of_birth) }}"
                                                                required />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="gender">Jenis Kelamin</label>
                                                            <select class="form-control" name="gender" required>
                                                                <option value="male"
                                                                    {{ $user->gender == 'male' ? 'selected' : '' }}>
                                                                    Laki-laki</option>
                                                                <option value="female"
                                                                    {{ $user->gender == 'female' ? 'selected' : '' }}>
                                                                    Perempuan</option>
                                                                <option value="other"
                                                                    {{ $user->gender == 'other' ? 'selected' : '' }}>
                                                                    Lainnya
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="whatsapp">Nomor Telepon</label>
                                                            <input type="text" class="form-control" name="whatsapp"
                                                                value="{{ old('whatsapp', $user->whatsapp) }}" />
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="form-group">
                                                            <label for="username">Username</label>
                                                            <div class="input-group mb-3">
                                                                <span class="input-group-text"><i
                                                                        class="fa fa-user"></i></span>
                                                                <input type="text" class="form-control" name="username"
                                                                    id="username"
                                                                    value="{{ old('username', $user->username) }}"
                                                                    readonly />
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="email">Alamat Email</label>
                                                            <input type="email" class="form-control" name="email"
                                                                value="{{ old('email', $user->email) }}" required />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="password">Kata Sandi</label>
                                                            <input type="password" class="form-control" name="password"
                                                                placeholder="Kosongkan jika tidak ingin diubah" />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="password_confirmation">Konfirmasi Kata
                                                                Sandi</label>
                                                            <input type="password" class="form-control"
                                                                name="password_confirmation"
                                                                placeholder="Ulangi kata sandi baru" />
                                                        </div>

                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="form-group">
                                                            <label for="address">Alamat</label>
                                                            <input type="text" class="form-control" name="address"
                                                                value="{{ old('address', $user->address) }}" required />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="city">Kota</label>
                                                            <input type="text" class="form-control" name="city"
                                                                value="{{ old('city', $user->city) }}" required />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="zip">Kode Pos</label>
                                                            <input type="text" class="form-control" name="zip"
                                                                value="{{ old('zip', $user->zip) }}" />
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="country">Negara</label>
                                                            <input type="text" class="form-control" name="country"
                                                                value="{{ old('country', $user->country) }}" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card-action">
                                                    <button type="submit" class="btn btn-success"
                                                        onclick="this.disabled=true; this.form.submit();">Simpan
                                                        Perubahan</button>
                                                    <button type="button" class="btn btn-danger"
                                                        onclick="window.history.back()">Batal</button>
                                                </div>

                                                @if ($errors->any())
                                                    <script>
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Validasi Gagal',
                                                            html: `{!! implode('<br>', $errors->all()) !!}`,
                                                        });
                                                    </script>
                                                @endif
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            @extends('layouts.admin')
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

    {{-- SweetAlert untuk Notifikasi Kelengkapan Profil --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cek apakah ada flash message 'error' yang datang dari ProfileController
            const errorMessage = "{{ session('error') }}";
            const currentUrl = window.location.href;

            // Logika untuk ketika datang dari alur pembayaran
            if (errorMessage && currentUrl.includes('profile/edit')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Mohon Lengkapi Profil Anda',
                    html: errorMessage, // Pesan error dari ProfileController
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });
            }
            // Logika untuk ketika tidak ada intended URL (langsung akses halaman profil edit)
            else if (currentUrl.includes('profile/edit') && !errorMessage) {
                // Cek apakah data profil pengguna belum lengkap
                const user = {!! json_encode($user) !!};
                const requiredFields = ['address', 'city', 'zip', 'whatsapp', 'date_of_birth'];
                let incompleteFields = [];

                requiredFields.forEach(field => {
                    if (!user[field]) {
                        incompleteFields.push(field.replace('_', ' ').replace(/\b\w/g, l => l
                        .toUpperCase())); // Format nama field
                    }
                });

                if (incompleteFields.length > 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Data Profil Belum Lengkap',
                        html: 'Harap lengkapi data berikut untuk pengalaman yang lebih baik:<br>' +
                            incompleteFields.join('<br>'),
                        confirmButtonText: 'Oke',
                    });
                }
            }
        });
    </script>
@endsection
