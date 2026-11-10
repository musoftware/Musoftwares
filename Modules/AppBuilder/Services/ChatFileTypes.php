<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderAttachment;
use ZipArchive;

/**
 * Which files the chat takes, judged by their content, never by the name alone. Images and PDFs go to Claude
 * as they are; Excel, Word and CSV files are read by the worker (in Docker), which sends back their text,
 * sheets and formulas. This class only recognises them; it does not read them.
 */
class ChatFileTypes
{
    public const EXCEL = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public const EXCEL_OLD = 'application/vnd.ms-excel';

    public const WORD = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public const CSV = 'text/csv';

    /** The files the worker reads for the chat. */
    public const WORKER_READS = [self::EXCEL, self::EXCEL_OLD, self::WORD, self::CSV];

    /** An old Excel file is an OLE2 compound file. */
    private const OLE_SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    /** The real type of a file the chat takes, or null when the chat does not take it. */
    public static function of(UploadedFile $file): ?string
    {
        $sniffed = (string) $file->getMimeType();
        if (in_array($sniffed, [...BuilderAttachment::IMAGE_TYPES, BuilderAttachment::PDF], true)) {
            return $sniffed;
        }

        return match (strtolower($file->getClientOriginalExtension())) {
            'xlsx' => self::zipHas($file, 'xl/workbook.xml') ? self::EXCEL : null,
            'docx' => self::zipHas($file, 'word/document.xml') ? self::WORD : null,
            'xls' => self::startsWith($file, self::OLE_SIGNATURE) ? self::EXCEL_OLD : null,
            'csv' => str_starts_with($sniffed, 'text/') ? self::CSV : null,
            default => null,
        };
    }

    public static function isReadByWorker(string $type): bool
    {
        return in_array($type, self::WORKER_READS, true);
    }

    private static function zipHas(UploadedFile $file, string $part): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath(), ZipArchive::RDONLY) !== true) {
            return false;
        }
        $found = $zip->locateName($part) !== false;
        $zip->close();

        return $found;
    }

    private static function startsWith(UploadedFile $file, string $signature): bool
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $head = $handle ? (string) fread($handle, strlen($signature)) : '';
        if ($handle) {
            fclose($handle);
        }

        return $head === $signature;
    }
}
