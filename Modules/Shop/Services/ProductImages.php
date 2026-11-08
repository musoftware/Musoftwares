<?php

namespace Modules\Shop\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImages
{
    private const DISK = 'public_uploads';

    /**
     * Only paths that already belong to this product can be removed.
     *
     * @param  list<string>  $current
     * @param  list<string>  $removed
     * @param  list<UploadedFile>  $added
     * @return list<string>
     */
    public function update(array $current, array $removed, array $added): array
    {
        $toDelete = array_values(array_intersect($current, $removed));
        Storage::disk(self::DISK)->delete($toDelete);

        $stored = array_map(fn (UploadedFile $file) => $file->store('shop/products', self::DISK), $added);

        return [...array_values(array_diff($current, $toDelete)), ...$stored];
    }
}
