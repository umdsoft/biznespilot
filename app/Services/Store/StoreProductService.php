<?php

namespace App\Services\Store;

use App\Models\Store\StoreProduct;
use App\Models\Store\StoreProductImage;
use App\Models\Store\TelegramStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreProductService
{
    public function createProduct(TelegramStore $store, array $data): StoreProduct
    {
        // multipart/form-data Form'da images bilan birga kelgan paytda
        // nested array'lar (variants, metadata) JSON string sifatida keladi —
        // PHP'ning native FormData parser nested object'larni qo'llab-quvvatlamaydi.
        // Defensive decode: string → array.
        $variants = $this->decodeIfJsonString($data['variants'] ?? []);
        $metadata = $this->decodeIfJsonString($data['metadata'] ?? null);

        $product = StoreProduct::create([
            'store_id' => $store->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(4),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'compare_price' => $data['compare_price'] ?? null,
            'sku' => $data['sku'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'track_stock' => $this->toBool($data['track_stock'] ?? true),
            'is_active' => $this->toBool($data['is_active'] ?? true),
            'is_featured' => $this->toBool($data['is_featured'] ?? false),
            'metadata' => $metadata,
        ]);

        // Create variants if provided
        if (is_array($variants) && ! empty($variants)) {
            foreach ($variants as $variantData) {
                if (! is_array($variantData) || empty($variantData['name'] ?? null)) {
                    continue; // Bo'sh yoki noto'g'ri variantni o'tkazib yuboramiz
                }
                $product->variants()->create([
                    'name' => $variantData['name'],
                    'sku' => $variantData['sku'] ?? null,
                    'price' => $variantData['price'] ?? $data['price'],
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'attributes' => $this->decodeIfJsonString($variantData['attributes'] ?? null),
                ]);
            }
        }

        return $product->load(['images', 'variants', 'category']);
    }

    /**
     * multipart/form-data orqali kelgan JSON string'ni array'ga aylantirish.
     * Allaqachon array bo'lsa o'zgartirmaydi.
     */
    private function decodeIfJsonString(mixed $value): mixed
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * multipart/form-data boolean values "true"/"false"/"1"/"0" string sifatida
     * keladi. Eloquent boolean cast bilan ham xato beradi ba'zan.
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }
        return (bool) $value;
    }

    public function updateProduct(StoreProduct $product, array $data): StoreProduct
    {
        $updateData = collect($data)->only([
            'category_id', 'name', 'description', 'price', 'compare_price',
            'sku', 'stock_quantity', 'track_stock', 'is_active', 'is_featured', 'metadata',
        ])->toArray();

        // multipart/form-data normalize: bool va JSON string fields
        foreach (['track_stock', 'is_active', 'is_featured'] as $boolField) {
            if (array_key_exists($boolField, $updateData)) {
                $updateData[$boolField] = $this->toBool($updateData[$boolField]);
            }
        }
        if (array_key_exists('metadata', $updateData)) {
            $updateData['metadata'] = $this->decodeIfJsonString($updateData['metadata']);
        }

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $updateData['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        }

        $product->update($updateData);

        return $product->fresh(['images', 'variants', 'category']);
    }

    public function deleteProduct(StoreProduct $product): void
    {
        // Delete images from storage
        foreach ($product->images as $image) {
            $this->deleteImageFile($image->image_url);
        }

        $product->delete();
    }

    public function uploadImage(StoreProduct $product, UploadedFile $file, bool $isPrimary = false): StoreProductImage
    {
        $disk = config('store.upload.disk', 'public');
        $path = config('store.upload.path', 'store/products');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs($path . '/' . $product->store_id, $filename, $disk);

        // If primary, unset other primaries
        if ($isPrimary) {
            $product->images()->update(['is_primary' => false]);
        }

        $sortOrder = $product->images()->max('sort_order') ?? 0;

        return StoreProductImage::create([
            'product_id' => $product->id,
            'image_url' => '/storage/' . $filePath,
            'sort_order' => $sortOrder + 1,
            'is_primary' => $isPrimary || $product->images()->count() === 0,
        ]);
    }

    public function deleteImage(StoreProductImage $image): void
    {
        $this->deleteImageFile($image->image_url);
        $image->delete();
    }

    protected function deleteImageFile(string $url): void
    {
        $disk = config('store.upload.disk', 'public');

        // Extract disk-relative path from both full URLs and relative paths
        $path = parse_url($url, PHP_URL_PATH);
        if ($path && str_starts_with($path, '/storage/')) {
            $path = substr($path, strlen('/storage/'));
        }

        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function reorderProducts(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            StoreProduct::where('id', $id)->update(['sort_order' => $index]);
        }
    }
}
