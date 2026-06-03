<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmcAcademyApiService
{
    /**
     * The base URL for AmcAcademy API.
     */
    protected string $baseUrl;

    /**
     * The fixed password required by AmcAcademy API.
     */
    protected string $apiPassword;

    public function __construct()
    {
        // For production, these should be moved to .env / config('services.amcacademy')
        $this->baseUrl = 'https://amcacademy.net/api/musoftwares';
        $this->apiPassword = 'MusoftwaresAmcApi2024!';
    }

    /**
     * Search for a mobile number in AmcAcademy.
     * This will also automatically trigger the 0.0625 deduction for Employee 22 in AmcAcademy.
     *
     * @param string $mobile
     * @return array|null
     */
    public function searchMobile(string $mobile): ?array
    {
        try {
            $response = Http::asForm()->post("{$this->baseUrl}/search_mobile", [
                'password' => $this->apiPassword,
                'mobile' => $mobile,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                }
            } else {
                Log::error('AmcAcademy API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AmcAcademy API Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Search for multiple FBIDs in bulk in AmcAcademy.
     * This triggers a deduction based on the count of numbers actually found.
     *
     * @param array $fbids
     * @return array|null Returns an associative array of [FBID => Phone] or null on failure.
     */
    public function searchFbidsBulk(array $fbids): ?array
    {
        if (empty($fbids)) {
            return [];
        }

        try {
            $response = Http::asForm()->post("{$this->baseUrl}/search_fbids_bulk", [
                'password' => $this->apiPassword,
                'fbids' => json_encode(array_values($fbids)),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                }
            } else {
                echo $response->body();
                Log::error('AmcAcademy API Bulk Search Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            echo  $e->getMessage();
            Log::error('AmcAcademy API Bulk Search Exception: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Process monthly deductions manually via API (Trigger end-of-month cleanup).
     *
     * @return array
     */
    public function processMonthlyDeductions(): array
    {
        try {
            $response = Http::asForm()->post("{$this->baseUrl}/process_monthly_deductions", [
                'password' => $this->apiPassword,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('AmcAcademy API Monthly Process Exception: ' . $e->getMessage());
        }

        return ['status' => 'error', 'message' => 'Failed to process deductions'];
    }
}
