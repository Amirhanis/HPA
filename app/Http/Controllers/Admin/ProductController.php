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
    private function s3IsConfigured(): bool
    {
        return (bool) (config('filesystems.disks.s3.key')
            && config('filesystems.disks.s3.secret')
            && config('filesystems.disks.s3.bucket'));
    }

    private function productImagesDisk(): string
    {
        // Local "default" disk is private (storage/app/private) and not web-accessible.
        // Use "public" for local/dev, and "s3" when you set FILESYSTEM_DISK=s3 in production.
        return (config('filesystems.default') === 's3' && $this->s3IsConfigured()) ? 's3' : 'public';
    }

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
            $disk = $this->productImagesDisk();
                if ($disk !== 's3') {
                    Storage::disk($disk)->makeDirectory('product_images');
                }
            $productImages = $request->file('product_images');
            foreach ($productImages as $image) {
                $uniqueName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                // Do not force public ACL. Many new S3 buckets have ACLs disabled (Bucket owner enforced),
                // which makes "public" visibility uploads fail.
                $storedPath = $image->storeAs('product_images', $uniqueName, $disk);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $storedPath,
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
            $disk = $this->productImagesDisk();
                if ($disk !== 's3') {
                    Storage::disk($disk)->makeDirectory('product_images');
                }
            $productImages = $request->file('product_images');
            foreach ($productImages as $image) {
                $uniqueName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $storedPath = $image->storeAs('product_images', $uniqueName, $disk);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $storedPath,
                ]);
            }
        }
        $product->update();
        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function deleteImage($id)
    {
        $image = ProductImage::findOrFail($id);

        $disk = $this->productImagesDisk();

        $relativePath = (string) $image->image;
        // Backward compatibility: older records stored as "storage/product_images/...".
        if (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, strlen('storage/'));
            $disk = 'public';
        }

        if ($relativePath) {
            try {
                if (Storage::disk($disk)->exists($relativePath)) {
                    Storage::disk($disk)->delete($relativePath);
                }
            } catch (\Throwable $e) {
                // Ignore storage errors; still delete DB record.
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
