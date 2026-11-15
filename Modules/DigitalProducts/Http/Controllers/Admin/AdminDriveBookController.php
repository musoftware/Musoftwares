<?php

declare(strict_types=1);

namespace Modules\DigitalProducts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\DigitalProducts\Services\DriveBook;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** The admin pastes a Drive link; the site brings the PDF and shows it back so its cover can be read. */
class AdminDriveBookController extends Controller
{
    public function __construct(private readonly DriveBook $drive) {}

    public function fetch(Request $request): JsonResponse
    {
        $link = (string) $request->validate(['link' => 'required|string|max:1000'])['link'];

        try {
            $book = $this->drive->fetch($link);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($book + ['preview_url' => route('admin.digitalproducts.drive.show', $book['token'])]);
    }

    public function show(string $token): BinaryFileResponse
    {
        $path = $this->drive->path($token) ?? abort(404);

        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }
}
