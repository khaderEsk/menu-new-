<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\City;
use App\Models\Emoji;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use App\Models\Package;
use App\Models\PackageRestaurant;
use App\Models\MenuTemplate;
use App\Models\News;
use App\Models\Order;
use App\Models\Table;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Laravel\Prompts\error;

class LogUserActionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if ($request->is('superAdmin_api/update_super_admin_restaurant_id') || $request->is('admin_api/update_super_admin_restaurant_id') ) {
        //     return $next($request);
        // }
        if ($request->isMethod('post')) {
            $id = $request->input('id');
            $originalData = null;

            if ($id) {
                $originalData = $this->getOriginalData($request, $id);
                if ($originalData) {
                    $request->attributes->set('oldData', $originalData);
                }
            }
        }
        $response = $next($request);

        if (Auth::check() && $response->getStatusCode() < 400) {
            $this->logUserActivity($request, $response);
        }

        return $response;
    }

    private function getOriginalData(Request $request, $id)
    {
        $url = $request->fullUrl();
        if (preg_match('/\/superAdmin_api\/update_city/', $url)) {
            return City::find($id)->toArray();
        } elseif (preg_match('/\/superAdmin_api\/update_emoji/', $url)) {
            return Emoji::find($id)->toArray();
        } elseif (preg_match('/\/superAdmin_api\/update_restaurant_manager/', $url)) {
            return Admin::find($id);
        } elseif (preg_match('/\/superAdmin_api\/update_restaurant/', $url)) {
            return Restaurant::find($id);
        } elseif (preg_match('/\/superAdmin_api\/update_admin_restaurant/', $url)) {
            return Admin::find($id);
        } elseif (preg_match('/\/superAdmin_api\/update_admin/', $url)) {
            return SuperAdmin::find($id)->toArray();
        }
        elseif (preg_match('/\/superAdmin_api\/update_super_admin_restaurant_id/', $url)) {
            return SuperAdmin::where('restaurant_id',$id)->get()->toArray();
        }
         elseif (preg_match('/\/superAdmin_api\/restaurant_manager/', $url)) {
            return SuperAdmin::find($id)->toArray();
        } elseif (preg_match('/\/superAdmin_api\/add_package/', $url)) {
            return Package::find($id)->toArray();
        } elseif (preg_match('/\/superAdmin_api\/add_subscription/', $url)) {
            return PackageRestaurant::find($id)->toArray();
        }
            // -----------------Admin--------------
        elseif (preg_match('/\/admin_api\/update_category/', $url)) {
            return Category::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_item/', $url)) {
            return Item::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_admin/', $url)) {
            return Admin::find($id);
        } elseif (preg_match('/\/admin_api\/update_restaurant_admin/', $url)) {
            return Restaurant::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_order/', $url)) {
            return Order::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_status_invoice_paid/', $url)) {
            return Invoice::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_user/', $url)) {
            return Admin::find($id);
        } elseif (preg_match('/\/admin_api\/update_advertisement/', $url)) {
            return Advertisement::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_news/', $url)) {
            return News::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/update_table/', $url)) {
            return Table::find($id)->toArray();
        }
        elseif (preg_match('/\/admin_api\/update_super_admin_restaurant_id/', $url)) {
            // return Admin::find($id)->toArray();
            return Admin::where('restaurant_id',$id)->get()->toArray();

        }
            // -----------------permissions & roles--------------
        elseif (preg_match('/\/superAdmin_api\/roles/', $url)) {
            return Role::find($id)->toArray();
        } elseif (preg_match('/\/superAdmin_api\/permissions/', $url)) {
            return Permission::find($id)->toArray();
        } elseif (preg_match('/\/admin_api\/roles/', $url)) {
            return Role::find($id)->toArray();
        }

        return null;
    }

    private function logUserActivity(Request $request, Response $response)
    {
        $user = Auth::user();
        $method = $request->method();
        $url = $request->fullUrl();
        $action = $this->determineAction($method);
        $entityType = '';
        $entityId = null;

        $originalData = $request->attributes->get('oldData', false);
        $responseData = json_decode($response->getContent(), true);
        $id = $responseData['data']['id'] ?? null;

        if ($id) {
            $entityType = $this->determineEntityType($url, $id, $action, $entityId);
        } else {
            $entityType = $this->determineEntityTypeFromUrl($url, $action);
        }

        $newData = $id ? $this->getNewData($entityType, $id) : null;

        $user->activityLogs()->create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $request->header('User-Agent') ." " . $url,
            'original_data' => $originalData ? json_encode($originalData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'ip_address' => $request->ip() ?? null,
        ]);
    }

    private function determineAction($method)
    {
        switch ($method) {
            case 'POST':
                return 'created or updated';
            case 'PUT':
            case 'PATCH':
                return 'updated';
            case 'DELETE':
                return 'deleted';
            case 'GET':
                return 'read';
            default:
                return 'accessed';
        }
    }

    private function determineEntityType($url, $id, &$action, &$entityId)
    {
        if (preg_match('/\/superAdmin_api\/add_city/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'city';
        } elseif (preg_match('/\/superAdmin_api\/update_city/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'city';
        } elseif (preg_match('/\/superAdmin_api\/show_city_by_id/', $url)) {
            $entityId = $id;
            return 'city';
        } elseif (preg_match('/\/superAdmin_api\/add_admin/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'City Super Admins, Data Entries';
        } elseif (preg_match('/\/superAdmin_api\/update_admin/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'City Super Admins, Data Entries';
        } elseif (preg_match('/\/superAdmin_api\/show_admin/', $url)) {
            $entityId = $id;
            return 'City Super Admins, Data Entries';
        } elseif (preg_match('/\/superAdmin_api\/add_emoji/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'Emoji';
        } elseif (preg_match('/\/superAdmin_api\/update_emoji/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'Emoji';
        } elseif (preg_match('/\/superAdmin_api\/show_emoji_by_id/', $url)) {
            $entityId = $id;
            return 'Emoji';
        } elseif (preg_match('/\/superAdmin_api\/add_restaurant/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'Restaurant';
        } elseif (preg_match('/\/superAdmin_api\/update_restaurant_manager/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'Restaurant Manager';
        } elseif (preg_match('/\/superAdmin_api\/update_restaurant/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'Restaurant';
        } elseif (preg_match('/\/superAdmin_api\/update_admin_restaurant/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'Admin';
        } elseif (preg_match('/\/superAdmin_api\/show_restaurant_manager/', $url)) {
            $entityId = $id;
            return 'Restaurant Manager';
        } elseif (preg_match('/\/superAdmin_api\/show_restaurant/', $url)) {
            $entityId = $id;
            return 'Restaurant';
        }
        elseif (preg_match('/\/superAdmin_api\/update_super_admin_restaurant_id/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'Super admin restaurant_id';
        }
        elseif (preg_match('/\/superAdmin_api\/restaurant_manager/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'Restaurant Manager';
        } elseif (preg_match('/\/superAdmin_api\/add_menu_form/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'Menu Template';
        } elseif (preg_match('/\/superAdmin_api\/show_menu_form_by_id/', $url)) {
            $entityId = $id;
            return 'Menu Template';
        } elseif (preg_match('/\/superAdmin_api\/add_package/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'package';
        } elseif (preg_match('/\/superAdmin_api\/update_package/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'package';
        } elseif (preg_match('/\/superAdmin_api\/show_package/', $url)) {
            $entityId = $id;
            return 'package';
        } elseif (preg_match('/\/superAdmin_api\/add_subscription/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'subscription';
        } elseif (preg_match('/\/superAdmin_api\/update_super_admin/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'Super Admin';
        }

        // --------------------------Admin------------------------
        elseif (preg_match('/\/admin_api\/add_category/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'category';
        } elseif (preg_match('/\/admin_api\/update_category/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'category';
        }

        elseif (preg_match('/\/admin_api\/add_item/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'item';
        } elseif (preg_match('/\/admin_api\/update_item/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'item';
        }

        elseif (preg_match('/\/admin_api\/update_admin/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'admin';
        } elseif (preg_match('/\/admin_api\/update_restaurant_admin/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'restaurant';
        } elseif (preg_match('/\/admin_api\/show_admin/', $url)) {
            $entityId = $id;
            return 'admin';
        }

        elseif (preg_match('/\/admin_api\/add_order/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'order';
        } elseif (preg_match('/\/admin_api\/update_order/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'order';
        } elseif (preg_match('/\/admin_api\/show_orders_invoice/', $url)) {
            $entityId = $id;
            return 'orders invoice';
        } elseif (preg_match('/\/admin_api\/show_order/', $url)) {
            $entityId = $id;
            return 'order';
        } elseif (preg_match('/\/admin_api\/show_invoice/', $url)) {
            $entityId = $id;
            return 'invoice';
        } elseif (preg_match('/\/admin_api\/add_invoice/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'invoice';
        } elseif (preg_match('/\/admin_api\/update_status_invoice_paid/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'invoice';
        }

        elseif (preg_match('/\/admin_api\/add_user/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'admin or employee or data entry';
        } elseif (preg_match('/\/admin_api\/update_user/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'admin or employee or data entry';
        } elseif (preg_match('/\/admin_api\/show_user/', $url)) {
            $entityId = $id;
            return 'admin or employee or data entry';
        }

        elseif (preg_match('/\/admin_api\/add_advertisement/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'advertisement';
        } elseif (preg_match('/\/admin_api\/update_advertisement/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'advertisement';
        } elseif (preg_match('/\/admin_api\/show_advertisement/', $url)) {
            $entityId = $id;
            return 'advertisement';
        }

        elseif (preg_match('/\/admin_api\/add_news/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'news';
        } elseif (preg_match('/\/admin_api\/update_news/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'news';
        } elseif (preg_match('/\/admin_api\/show_news_by_id/', $url)) {
            $entityId = $id;
            return 'news';
        }

        elseif (preg_match('/\/admin_api\/add_table/', $url)) {
            $entityId = $id;
            $action = 'create';
            return 'table';
        } elseif (preg_match('/\/admin_api\/update_table/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'table';
        } elseif (preg_match('/\/admin_api\/show_table/', $url)) {
            $entityId = $id;
            return 'table';
        }
        elseif (preg_match('/\/admin_api\/update_super_admin_restaurant_id/', $url)) {
            $entityId = $id;
            $action = 'updated';
            return 'admin restaurant id';
        }

        return 'error';
    }

    private function determineEntityTypeFromUrl($url, &$action)
    {
        if (preg_match('/\/superAdmin_api\/show_cities/', $url)) {
            return 'city';
        } elseif (preg_match('/\/superAdmin_api\/active_or_not_city/', $url)) {
            $action = 'deactive';
            return 'city';
        } elseif (preg_match('/\/superAdmin_api\/delete_city/', $url)) {
            return 'city';
        } elseif (preg_match('/\/superAdmin_api\/show_admins/', $url)) {
            return 'City Super Admins, Data Entries';
        } elseif (preg_match('/\/superAdmin_api\/active_admin/', $url)) {
            $action = 'deactive';
            return 'City Super Admins, Data Entries';
        } elseif (preg_match('/\/superAdmin_api\/delete_admin/', $url)) {
            return 'City Super Admins, Data Entries';
        } elseif (preg_match('/\/superAdmin_api\/show_emoji/', $url)) {
            return 'Emoji';
        } elseif (preg_match('/\/superAdmin_api\/deactivate_emoji/', $url)) {
            $action = 'deactive';
            return 'Emoji';
        } elseif (preg_match('/\/superAdmin_api\/delete_emoji/', $url)) {
            return 'Emoji';
        } elseif (preg_match('/\/superAdmin_api\/show_restaurants/', $url)) {
            return 'Restaurant';
        } elseif (preg_match('/\/superAdmin_api\/deactivate_restaurant/', $url)) {
            $action = 'deactive';
            return 'Restaurant';
        } elseif (preg_match('/\/superAdmin_api\/delete_restaurant/', $url)) {
            return 'Restaurant';
        } elseif (preg_match('/\/superAdmin_api\/restaurant_managers/', $url)) {
            return 'Restaurant Manager';
        } elseif (preg_match('/\/superAdmin_api\/active_restaurant_manager/', $url)) {
            $action = 'deactive';
            return 'Restaurant Manager';
        } elseif (preg_match('/\/superAdmin_api\/delete_restaurant_manager/', $url)) {
            return 'Restaurant Manager';
        } elseif (preg_match('/\/superAdmin_api\/show_menu_forms/', $url)) {
            return 'Menu Template';
        } elseif (preg_match('/\/superAdmin_api\/deactivate_menu_form/', $url)) {
            $action = 'deactive';
            return 'Menu Template';
        } elseif (preg_match('/\/superAdmin_api\/delete_menu_form/', $url)) {
            return 'Menu Template';
        } elseif (preg_match('/\/superAdmin_api\/show_rates/', $url)) {
            return 'Rate';
        } elseif (preg_match('/\/superAdmin_api\/excel/', $url)) {
            return 'excel';
        } elseif (preg_match('/\/superAdmin_api\/show_packages/', $url)) {
            return 'package';
        } elseif (preg_match('/\/superAdmin_api\/active_package/', $url)) {
            $action = 'deactive';
            return 'package';
        } elseif (preg_match('/\/superAdmin_api\/delete_package/', $url)) {
            return 'package';
        } elseif (preg_match('/\/superAdmin_api\/show_restaurant_subscription/', $url)) {
            return 'subscription';
        } elseif (preg_match('/\/superAdmin_api\/logs/', $url)) {
            return 'logs';
        }
        elseif (preg_match('/\/superAdmin_api\/update_super_admin_restaurant_id/', $url)) {
            $action = 'updated';
            return 'Super admin restaurant_id';
        }
        // --------------------------Admin------------------------
        elseif (preg_match('/\/admin_api\/show_admin_categories/', $url)) {
            return 'category';
        } elseif (preg_match('/\/admin_api\/deactivate_category/', $url)) {
            $action = 'deactive';
            return 'category';
        } elseif (preg_match('/\/admin_api\/reorder_categories/', $url)) {
            $action = 'reOrder';
            return 'category';
        } elseif (preg_match('/\/admin_api\/delete_category/', $url)) {
            return 'category';
        }

        elseif (preg_match('/\/admin_api\/show_items/', $url)) {
            return 'item';
        } elseif (preg_match('/\/admin_api\/deactivate_item/', $url)) {
            $action = 'deactive';
            return 'item';
        } elseif (preg_match('/\/admin_api\/reorder_items/', $url)) {
            $action = 'reOrder';
            return 'item';
        } elseif (preg_match('/\/admin_api\/delete_item/', $url)) {
            return 'item';
        }

        elseif (preg_match('/\/admin_api\/show_orders_invoice/', $url)) {
            return 'orders invoice';
        }
        elseif (preg_match('/\/admin_api\/show_orders/', $url)) {
            return 'order';
        } elseif (preg_match('/\/admin_api\/delete_order/', $url)) {
            return 'order';
        } elseif (preg_match('/\/admin_api\/show_invoices/', $url)) {
            return 'invoice';
        }

        elseif (preg_match('/\/admin_api\/show_users/', $url)) {
            return 'admin or employee or data entry';
        } elseif (preg_match('/\/admin_api\/active_user/', $url)) {
            $action = 'deactive';
            return 'admin or employee or data entry';
        } elseif (preg_match('/\/admin_api\/delete_user/', $url)) {
            return 'admin or employee or data entry';
        }

        elseif (preg_match('/\/admin_api\/show_advertisements/', $url)) {
            return 'advertisement';
        } elseif (preg_match('/\/admin_api\/delete_advertisement/', $url)) {
            return 'advertisement';
        }

        elseif (preg_match('/\/admin_api\/show_news/', $url)) {
            return 'news';
        } elseif (preg_match('/\/admin_api\/delete_news/', $url)) {
            return 'news';
        }

        elseif (preg_match('/\/admin_api\/show_rates/', $url)) {
            return 'rates';
        } elseif (preg_match('/\/admin_api\/excel/', $url)) {
            return 'excel';
        }

        elseif (preg_match('/\/admin_api\/show_notifications/', $url)) {
            return 'notifications';
        }

        elseif (preg_match('/\/admin_api\/show_tables/', $url)) {
            return 'table';
        } elseif (preg_match('/\/admin_api\/delete_table/', $url)) {
            return 'table';
        }
        elseif (preg_match('/\/admin_api\/show_restaurants/', $url)) {
            return 'Restaurant';
        }
        elseif (preg_match('/\/admin_api\/update_super_admin_restaurant_id/', $url)) {
            $action = 'updated';
            return 'admin restaurant id';
        }
        // -----------------permissions & roles--------------
        elseif (preg_match('/\/superAdmin_api\/roles/', $url)) {
            return 'role';
        } elseif (preg_match('/\/superAdmin_api\/permissions/', $url)) {
            return 'permissions';
        } elseif (preg_match('/\/admin_api\/roles/', $url)) {
            return 'role';
        }
        return 'error';
    }

    private function getNewData($entityType, $id)
    {
        switch ($entityType) {
            case 'city':
                return City::find($id)->toArray();
            case 'Emoji':
                return Emoji::find($id)->toArray();
            case 'Restaurant':
                return Restaurant::find($id)->toArray();
            case 'Super Admin':
                return SuperAdmin::find($id)->toArray();
            case 'Restaurant Manager':
                return Admin::find($id);
            case 'Menu Template':
                return MenuTemplate::find($id)->toArray();
            case 'package':
                return Package::find($id)->toArray();
            case 'subscription':
                return PackageRestaurant::find($id)->toArray();
            // -----------------Admin------------------

            case 'admin restaurant id':
                return Restaurant::find($id)->toArray();
            case 'admin or employee or data entry':
                return Admin::find($id);
            case 'category':
                return Category::find($id)->toArray();
            case 'item':
                return Item::find($id)->toArray();
            case 'order':
                return Order::find($id)->toArray();
            case 'invoice':
                return Invoice::find($id)->toArray();
            case 'orders invoice':
                return Invoice::with('orders')->find($id)->toArray();
            case 'advertisement':
                return Advertisement::find($id)->toArray();
            case 'news':
                return News::find($id)->toArray();
            case 'table':
                return Table::find($id)->toArray();
            // ---------------------------------------------
            case 'role':
                return Role::find($id)->toArray();
            case 'permissions':
                return Permission::find($id)->toArray();
            default:
                return null;
        }
    }
}
