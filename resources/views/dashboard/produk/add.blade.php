@extends('layouts.admin')

@section('title', 'Add Produk')

@section('content')
    <div class="wrapper">
        @include('dashboard.partials.sidebar')
        <div class="main-panel">
            @include('dashboard.partials.navbar')
            <div class="container">
                <div class="page-inner">
                    <div class="page-category">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center">
                                            <h4 class="card-title">Daftar Produk</h4>
                                            <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal"
                                                data-bs-target="#addRowModal">
                                                <i class="fa fa-plus"></i>
                                                Add Produk
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Modal to Add Product -->
                                        <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog"
                                            aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title">
                                                            <span class="fw-mediumbold">Tambah Produk</span>
                                                        </h5>
                                                        <button type="button" class="close" data-bs-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="{{ route('products.store') }}" method="POST"
                                                            enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="form-group">
                                                                <label for="name">Nama Produk</label>
                                                                <input type="text" id="name" name="name"
                                                                    class="form-control" placeholder="Nama Produk" required>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="category">Kategori</label>
                                                                <select id="category" name="category" class="form-control"
                                                                    required>
                                                                    <option value="" disabled selected>Pilih Kategori
                                                                    </option>
                                                                    <option value="Produk Digital">Produk Digital</option>
                                                                    <option value="Produk Fisik">Produk Fisik</option>
                                                                </select>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="price">Harga</label>
                                                                <input type="text" id="price" name="price"
                                                                    class="form-control" placeholder="Harga Produk"
                                                                    required>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="description">Deskripsi Produk</label>
                                                                <textarea id="description" name="description" class="form-control" placeholder="Deskripsi Produk" rows="4"
                                                                    required></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="image">Upload Foto</label>
                                                                <input type="file" id="image" name="image"
                                                                    class="form-control">
                                                            </div>

                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn btn-danger"
                                                                    data-bs-dismiss="modal">
                                                                    Close
                                                                </button>
                                                                <button type="submit" id="addRowButton"
                                                                    class="btn btn-primary">
                                                                    Add
                                                                </button>
                                                            </div>
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Table to display products -->
                                        <div class="table-responsive">
                                            <table id="add-row" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr style="text-align: center;">
                                                        <th style="width: 10%">Foto</th>
                                                        <th>Nama Produk</th>
                                                        <th>Kategori</th>
                                                        <th>Harga</th>
                                                        <th style="width: 20%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($products as $product)
                                                        <tr>
                                                            <td style="text-align: center;">
                                                                @if ($product->image)
                                                                    <img src="{{ asset('storage/' . $product->image) }}"
                                                                        alt="{{ $product->name }}" width="50"
                                                                        height="50">
                                                                @else
                                                                    <span>No Image</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $product->name }}</td>
                                                            <td>{{ $product->category }}</td>
                                                            <td>{{ $product->formatted_price }}</td>
                                                            <td class="d-flex justify-content-center">
                                                                <div class="col-md-12 d-flex justify-content-center">
                                                                    <div class="col-md-6 d-flex justify-content-center">
                                                                        <!-- Edit Button -->
                                                                        <button type="button"
                                                                            class="btn btn-link btn-primary btn-lg"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editModal{{ $product->id }}"
                                                                            data-original-title="Edit Task">
                                                                            <i class="fa fa-edit"></i>
                                                                        </button>
                                                                    </div>
                                                                    <div class="col-md-6 d-flex justify-content-center">
                                                                        <!-- Delete Button -->
                                                                        <button type="button"
                                                                            class="btn btn-link btn-danger btn-lg"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteModal{{ $product->id }}"
                                                                            data-original-title="Remove">
                                                                            <i class="fa fa-times"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                    @endforeach
                                                </tbody>

                                                <!-- Modal Edit -->
                                                @foreach ($products as $product)
                                                    <div class="modal fade" id="editModal{{ $product->id }}"
                                                        tabindex="-1" aria-labelledby="editModalLabel"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="editModalLabel">Edit
                                                                        Product</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"
                                                                        aria-label="Close"></button>
                                                                </div>
                                                                <form action="{{ route('products.update', $product->id) }}"
                                                                    method="POST" enctype="multipart/form-data">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <div class="modal-body">
                                                                        <div class="form-group">
                                                                            <label for="name">Nama Produk</label>
                                                                            <input type="text" class="form-control"
                                                                                name="name"
                                                                                value="{{ $product->name }}" required>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label for="category">Kategori</label>
                                                                            <select class="form-control" name="category"
                                                                                required>
                                                                                <option value="" disabled
                                                                                    {{ old('category', $product->category ?? '') == '' ? 'selected' : '' }}>
                                                                                    Pilih Kategori</option>
                                                                                <option value="Produk Digital"
                                                                                    {{ old('category', $product->category ?? '') == 'Produk Digital' ? 'selected' : '' }}>
                                                                                    Produk Digital</option>
                                                                                <option value="Produk Fisik"
                                                                                    {{ old('category', $product->category ?? '') == 'Produk Fisik' ? 'selected' : '' }}>
                                                                                    Produk Fisik</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label for="price">Harga</label>
                                                                            <input type="number" class="form-control"
                                                                                name="price"
                                                                                value="{{ $product->price }}" required>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label for="description">Deskripsi
                                                                                Produk</label>
                                                                            <textarea class="form-control" name="description" rows="4" required>{{ $product->description }}</textarea>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label for="image">Gambar Produk</label>
                                                                            <input type="file" class="form-control"
                                                                                name="image">
                                                                        </div>
                                                                    </div>

                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary"
                                                                            data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit"
                                                                            class="btn btn-primary">Save changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach

                                                <!-- Modal Delete -->
                                                @foreach ($products as $product)
                                                    <div class="modal fade" id="deleteModal{{ $product->id }}"
                                                        tabindex="-1" aria-labelledby="deleteModalLabel"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel">Delete
                                                                        Product</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"
                                                                        aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Apa anda yakin menghapus produk ini?</p>
                                                                    <p><strong>{{ $product->name }}</strong></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <form
                                                                        action="{{ route('products.destroy', $product->id) }}"
                                                                        method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button" class="btn btn-secondary"
                                                                            data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit"
                                                                            class="btn btn-danger">Delete</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach

                                            </table>
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
        <script>
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
            $(document).ready(function() {
                // Initialize basic DataTable
                $("#basic-datatables").DataTable({});

                // Initialize multi-filter DataTable with a select dropdown for each column
                $("#multi-filter-select").DataTable({
                    pageLength: 5,
                    initComplete: function() {
                        this.api()
                            .columns()
                            .every(function() {
                                var column = this;
                                var select = $(
                                        '<select class="form-select"><option value=""></option></select>'
                                    )
                                    .appendTo($(column.footer()).empty())
                                    .on("change", function() {
                                        var val = $.fn.dataTable.util.escapeRegex($(this).val());

                                        column
                                            .search(val ? "^" + val + "$" : "", true, false)
                                            .draw();
                                    });

                                column
                                    .data()
                                    .unique()
                                    .sort()
                                    .each(function(d, j) {
                                        select.append(
                                            '<option value="' + d + '">' + d + "</option>"
                                        );
                                    });
                            });
                    },
                });

                // Initialize DataTable for add-row with page length of 5 and disable sorting on the Image and Action columns
                $("#add-row").DataTable({
                    pageLength: 5,
                    columnDefs: [{
                            targets: [0, 4],
                            orderable: false
                        } // Disable sorting for the 1st (Image) and 5th (Action) columns
                    ]
                });

                var action =
                    '<td> <div class="form-button-action"> <button type="button" data-bs-toggle="tooltip" title="" class="btn btn-link btn-primary btn-lg" data-original-title="Edit Task"> <i class="fa fa-edit"></i> </button> <button type="button" data-bs-toggle="tooltip" title="" class="btn btn-link btn-danger" data-original-title="Remove"> <i class="fa fa-times"></i> </button> </div> </td>';

                // Handle the Add Row button click event
                $("#addRowButton").click(function() {
                    // Get the image file input and create a URL for the image
                    var image = $("#addImage")[0].files[0];
                    var imageURL = image ? URL.createObjectURL(image) :
                        ''; // If no image selected, use an empty string

                    // Add new row to the DataTable with an image in the first column
                    $("#add-row")
                        .dataTable()
                        .fnAddData([
                            imageURL ? '<img src="' + imageURL +
                            '" alt="Product Image" width="50" height="50">' : '', // Image column
                            $("#addName").val(),
                            $("#addPosition").val(),
                            $("#addOffice").val(),
                            action,
                        ]);

                    // Close the modal after adding the row
                    $("#addRowModal").modal("hide");

                    // Optionally clear the form fields after adding the row
                    $("#addName").val('');
                    $("#addPosition").val('');
                    $("#addOffice").val('');
                    $("#addImage").val('');
                });
            });
        </script>


        @include('dashboard.partials.scripts')

    @endsection
