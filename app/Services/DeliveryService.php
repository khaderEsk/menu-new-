<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeliveryService
{
    protected OsrmService $osrmService;

    // 1. Inject the OsrmService into the constructor.
    public function __construct(OsrmService $osrmService)
    {
        $this->osrmService = $osrmService;
    }

    // to show all deliveries
    public function all(int $restaurant_id)
    {
        // 1. Use the private helper to build the base query with all relationships.
        $query = $this->getDeliveryQuery($restaurant_id);

        // 2. Add the specific condition for this method and get the results.
        $deliveries = $query->where('is_active', 1)->get();

        // 3. Add the calculated distance to each model in the collection.
        return $this->addDistanceToDeliveries($deliveries);
    }

    // to show paginate deliveries
    public function paginate(int $restaurant_id, int $perPage, ?string $searchTerm = null)
    {
        // 1. Use the private helper to build the base query.
        $query = $this->getDeliveryQuery($restaurant_id, $searchTerm);

        // 2. Get the paginated results.
        $paginatedDeliveries = $query->paginate($perPage);

        // 3. Add the distance calculation only to the items on the current page.
        $this->addDistanceToDeliveries($paginatedDeliveries->getCollection());

        return $paginatedDeliveries;
    }
    public function paginateDelivery(int $restaurant_id, int $id)
    {
        // 1. Use the private helper to build the base query.
        $query = $this->findDelivery($restaurant_id, $id);

        // 2. Get the paginated results.
        // $paginatedDeliveries = $query->paginate($perPage);

        // 3. Add the distance calculation only to the items on the current page.
        // $this->addDistanceToDeliveries($query->getCollection());

        return $query;
    }

    public function findDelivery(int $restaurant_id, int $id)
    {
        $query = User::where('restaurant_id', $restaurant_id)->where('role', 1)->where('id', $id)->first();
        $query->with(['restaurant']);
        // 🚀 PERFORMANCE WIN: Eager load all relationships needed by the resource.
        // $query->with(['invoices']);

        // if ($searchTerm) {
        //     $query->where(function ($q) use ($searchTerm) {
        //         $q->where('name', 'LIKE', "%{$searchTerm}%")
        //             ->orWhere('username', 'LIKE', "%{$searchTerm}%")
        //             ->orWhere('email', 'LIKE', "%{$searchTerm}%")
        //             ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        //     });
        // }

        return $query;
    }

    private function getDeliveryQuery(int $restaurant_id, ?string $searchTerm = null)
    {
        $query = User::where('restaurant_id', $restaurant_id)->where('role', 1);

        $query->with(['restaurant', 'latestAddress']);
        $query->with('invoices', function ($q) {
            $q->whereNot('status', 6);
        });

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('username', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    private function addDistanceToDeliveries($deliveries)
    {
        return $deliveries->transform(function ($delivery) {
            $restaurant = $delivery->restaurant;
            $distance = null;

            if (
                $restaurant && !empty($restaurant->latitude) && !empty($restaurant->longitude) &&
                !empty($delivery->latitude) && !empty($delivery->longitude)
            ) {

                $routeData = $this->osrmService->getRoute(
                    (float)$delivery->latitude,
                    (float)$delivery->longitude,
                    (float)$restaurant->latitude,
                    (float)$restaurant->longitude
                );

                if ($routeData && isset($routeData['distance'])) {
                    $distance = round($routeData['distance'] / 1000, 2); // In KM
                }
            }

            // Add the distance as a new property on the model.
            $delivery->distance = $distance;

            return $delivery;
        });
    }

    // to create delivery
    public function create(array $data,  $imageFile): ?User
    {
        // Add the required 'role' and hash the password before creation.
        $data['role'] = 1;
        $data['password'] = Hash::make($data['password']);

        try {
            // ✅ DATA INTEGRITY: Wrap creation and file upload in a transaction.
            // If the image upload fails, the user creation will be rolled back.
            $delivery = DB::transaction(function () use ($data, $imageFile) {
                // 1. Create the user.
                $user = User::create($data);

                // 2. If an image was provided, handle the upload.
                if ($imageFile) {
                    $user->addMedia($imageFile)
                        ->usingFileName(Str::random(10) . '.' . $imageFile->getClientOriginalExtension())
                        ->toMediaCollection('delivery');
                }

                return $user;
            });

            return $delivery->load(['restaurant', 'invoices', 'latestAddress']);
        } catch (\Throwable $e) {
            report($e); // Log the actual error for debugging.
            return null; // Return null on failure.
        }
    }

    // to update delivery
    public function update(User $delivery, array $data, ?UploadedFile $imageFile): ?User
    {
        // If a new password is provided and it's not empty, hash it.
        // Otherwise, remove it from the data array to avoid overwriting the existing password.
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        try {
            // ✅ DATA INTEGRITY: Wrap the entire operation in a transaction.
            // If the image upload fails, the database update will be rolled back.
            DB::transaction(function () use ($delivery, $data, $imageFile) {
                // 1. Update the user's details in the database.
                $delivery->update($data);

                // 2. If a new image was provided, replace the old one.
                if ($imageFile) {
                    $delivery->clearMediaCollection('delivery');
                    $delivery->addMedia($imageFile)
                        ->usingFileName(Str::random(10) . '.' . $imageFile->getClientOriginalExtension())
                        ->toMediaCollection('delivery');
                }
            });

            // 🚀 PERFORMANCE WIN: Return the updated model, fully loaded with all necessary relationships.
            // This prevents the controller from needing to re-fetch the user.
            return $delivery->load(['restaurant', 'invoices', 'latestAddress']);
        } catch (\Throwable $e) {
            report($e); // Log the actual error for debugging.
            return null; // Return null on failure.
        }
    }

    // to show a delivery
    public function show(string $id)
    {
        return User::with('invoices')->findOrFail($id);
    }

    // to delete a delivery
    public function destroy(string $id, $admin)
    {
        return User::whereRestaurantId($admin)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data)
    {
        if ($data['is_active'] == 1) {
            $delivery = User::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $delivery = User::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $delivery;
    }
}
