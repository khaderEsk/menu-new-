<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class UserTakeoutService
{
    public function __construct(private OsrmService $osrmService)
    {
    }

    // to show all users
    public function all()
    {
        $users = User::whereRole(0)->get();
        return $users;
    }

    // to show paginate users
    public function paginate($id, $num)
    {
        $users = User::whereRestaurantId($id)->whereRole(0)->paginate($num);
        return $users;
    }

    // to create user
    public function create($data)
    {
        $data['role'] = 0;
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        if (request()->has('address') || request()->has('city') || request()->has('region') || request()->has('longitude') || request()->has('latitude')) {
            if (request()->has('longitude') && request()->has('latitude') && request()->longitude != null && request()->latitude != null) {
                $client = new Client();
                $headers = [
                    'User-Agent' => 'Menu/1.0 (your.email@example.com)'
                ];
                $response = $client->get('https://nominatim.openstreetmap.org/reverse', [
                    'headers' => $headers,
                    'query' => [
                        'lat' => $data['latitude'],
                        'lon' => $data['longitude'],
                        'format' => 'json',
                        'addressdetails' => 1,
                    ]
                ]);

                $d = json_decode($response->getBody(), true);
                if (isset($d['address'])) {
                    $city = $d['address']['city'] ?? null;
                    $region = $d['address']['state'] ?? null;
                    $street = $d['address']['road'] ?? null;
                    $neighborhood = $d['address']['suburb'] ?? null;

                    $addressParts = [$region, $city, $street, $neighborhood];

                    $addressParts = array_filter($addressParts, fn($value) => !is_null($value));

                    $r = implode(' - ', $addressParts);

                    $address = Address::create([
                        'city' => $region ?? null,
                        'region' => $r ?? null,
                        'url' => $data['url'] ?? null,
                        'user_id' => $user->id,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                    ]);
                }
            }
            if (request()->has('address') && request()->address != null) {
                $address = Address::create([
                    'city' => $data['city'] ?? null,
                    'region' => isset($data['region']) ? $data['region'] : (isset($data['address']) ? $data['address'] : null),
                    'url' => $data['url'] ?? null,
                    'user_id' => $user->id,
                ]);
            }
        }
        $user->assignRole(['takeout']);
        return $user;
    }

    // to update user
    public function update($restaurant_id, $data)
    {
        if (request()->has('password'))
            $data['password'] = Hash::make($data['password']);
        $user = User::where('restaurant_id', $restaurant_id)->whereId($data['id'])->update($data);
        return $user;
    }

    public function updateInfo(User $user, array $data): User
    {
        $user->update($data);
        return $user->load(['invoices', 'restaurant', 'latestAddress']);
    }

    /**
     * Get a single user by their ID, fully loaded with relationships.
     * (This is the existing `show` method, now improved for consistency).
     */
    public function show(string $id)
    {
        return User::with(['invoices', 'restaurant', 'latestAddress'])->findOrFail($id);
    }


    // to delete a user
    public function destroy(string $id, $admin)
    {
        return User::whereRestaurantId($admin)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data)
    {
        if ($data['is_active'] == 1) {
            $user = User::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $user = User::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $user;
    }

    public function getAndPruneAddresses(User $user): Collection
    {
        // ✅ DATA INTEGRITY: Wrap the entire multi-step process in a transaction.
        // This ensures that finding, deleting, and fetching all happen as one atomic operation.
        return DB::transaction(function () use ($user) {
            // 1. Find the IDs of the 3 most recent addresses for the user.
            $lastThreeIds = Address::where('user_id', $user->id)
                ->latest('created_at') // latest() is a cleaner way to write orderBy('created_at', 'desc')
                ->limit(3)
                ->pluck('id');

            // 2. Delete all other addresses for that user.
            Address::where('user_id', $user->id)
                ->whereNotIn('id', $lastThreeIds)
                ->delete();

            // 3. ✅ EFFICIENCY: Instead of querying again, we can be more direct.
            // We fetch the models whose IDs we already have.
            // This is slightly more efficient and guarantees we get the correct records.
            return Address::whereIn('id', $lastThreeIds)
                ->latest('created_at')
                ->get();
        });
    }

    public function addOrUpdateAddressAndCalculateDelivery(User $user, array $data): float
    {
        // 1. ✅ AUTHORIZATION: Ensure the user has the correct role.
        if ($user->role != 0) { // Assuming role 0 is a customer
            throw new AuthorizationException(trans('locale.youCantDoThisOperation'));
        }

        // 2. Handle the 'takeout' case where no delivery is needed.
        if (isset($data['isDelivery']) && $data['isDelivery'] == false) {
            return 0.0;
        }

        $restaurant = $user->restaurant; // Eager-load this in the controller for performance
        $address = null;

        // 3. ✅ SEPARATION OF CONCERNS: Determine which address to use or create.
        if (!empty($data['friend_address'])) {
            $coordinates = $this->getCoordinatesForAddressString($data['friend_address']);
            $address = Address::create([
                'user_id' => $user->id,
                'region' => $data['friend_address'],
                'latitude' => $coordinates['latitude'] ?? null,
                'longitude' => $coordinates['longitude'] ?? null,
            ]);
        } elseif (!empty($data['address'])) {
            $address = Address::findOrFail($data['address']);
            // Optionally update coordinates if they are not set
            if (empty($address->latitude) || empty($address->longitude)) {
                $coordinates = $this->getCoordinatesForAddressString($address->region);
                $address->update([
                    'latitude' => $coordinates['latitude'] ?? null,
                    'longitude' => $coordinates['longitude'] ?? null,
                ]);
            }
        } elseif (!empty($data['latitude']) && !empty($data['longitude'])) {
            $addressDetails = $this->getAddressFromCoordinates($data['latitude'], $data['longitude']);
            $address = Address::create([
                'user_id' => $user->id,
                'city' => $addressDetails['city'] ?? null,
                'region' => $addressDetails['region'] ?? 'N/A',
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);
        }

        if (!$address || empty($address->latitude) || empty($address->longitude)) {
            // If we couldn't determine an address with coordinates, we can't calculate a price.
            return 0.0;
        }

        // 4. ✅ BUG FIX: Use the OSRM service for accurate road distance.
        $routeData = $this->osrmService->getRoute(
            (float)$restaurant->latitude,
            (float)$restaurant->longitude,
            (float)$address->latitude,
            (float)$address->longitude
        );

        if (!$routeData || !isset($routeData['distance'])) {
            return 0.0; // Cannot calculate route, so price is 0.
        }

        $distanceInKm = $routeData['distance'] / 1000;
        return $distanceInKm * $restaurant->price_km;
    }

    /**
     * A private helper to get coordinates from an address string (Forward Geocoding).
     */
    private function getCoordinatesForAddressString(string $addressString): ?array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/search', [
            'q' => $addressString,
            'format' => 'json',
            'limit' => 1
        ]);

        if ($response->successful() && !empty($response->json())) {
            $location = $response->json()[0];
            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lon'],
            ];
        }
        return null;
    }

    /**
     * A private helper to get address details from coordinates (Reverse Geocoding).
     */
    private function getAddressFromCoordinates(float $latitude, float $longitude): ?array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
            'addressdetails' => 1,
        ]);

        if ($response->successful() && isset($response->json()['address'])) {
            $address = $response->json()['address'];
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;
            $region = $address['state'] ?? null;
            return ['city' => $city, 'region' => $region];
        }
        return null;
    }
}
