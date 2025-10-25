 <!-- Sidebar -->
 <div class="sidebar" data-background-color="dark">
     <div class="sidebar-logo">
         <div class="logo-header" data-background-color="dark">
             <a href="{{ route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'user.dashboard') }}" class="logo">
                 <img src="{{ asset('img/kaiadmin/logo.png') }}" alt="navbar brand" class="navbar-brand" height="20" />
             </a>
             <div class="nav-toggle">
                 <button class="btn btn-toggle toggle-sidebar">
                     <i class="gg-menu-right"></i>
                 </button>
                 <button class="btn btn-toggle sidenav-toggler">
                     <i class="gg-menu-left"></i>
                 </button>
             </div>
             <button class="topbar-toggler more">
                 <i class="gg-more-vertical-alt"></i>
             </button>
         </div>
     </div>
     <div class="sidebar-wrapper scrollbar scrollbar-inner">
         <div class="sidebar-content">
             <ul class="nav nav-secondary">
                 <li class="nav-item {{ Request::is('dashboard') ? 'active' : '' }}">
                     @if (Auth::check() && Auth::user()->role === 'admin')
                         <a href="{{ route('admin.dashboard') }}">
                             <i class="fas fa-home"></i>
                             <p>Dashboard Admin</p>
                         </a>
                     @elseif(Auth::check() && Auth::user()->role === 'user')
                         <a href="{{ route('user.dashboard') }}">
                             <i class="fas fa-home"></i>
                             <p>Dashboard User</p>
                         </a>
                     @endif
                 </li>

                 <li class="nav-section">
                     <span class="sidebar-mini-icon">
                         <i class="fa fa-ellipsis-h"></i>
                     </span>
                     <h4 class="text-section">Setting</h4>
                 </li>

                 <li class="nav-item {{ Request::routeIs('notifikasi.*') ? 'active' : '' }}">
                     <a data-bs-toggle="collapse" href="#sidebarLayouts">
                         <i class="fas fa-th-list"></i>
                         @if (Auth::check() && Auth::user()->role === 'admin')
                             <p>Pengelolaan Event</p>
                         @elseif(Auth::check() && Auth::user()->role === 'user')
                             <p>Event</p>
                         @endif
                         <span class="caret"></span>
                     </a>
                     <div class="collapse" id="sidebarLayouts">
                         <ul class="nav nav-collapse">
                             <li>
                                 @if (Auth::check() && Auth::user()->role === 'admin')
                                     <a href="{{ route('notifikasi.index') }}">
                                         <span class="sub-item">Kelola Event</span>
                                     </a>
                                 @elseif(Auth::check() && Auth::user()->role === 'user')
                                     <a href="{{ route('notifikasi.index') }}">
                                         <span class="sub-item">Daftar Event</span>
                                     </a>
                                 @endif
                             </li>
                             @if (Auth::check() && Auth::user()->role === 'admin')
                                 <li>
                                     <a href="{{ route('notifikasi.event') }}">
                                         <span class="sub-item">Funnel Event</span>
                                     </a>
                                 </li>
                             @endif
                         </ul>
                     </div>
                 </li>

                 <li class="nav-item {{ Request::is('lead*') ? 'active' : '' }}">
                     <a data-bs-toggle="collapse" href="#forms">
                         <i class="fas fa-pen-square"></i>
                         <p>Data Leads</p>
                         <span class="caret"></span>
                     </a>
                     <div class="collapse" id="forms">
                         <ul class="nav nav-collapse">
                             <li>
                                 <a href="{{ route('members.index') }}">
                                     <span class="sub-item">Lead Webinar</span>
                                 </a>
                             </li>
                             <li>
                                 <a href="{{ route('members.workshop') }}">
                                     <span class="sub-item">Lead Workshop</span>
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li>

                 {{-- BARU DITAMBAHKAN: Catatan Transaksi --}}
                 <li class="nav-item {{ Request::routeIs('transactions.index') ? 'active' : '' }}">
                     <a href="{{ route('transactions.index') }}">
                         <i class="fas fa-receipt"></i>
                         @if (Auth::check() && Auth::user()->role === 'admin')
                             <p>Transaksi</p>
                         @elseif(Auth::check() && Auth::user()->role === 'user')
                             <p>Riwayat Transaksi</p>
                         @endif
                     </a>
                 </li>

                 <li class="nav-item {{ Request::is('produk*') ? 'active' : '' }}">
                     <a data-bs-toggle="collapse" href="#produk">
                         <i class="fas fa-clipboard-list"></i>
                         <p>Produk</p>
                         <span class="caret"></span>
                     </a>
                     <div class="collapse" id="produk">
                         <ul class="nav nav-collapse">
                             @if (Auth::check() && Auth::user()->role === 'admin')
                                 {{-- Hanya admin bisa tambah produk --}}
                                 <li>
                                     <a href="{{ route('products.create') }}"> {{-- Menggunakan route name --}}
                                         <span class="sub-item">Add Produk</span>
                                     </a>
                                 </li>
                             @endif
                             <li>
                                 <a href="{{ route('products.index') }}"> {{-- Menggunakan route name --}}
                                     <span class="sub-item">List Produk</span>
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li>

                 <li class="nav-item {{ Request::is('member*') ? 'active' : '' }}"> {{-- Mengubah 'submenu*' menjadi 'member*' agar active saat di halaman member --}}
                     <a data-bs-toggle="collapse" href="#submenu">
                         <i class="fas fa-bars"></i>
                         @if (Auth::check() && Auth::user()->role === 'admin')
                             <p>Kelola Admin</p>
                         @elseif(Auth::check() && Auth::user()->role === 'user')
                             <p>Kelola Member</p>
                         @endif
                         <span class="caret"></span>
                     </a>
                     <div class="collapse" id="submenu">
                         <ul class="nav nav-collapse">
                             {{-- Item untuk Admin --}}
                             @if (Auth::check() && Auth::user()->role === 'admin')
                                 <li>
                                     <a data-bs-toggle="collapse" href="#subnav1">
                                         <span class="sub-item">List Member</span>
                                         <span class="caret"></span>
                                     </a>
                                     <div class="collapse" id="subnav1">
                                         <ul class="nav nav-collapse subnav">
                                             <li>
                                                 <a href="{{ route('members.member') }}"> {{-- Menggunakan route name --}}
                                                     <span class="sub-item">Semua Member</span>
                                                 </a>
                                             </li>
                                         </ul>
                                     </div>
                                 </li>
                             @endif
                             {{-- Item untuk User --}}
                             @if (Auth::check() && Auth::user()->role === 'user')
                                 <li>
                                     <a href="{{ route('members.member') }}"> {{-- Menggunakan route name --}}
                                         <span class="sub-item">List Member</span>
                                     </a>
                                 </li>
                             @endif
                         </ul>
                     </div>
                 </li>
             </ul>
         </div>
     </div>
 </div>
 {{--  <script>
    $(document).ready(function () {
        // Menambahkan event listener untuk setiap <a> di dalam #produk
        $("#produk a").click(function (e) {
          e.preventDefault(); // Mencegah aksi default dari <a> (misalnya mengarah ke URL)

          // Menampilkan SweetAlert modal
          Swal.fire({
            title: "Coming soon!",
            text: "Fitur ini akan segera hadir.",
            icon: "info",
            showConfirmButton: false,
            timer: 3000 // Modal akan otomatis hilang dalam 3 detik
          });
        });
      });

 </script>  --}}
 <!-- End Sidebar -->
