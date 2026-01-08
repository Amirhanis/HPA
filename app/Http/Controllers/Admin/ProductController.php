<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'brand', 'product_images')->get();
        $brands = Brand::get();
        $categories = Category::get();

        return Inertia::render(
            'Admin/Product/Index',
            [
                'products' => $products,
                'brands' => $brands,
                'categories' => $categories
            ]
        );
    }



    public function store(Request $request)
    {

        $product = new Product;
        $product->title = $request->title;
        $product->price = $request->price;
        $product->quantity = $request->quantity;
        $product->description = $request->description;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $product->save();

        //check if product has images upload

        if ($request->hasFile('product_images')) {
            Storage::disk('public')->makeDirectory('product_images');
            $productImages = $request->file('product_images');
            foreach ($productImages as $image) {
                $uniqueName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $storedPath = $image->storeAs('product_images', $uniqueName, 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => 'storage/' . $storedPath,
                ]);
            }
        }
        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    //update
    public function update(Request $request, $id)
    {

        $product = Product::findOrFail($id);

        if ($request->has('published')) {
            $product->published = $request->published;
            $product->inStock = $request->inStock;
            $product->save();

            return back()->with('success', 'Product status updated successfully');
        }

        // dd($product);
        $product->title = $request->title;
        $product->price = $request->price;
        $product->quantity = $request->quantity;
        $product->description = $request->description;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        // Check if product images were uploaded
        if ($request->hasFile('product_images')) {
            Storage::disk('public')->makeDirectory('product_images');
            $productImages = $request->file('product_images');
            foreach ($productImages as $image) {
                $uniqueName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $storedPath = $image->storeAs('product_images', $uniqueName, 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => 'storage/' . $storedPath,
                ]);
            }
        }
        $product->update();
        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function deleteImage($id)
    {
        $image = ProductImage::findOrFail($id);

        $relativePath = (string) $image->image;
        if (str_starts_with($relativePath, 'storage/')) {
            $diskPath = substr($relativePath, strlen('storage/'));
            if ($diskPath && Storage::disk('public')->exists($diskPath)) {
                Storage::disk('public')->delete($diskPath);
            }
        } else {
            $fullPath = public_path($relativePath);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $image->delete();
        return redirect()->route('admin.products.index')->with('success', 'Image deleted successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id)->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
