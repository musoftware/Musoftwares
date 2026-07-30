<?php

namespace Modules\Listing\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Modules\Listing\Models\Listing;
use Modules\Listing\Notifications\ListingAuthorRegisteredNotification;

class AutoUserRegistrationService
{
    /**
     * Register a user (if not exists) and save the scraped listing.
     *
     * @param array $adData
     * @return Listing
     */
    public function registerAndPost(array $adData): Listing
    {
        return DB::transaction(function () use ($adData) {
            $email = $adData['email'];
            $phone = $adData['phone'];

            // 1. Find or create the user
            $user = User::where('email', $email)
                ->orWhere('phone_number', $phone)
                ->first();

            $isNewUser = false;
            $generatedPassword = null;

            if (!$user) {
                $isNewUser = true;
                $generatedPassword = Str::random(10);

                // Create user
                $user = new User([
                    'name' => 'Employer (' . ($adData['city'] ?? 'مصر') . ')',
                    'email' => $email,
                    'password' => Hash::make($generatedPassword),
                ]);
                $user->phone_number = $phone;
                $user->save();

                Log::info("[AutoUserRegistration] Created new user: ID {$user->id} | Email: {$email} | Phone: {$phone}");
            } else {
                Log::info("[AutoUserRegistration] Found existing user: ID {$user->id} for Email: {$email} or Phone: {$phone}");
            }

            // 2. Ensure they have an active subscription for the listing module
            if (!$user->hasModuleSubscription('listing')) {
                $user->subscriptions()->create([
                    'object' => 'listing',
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => now()->addYears(10), // Long-term active subscription
                ]);
                Log::info("[AutoUserRegistration] Granted 'listing' subscription to User ID {$user->id}");
            }

            // 3. Create the listing
            $listing = Listing::updateOrCreate(
                ['waseet_id' => $adData['waseet_id']],
                [
                    'user_id' => $user->id,
                    'title' => $adData['title'],
                    'description' => $adData['description'],
                    'price' => $adData['price'] ?? 0,
                    'currency' => $adData['currency'] ?? 'ج.م',
                    'phone' => $phone,
                    'email' => $email,
                    'original_url' => $adData['original_url'],
                    'images' => $adData['images'] ?? [],
                    'city' => $adData['city'] ?? null,
                    'status' => 'active',
                ]
            );

            Log::info("[AutoUserRegistration] Created/Updated listing: ID {$listing->id} | Waseet ID {$adData['waseet_id']}");

            // 4. Send email notification with login details if the user is new
            if ($isNewUser) {
                try {
                    $user->notify(new ListingAuthorRegisteredNotification($generatedPassword, $listing));
                    Log::info("[AutoUserRegistration] Dispatched welcome notification to email: {$email}");
                } catch (\Throwable $e) {
                    Log::error("[AutoUserRegistration] Failed to send notification to {$email}: " . $e->getMessage());
                }
            }

            return $listing;
        });
    }
}
