<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function create()
    {
        // Ambil semua produk dari database
        $products = Product::all();

        // Kirim data produk ke view
        return view('dashboard.produk.add', compact('products')); // Menggunakan compact untuk mengirim data
    }

    // Method untuk menyimpan produk baru
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
        ]);

        // Simpan produk ke database
        $product = new Product();
        $product->name = $request->name;
        $product->category = $request->category;
        $product->price = $request->price;
        $product->description = $request->description;
        if ($request->hasFile('image')) {
            // Simpan gambar di storage/app/public/images/ (tanpa 'public/')
            $imagePath = $request->file('image')->store('public/images');

            // Ambil nama file tanpa prefix 'public/' dan simpan di database
            $product->image = Str::replaceFirst('public/', '', $imagePath); // Menggunakan Str::replaceFirst
        }

        $product->save();

        // Redirect ke halaman produk
        return redirect()->route('products.create')->with('success', 'Product berhasil ditambahkan!');
    }

    public function showProducts()
    {
        $products = Product::all();

        // Memformat harga produk dalam format rupiah
        foreach ($products as $product) {
            $product->formatted_price = 'Rp ' . number_format($product->price, 0, ',', '.');
        }

        // PASTIKAN BARIS INI BERUBAH KE 'dashboard.produk.index'
        return view('dashboard.produk.index', compact('products')); //
    }
    // Update product
    public function update(Request $request, Product $product)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
        ]);

        // Update data produk
        if ($request->hasFile('image')) {
            // Simpan gambar baru jika ada, dan hapus gambar lama jika perlu
            $imagePath = $request->file('image')->store('public/images');
            // dd($imagePath); // Baris ini bisa dihapus jika tidak debugging
            $product->image = Str::replaceFirst('public/', '', $imagePath);  // Menghapus 'public/' dari path
        }

        // Update data lainnya
        $product->name = $request->name;
        $product->category = $request->category;
        $product->price = $request->price;
        $product->description = $request->description; // Menyimpan deskripsi produk

        // Simpan perubahan
        $product->save();

        // Redirect atau pesan sukses
        return redirect()->route('products.create')->with('success', 'Product updated successfully!');
    }


    // Delete product
    public function destroy(Product $product)
    {
        // Cek apakah produk memiliki gambar
        if ($product->image) {
            // Hapus gambar dari storage
            Storage::delete('public/' . $product->image);
        }

        // Hapus produk dari database
        $product->delete();

        return redirect()->route('products.create')->with('success', 'Product deleted successfully!');
    }

    public function show(Request $request, $slug)
    {
        // Log saat metode diakses
        Log::info("🟨 [ProductController] Halaman detail produk diakses untuk slug: '{$slug}'.");

        $product = Product::where('slug', $slug)->firstOrFail();
        $product->formatted_price = 'Rp ' . number_format($product->price, 0, ',', '.');

        $affiliateId = $request->query('affiliate_id');
        if ($affiliateId) {
            // Simpan affiliate_id ke session
            session(['affiliate_id_from_product_url' => $affiliateId]);
            Log::info("🟨 [ProductController] Affiliate ID '{$affiliateId}' ditemukan dan disimpan ke session 'affiliate_id_from_product_url'.");
        }

        return view('landingpage.produk.detail', compact('product', 'affiliateId'));
    }
}
