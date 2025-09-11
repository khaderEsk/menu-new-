<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Restaurant;
use App\Models\RestaurantTranslation;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Admin\UpdateRestaurantRequest;
use App\Models\AppLink;
use Illuminate\Support\Str;

class AdminService
{
    // to update Admin
    public function update(Admin $admin, array $data): ?Admin
    {
        // If a password is provided ,and it's not empty, hash it.

        // Using !empty() checks for null, '', and false.
        if (array_key_exists('password', $data) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // If the password is null or empty, remove it from the data array
            // to prevent overwriting the existing password with an empty value.
            unset($data['password']);
        }

        // Perform the update directly on the model instance.
        // These returns true or false.
        $updated = $admin->update($data);

        // Return the updated model instance on success, or null on failure.
        // The calling method will have the complete, updated object.
        return $updated ? $admin : null;
    }

    // to update  Restaurant
    public function updateRestaurant(Restaurant $restaurant, UpdateRestaurantRequest $request): ?Restaurant
    {
        // Extract only the data relevant to the Restaurant model and its translations.
        $restaurantData = $request->safe()->except(['name_en', 'name_ar', 'note_en', 'note_ar']);
        $translationData = $request->safe()->only(['name_en', 'name_ar', 'note_en', 'note_ar']);

        try {
            DB::transaction(function () use ($restaurant, $restaurantData, $translationData, $request) {
                // 1. Update the main restaurant table attributes.
                $restaurant->update($restaurantData);

                // 2. Update the translation table attributes.
                foreach (['en', 'ar'] as $locale) {
                    if (isset($translationData['name_' . $locale]) || isset($translationData['note_' . $locale])) {
                        RestaurantTranslation::where('restaurant_id', $restaurant->id)
                            ->where('locale', $locale)
                            ->update([
                                'name' => $translationData['name_' . $locale] ?? $restaurant->translate($locale)->name,
                                'note' => $translationData['note_' . $locale] ?? $restaurant->translate($locale)->note,
                            ]);
                    }
                }

                // 3. Centralized Media Handling
                // An array of all possible media collections to process.
                $mediaCollections = [
                    'cover',
                    'logo',
                    'logo_home_page',
                    'background_image_home_page',
                    'background_image_category',
                    'background_image_sub',
                    'background_image_item'
                ];

                foreach ($mediaCollections as $collection) {
                    // We call a private helper method to avoid repeating code.
                    $this->handleMediaUpload($request, $restaurant, $collection);
                }
            });

            $linkKeys = ['user_link', 'delivery_link', 'admin_link'];
            $linksData = Arr::only($request->validated(), $linkKeys);
            $linksData['restaurant_id'] = $restaurant->id;
            AppLink::query()->update($linksData);
        } catch (\Throwable $e) {
            // If anything inside the transaction fails, log the error and return null.
            report($e);
            return null;
        }

        // Return the same restaurant instance, now with updated data.
        return $restaurant;
    }

    public function getAdminProfile(int $id)
    {
        return Admin::with([
            'restaurant',
            'roles',
            'permissions',
            'type',
            'categories'
        ])->findOrFail($id);
    }

    public function getSuperAdminProfile(int $id)
    {
        return \App\Models\SuperAdmin::with([
            'restaurant.emoji',
            'restaurant.FontEn',
            'restaurant.FontAr',
            'restaurant.fontTypeWelcome',
            'restaurant.fontTypeCategoryEn',
            'restaurant.fontTypeCategoryAr',
            'restaurant.fontTypeItemEn',
            'restaurant.fontTypeItemAr'
        ])->findOrFail($id);
    }

    public function getVisitorCount(Restaurant $restaurant): int
    {
        return $restaurant->tables()->sum('visited');
    }

    public function getQrCodeUrl(Restaurant $restaurant): ?string
    {
        // Using a relationship is cleaner than a separate query.
        $qr = $restaurant->ipQr()->first();
        if ($qr && $qr->qr_code) {
            return env('APP_URL') . '/' . str_replace('public', 'storage', $qr->qr_code);
        }
        return null;
    }

    public function calculateAverageWorkTime(Admin $admin, Request $request): array
    {
        // If the admin type is not an employee type, return default values immediately.
        if ($admin->type_id <= 2) {
            return ['count' => 'ــ', 'avg' => 'ــ'];
        }

        $query = $admin->employeeTables(); // Assumes 'employeeTables' relationship exists on Admin model.

        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('created_at', [$request->startDate, $request->endDate]);
        } elseif ($request->has('endDate')) {
            $query->whereDate('created_at', '<=', $request->endDate);
        } elseif ($request->has('startDate')) {
            $query->whereDate('created_at', '=', $request->startDate);
        }

        $employeeRecords = $query->get();
        $totalSeconds = 0;

        foreach ($employeeRecords as $record) {
            // Use Carbon for robust time parsing
            $totalSeconds += \Carbon\Carbon::parse($record->order_time)->secondsSinceMidnight();
        }

        $recordCount = $employeeRecords->count();
        if ($recordCount === 0) {
            return ['count' => 0, 'avg' => '00:00:00'];
        }

        $averageSeconds = $totalSeconds / $recordCount;
        $hours = floor($averageSeconds / 3600);
        $minutes = floor(($averageSeconds % 3600) / 60);
        $seconds = $averageSeconds % 60;

        return [
            'count' => $recordCount,
            'avg' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
        ];
    }

    private function handleMediaUpload(Request $request, Restaurant $restaurant, string $collectionName): void
    {
        // Check if the request has a file for the given collection name.
        if ($request->hasFile($collectionName)) {
            // Clear any old media in this collection first.
            $restaurant->clearMediaCollection($collectionName);

            // Add the new media from the request.
            $restaurant->addMediaFromRequest($collectionName)
                ->usingFileName(Str::random(10) . '.' . $request->file($collectionName)->getClientOriginalExtension())
                ->toMediaCollection($collectionName);
        }
    }
}
