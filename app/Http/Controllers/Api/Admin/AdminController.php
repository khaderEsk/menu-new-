<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IdRequest;
use App\Http\Requests\Admin\UpdateRequest;
use App\Http\Requests\Admin\UpdateRestaurantRequest;
use App\Http\Requests\Restaurant\IdRequest as RestaurantIdRequest;
use App\Http\Resources\AdminResource;
use App\Http\Resources\RestaurantResource;
use App\Models\Admin;
use App\Models\IpQr;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use App\Models\Table;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminController extends Controller
{

    public function __construct(private AdminService $adminService)
    {
    }

    // Update Admin
    // Update Restaurant Admin

    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            // We get the authenticated admin model instance.
            $admin = auth()->user();

            // Call the service. It now returns the updated model or null.
            $updatedAdmin = $this->adminService->update($admin, $request->validated());

            // Check if the update was successful. This is clearer than checking for '0'.
            if (!$updatedAdmin) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 422);
            }

            // 'restaurant' relationship is loaded for the API Resource.
            $updatedAdmin->load('restaurant');

            // Pass the updated and loaded model directly to the resource.
            $data = AdminResource::make($updatedAdmin);

            return $this->successResponse($data, trans('locale.updated'), 200);

        } catch (Throwable $th) {
            // It's good practice to log the actual error for debugging
            // and return a generic message to the client.
            Log::error('Admin Update Failed: ' . $th->getMessage());
            return $this->messageErrorResponse(trans('locale.something_went_wrong')); // Generic error
        }
    }

    public function updateRestaurantAdmin(UpdateRestaurantRequest $request): JsonResponse
    {
        try {
            // 1. Get the restaurant directly from the authenticated user.
            $restaurant = auth()->user()->restaurant;

            // 2. Pass the restaurant and the entire request to the service.
            // The service now handles all data processing and file uploads.
            $updatedRestaurant = $this->adminService->updateRestaurant($restaurant, $request);

            // 3. Check if the service method failed.
            if (!$updatedRestaurant) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 422); // 422 is often better for a failed update.
            }

            // 🚀 PERFORMANCE WIN: Eager load all relationships needed by the Resource.
            // This turns many database queries into just one, preventing the N+1 problem.
            $updatedRestaurant->load([
                'emoji', 'FontEn', 'FontAr', 'fontTypeWelcome',
                'fontTypeCategoryEn', 'fontTypeCategoryAr',
                'fontTypeItemEn', 'fontTypeItemAr'
            ]);

            // 4. Pass the fully loaded, updated model to the resource.
            $data = RestaurantResource::make($updatedRestaurant);

            return $this->successResponse($data, trans('locale.updated'), 200);

        } catch (\Throwable $th) {
            // Log the detailed error for debugging purposes.
            Log::error('Restaurant Update Failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            // Return a generic error to the user.
            return $this->messageErrorResponse('An unexpected error occurred.');
        }
    }

    // Show Admin By id
    public function showById(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Path for SuperAdmins
            if ($user instanceof SuperAdmin) {
                $superAdmin = $this->adminService->getSuperAdminProfile($user->id);
                $qrUrl = $this->adminService->getQrCodeUrl($superAdmin->restaurant);

                // Use a dedicated resource to ensure the output is correct and stable.
                $resource = AdminResource::make($superAdmin);
                $resource->qr_offline = $qrUrl; // Safely add the extra data.

                return $this->successResponse($resource, trans('locale.adminFound'), 200);
            }

            // Path for regular Admins
            if ($user instanceof Admin) {
                $admin = $this->adminService->getAdminProfile($user->id);
                $workTimeData = $this->adminService->calculateAverageWorkTime($admin, $request);
                $qrUrl = $this->adminService->getQrCodeUrl($admin->restaurant);

                // Pass the calculated data to the resource.
                $resource = AdminResource::make($admin);
                $resource->workTimeData = $workTimeData; // Pass the array with 'count' and 'avg'
                $resource->qr_offline = $qrUrl;

                return $this->successResponse($resource, trans('locale.adminFound'), 200);
            }

            // Fallback for unknown user types
            return $this->messageErrorResponse('User type not supported.', 403);

        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while fetching the profile.');
        }
    }
}
