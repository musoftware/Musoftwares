<?php

namespace App\Services;

class LeadDataCleaningService
{
    public static function cleanName(?string $name): ?string
    {
        return $name !== null ? trim(strip_tags($name)) : null;
    }

    public static function cleanEmail(?string $email): ?string
    {
        return $email !== null ? strtolower(trim(strip_tags($email))) : null;
    }

    public static function cleanPhone(?string $phone): ?string
    {
        return $phone !== null ? preg_replace('/[^\d+]/', '', trim(strip_tags($phone))) : null;
    }

    public static function cleanCompany(?string $company): ?string
    {
        return $company !== null ? trim(strip_tags($company)) : null;
    }

    public static function cleanMessage(?string $message): ?string
    {
        return $message !== null ? trim(strip_tags($message)) : null;
    }
}
