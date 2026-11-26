<?php

namespace App\Services;

use App\Models\{Product, ProductKey, User, AuditLog};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeyImportService
{
    public function import(Product $product, array $lines, User $actor): int
    {
        $keys = array_values(array_filter(array_map('trim', $lines), fn ($key) => $key !== ''));
        if (count($keys) < 1 || count($keys) > 2000) {
            throw ValidationException::withMessages(['keys' => 'Nhập từ 1 đến 2.000 key mỗi lần.']);
        }
        $hashes = array_map([ProductKey::class, 'fingerprint'], $keys);
        if (count(array_unique($hashes)) !== count($hashes) || collect($keys)->contains(fn ($key) => mb_strlen($key) > 2000)) {
            throw ValidationException::withMessages(['keys' => 'Danh sách có key trùng hoặc dài quá 2.000 ký tự.']);
        }
        return DB::transaction(function () use ($product, $keys, $hashes, $actor) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (ProductKey::whereIn('fingerprint', $hashes)->exists()) {
                throw ValidationException::withMessages(['keys' => 'Có key đã tồn tại trong hệ thống. Chưa nhập key nào.']);
            }
            foreach ($keys as $index => $key) {
                ProductKey::create(['product_id' => $product->id, 'secret' => $key, 'fingerprint' => $hashes[$index], 'imported_by' => $actor->id]);
            }
            AuditLog::record('keys.imported', $product, ['count' => count($keys)]);
            return count($keys);
        }, 3);
    }
}
