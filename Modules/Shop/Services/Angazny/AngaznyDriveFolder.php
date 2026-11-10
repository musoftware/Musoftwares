<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Reads the photo list of a public Google Drive folder, including folders inside it. */
class AngaznyDriveFolder
{
    private const MAX_DEPTH = 2;

    private const TIMEOUT_SECONDS = 30;

    private const ROW = '~<tr[^>]*data-id="([^"]+)"[^>]*data-target="[^"]+"(.*?)</tr>~s';

    private const LABEL = '~(?:data-tooltip|aria-label)="[^"]*?\s(Image|Video|Folder|Shared folder)"~';

    /** @return list<string>|null photo ids, or null when Drive could not be read */
    public function imageIds(string $link): ?array
    {
        if (preg_match('~folders/([-\w]{10,})~', $link, $match) !== 1) {
            return [];
        }

        return $this->walk($match[1], 0);
    }

    private function walk(string $folderId, int $depth): ?array
    {
        $page = $this->page($folderId);
        if ($page === null) {
            return null;
        }

        $ids = [];
        foreach ($this->rows($page) as [$id, $kind]) {
            $found = $this->resolve($id, $kind, $depth);
            if ($found === null) {
                return null;
            }
            $ids = [...$ids, ...$found];
        }

        return array_values(array_unique($ids));
    }

    private function resolve(string $id, string $kind, int $depth): ?array
    {
        if ($kind === 'Image') {
            return [$id];
        }

        $isFolder = in_array($kind, ['Folder', 'Shared folder'], true);

        return $isFolder && $depth < self::MAX_DEPTH ? $this->walk($id, $depth + 1) : [];
    }

    /** @return list<array{0: string, 1: string}> id and Drive's type word for each row */
    private function rows(string $page): array
    {
        preg_match_all(self::ROW, $page, $rows, PREG_SET_ORDER);
        $found = [];

        foreach ($rows as [, $id, $body]) {
            $kind = preg_match(self::LABEL, $body, $label) === 1 ? $label[1] : '';
            $found[] = [$id, $kind];
        }

        return $found;
    }

    private function page(string $folderId): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withUserAgent('Mozilla/5.0 MusoftwareStore/1.0')
                ->get('https://drive.google.com/drive/folders/'.$folderId, ['hl' => 'en']);
        } catch (ConnectionException $exception) {
            Log::warning('Drive folder could not be reached', ['folder' => $folderId, 'error' => $exception->getMessage()]);

            return null;
        }

        return $response->successful() ? $response->body() : null;
    }
}
