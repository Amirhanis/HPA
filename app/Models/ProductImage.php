<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'image',];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        $value = (string) ($this->image ?? '');
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $defaultDisk = (string) config('filesystems.default');
        $s3Configured = (bool) (config('filesystems.disks.s3.key')
            && config('filesystems.disks.s3.secret')
            && config('filesystems.disks.s3.bucket'));

        $disk = ($defaultDisk === 's3' && $s3Configured) ? 's3' : 'public';

        // Legacy format: older code stored "storage/<path>". If we're on S3, treat it as a key
        // (strip the prefix) so existing DB rows still render correctly.
        if (str_starts_with($value, 'storage/')) {
            if ($disk === 's3') {
                $value = substr($value, strlen('storage/'));
            } else {
                return '/' . ltrim($value, '/');
            }
        }

        if ($disk === 's3') {
            // Works for private buckets too (recommended). If temporary URLs are not supported,
            // fall back to the standard url().
            try {
                return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(60));
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return Storage::disk($disk)->url($value);
    }

    function product()
    {
        return $this->belongsTo(Product::class);
    }
}
