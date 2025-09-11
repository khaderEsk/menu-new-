<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\City;
use App\Models\Emoji;
use App\Models\Item;
use App\Models\MenuTemplate;
use App\Models\News;
use App\Models\Order;
use App\Models\Package;
use App\Models\PackageRestaurant;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use App\Models\Table;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogUserActionsOld
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

    //     if ($request->isMethod('post')) {
    //         // return $id = $request->route('id'); // تأكد من استخدام المسار الصحيح للـ ID
    //         $id = $request->input('id'); // احصل على الـ ID من بيانات الطلب
    //         if ($id) {
    //             $request->attributes->set('oldData', City::find($id)->toArray());
    //     }
    // }

    if ($request->isMethod('post')) {
        $id = $request->input('id'); // احصل على الـ ID من بيانات الطلب

        if ($id) {
            $originalData = null;
            $url = $request->fullUrl();

            if (preg_match('/\/superAdmin_api\/update_city/', $url)) {
                $originalData = City::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/update_emoji/', $url)) {
                $originalData = Emoji::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/update_restaurant/', $url)) {
                $originalData = Restaurant::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/update_admin/', $url)) {
                $originalData = SuperAdmin::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/update_super_admin_restaurant_id/', $url)) {
                $originalData = SuperAdmin::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/restaurant_manager/', $url)) {
                $originalData = SuperAdmin::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/add_package/', $url)) {
                $originalData = Package::find($id)->toArray();
            } elseif (preg_match('/\/superAdmin_api\/add_subscription/', $url)) {
                $originalData = PackageRestaurant::find($id)->toArray();
            }
            // -----------------Admin--------------
            elseif (preg_match('/\/admin_api\/update_category/', $url)) {
                $originalData = Category::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_item/', $url)) {
                $originalData = Item::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_admin/', $url)) {
                $originalData = Admin::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_restaurant_admin/', $url)) {
                $originalData = Restaurant::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_order/', $url)) {
                $originalData = Order::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_user/', $url)) {
                $originalData = Admin::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_advertisement/', $url)) {
                $originalData = Advertisement::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_news/', $url)) {
                $originalData = News::find($id)->toArray();
            } elseif (preg_match('/\/admin_api\/update_table/', $url)) {
                $originalData = Table::find($id)->toArray();
            }

            if ($originalData) {
                $request->attributes->set('oldData', $originalData);
            }
        }
    }



        // $newData = $request->all();
        $response = $next($request);

        if (Auth::check()) {
            $newData = false;
            $user = auth()->user();

            $method = $request->method();
            $url = $request->fullUrl();
            $action = '';
            $entityType = '';
            $entityId = null;
            if ($response->getStatusCode() >= 400) {
                $errorData = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'status_code' => $response->getStatusCode(),
                    'error' => $response->getContent(),
                    'url' => $url,
                ];
                return $response;
            }
            $originalData = $request->attributes->get('oldData', false);
            switch ($method) {
                case 'POST':
                    $action = 'created or updated';
                    break;
                case 'PUT':
                case 'PATCH':
                    $action = 'updated';
                    break;
                case 'DELETE':
                    $action = 'deleted';
                    break;
                case 'GET':
                    $action = 'read';
                    break;
                default:
                    $action = 'accessed';
            }

            $responseData = json_decode($response->getContent(), true);
            if (isset($responseData['data']['id']))
            {
                $id = $responseData['data']['id'];
                $entityId = $id;

                if (preg_match('/\/superAdmin_api\/add_city/', $url, $matches)) {
                    $newData = City::find($entityId)->toArray();
                    $entityType = 'city';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/update_city/', $url, $matches)) {
                    $newData = City::find($entityId)->toArray();
                    $entityType = 'city';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/superAdmin_api\/show_city_by_id/', $url, $matches)) {
                    $entityType = 'city';
                    $entityId = $id;
                }

                elseif (preg_match('/\/superAdmin_api\/add_admin/', $url, $matches)) {
                    $newData = SuperAdmin::find($entityId)->toArray();
                    $entityType = 'City Super Admins ,Data Entries';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/update_admin/', $url, $matches)) {
                    $newData = SuperAdmin::find($entityId)->toArray();
                    $entityType = 'City Super Admins ,Data Entries';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/superAdmin_api\/show_admin/', $url, $matches)) {
                    $entityType = 'City Super Admins ,Data Entries';
                    $entityId = $id;
                }

                elseif (preg_match('/\/superAdmin_api\/add_emoji/', $url, $matches)) {
                    $newData = Emoji::find($entityId)->toArray();
                    $entityType = 'Emoji';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/update_emoji/', $url, $matches)) {
                    $newData = Emoji::find($entityId)->toArray();
                    $entityType = 'Emoji';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/superAdmin_api\/show_emoji_by_id/', $url, $matches)) {
                    $entityType = 'Emoji';
                    $entityId = $id;
                }

                elseif (preg_match('/\/superAdmin_api\/add_restaurant/', $url, $matches)) {
                    $newData = Restaurant::find($entityId)->toArray();
                    $entityType = 'Restaurant';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/update_restaurant/', $url, $matches)) {
                    $newData = Restaurant::find($entityId)->toArray();
                    $entityType = 'Restaurant';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/superAdmin_api\/show_restaurant/', $url, $matches)) {
                    $entityType = 'Restaurant';
                    $entityId = $id;
                }

                elseif (preg_match('/\/superAdmin_api\/update_super_admin_restaurant_id/', $url, $matches)) {
                    $newData = SuperAdmin::find($entityId)->toArray();
                    $entityType = 'Super Admin';
                    $entityId = $id;
                    $action = 'updated';
                }

                elseif (preg_match('/\/superAdmin_api\/restaurant_manager/', $url, $matches)) {
                    $newData = Admin::find($entityId)->toArray();
                    $entityType = 'Restaurant Manager';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/update_restaurant_manager/', $url, $matches)) {
                    $newData = Admin::find($entityId)->toArray();
                    $entityType = 'Restaurant Manager';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/superAdmin_api\/show_restaurant_manager/', $url, $matches)) {
                    $entityType = 'Restaurant Manager';
                    $entityId = $id;
                }

                elseif (preg_match('/\/superAdmin_api\/add_menu_form/', $url, $matches)) {
                    $newData = MenuTemplate::find($entityId)->toArray();
                    $entityType = 'Menu Template';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/show_menu_form_by_id/', $url, $matches)) {
                    $entityType = 'Menu Template';
                    $entityId = $id;
                }

                elseif (preg_match('/\/superAdmin_api\/add_package/', $url, $matches)) {
                    $newData = Package::find($entityId)->toArray();
                    $entityType = 'package';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/superAdmin_api\/update_package/', $url, $matches)) {
                    $newData = Package::find($entityId)->toArray();
                    $entityType = 'package';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/superAdmin_api\/show_package/', $url, $matches)) {
                    $entityType = 'package';
                    $entityId = $id;
                }
                elseif (preg_match('/\/superAdmin_api\/add_subscription/', $url, $matches)) {
                    $newData = PackageRestaurant::find($entityId)->toArray();
                    $entityType = 'subscription';
                    $entityId = $id;
                    $action = 'create';
                }

                elseif (preg_match('/\/superAdmin_api\/update_super_admin/', $url, $matches)) {
                    $newData = SuperAdmin::find($entityId)->toArray();
                    $entityType = 'Super Admin';
                    $entityId = $id;
                    $action = 'updated';
                }
                // --------------------------Admin------------------------
                elseif (preg_match('/\/admin_api\/add_category/', $url, $matches)) {
                    $newData = Category::find($entityId)->toArray();
                    $entityType = 'category';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_category/', $url, $matches)) {
                    $newData = Category::find($entityId)->toArray();
                    $entityType = 'category';
                    $entityId = $id;
                    $action = 'updated';
                }

                elseif (preg_match('/\/admin_api\/add_item/', $url, $matches)) {
                    $newData = Item::find($entityId)->toArray();
                    $entityType = 'item';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_item/', $url, $matches)) {
                    $newData = Item::find($entityId)->toArray();
                    $entityType = 'item';
                    $entityId = $id;
                    $action = 'updated';
                }

                elseif (preg_match('/\/admin_api\/update_admin/', $url, $matches)) {
                    $newData = Admin::find($entityId)->toArray();
                    $entityType = 'admin';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/update_restaurant_admin/', $url, $matches)) {
                    $newData = Restaurant::find($entityId)->toArray();
                    $entityType = 'restaurant';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/show_admin/', $url, $matches)) {
                    $entityType = 'item';
                    $entityId = $id;
                }

                elseif (preg_match('/\/admin_api\/add_order/', $url, $matches)) {
                    $newData = Order::find($entityId)->toArray();
                    $entityType = 'order';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_order/', $url, $matches)) {
                    $newData = Order::find($entityId)->toArray();
                    $entityType = 'order';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/show_order/', $url, $matches)) {
                    $entityType = 'order';
                    $entityId = $id;
                }

                elseif (preg_match('/\/admin_api\/add_user/', $url, $matches)) {
                    $newData = Admin::find($entityId)->toArray();
                    $entityType = 'admin or employee or data entry';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_user/', $url, $matches)) {
                    $newData = Admin::find($entityId)->toArray();
                    $entityType = 'admin or employee or data entry';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/show_user/', $url, $matches)) {
                    $entityType = 'admin or employee or data entry';
                    $entityId = $id;
                }

                elseif (preg_match('/\/admin_api\/add_advertisement/', $url, $matches)) {
                    $newData = Advertisement::find($entityId)->toArray();
                    $entityType = 'advertisement';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_advertisement/', $url, $matches)) {
                    $newData = Advertisement::find($entityId)->toArray();
                    $entityType = 'advertisement';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/show_advertisement/', $url, $matches)) {
                    $entityType = 'advertisement';
                    $entityId = $id;
                }

                elseif (preg_match('/\/admin_api\/add_news/', $url, $matches)) {
                    $newData = News::find($entityId)->toArray();
                    $entityType = 'news';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_news/', $url, $matches)) {
                    $newData = News::find($entityId)->toArray();
                    $entityType = 'news';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/show_news_by_id/', $url, $matches)) {
                    $entityType = 'news';
                    $entityId = $id;
                }

                elseif (preg_match('/\/admin_api\/add_table/', $url, $matches)) {
                    $newData = Table::find($entityId)->toArray();
                    $entityType = 'table';
                    $entityId = $id;
                    $action = 'create';
                } elseif (preg_match('/\/admin_api\/update_table/', $url, $matches)) {
                    $newData = Table::find($entityId)->toArray();
                    $entityType = 'table';
                    $entityId = $id;
                    $action = 'updated';
                } elseif (preg_match('/\/admin_api\/show_table/', $url, $matches)) {
                    $entityType = 'table';
                    $entityId = $id;
                }
            }

            else
            {
                if (preg_match('/\/superAdmin_api\/show_cities/', $url, $matches)) {
                    $entityType = 'city';
                } elseif (preg_match('/\/superAdmin_api\/active_or_not_city/', $url, $matches)) {
                    $entityType = 'city';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_city/', $url, $matches)) {
                    $entityType = 'city';
                } elseif (preg_match('/\/superAdmin_api\/add_city/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/update_city/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_city_by_id/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/show_admins/', $url, $matches)) {
                    $entityType = 'City Super Admins ,Data Entries';
                }  elseif (preg_match('/\/superAdmin_api\/active_admin/', $url, $matches)) {
                    $entityType = 'City Super Admins or Data Entries';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_admin/', $url, $matches)) {
                    $entityType = 'City Super Admins ,Data Entries';
                } elseif (preg_match('/\/superAdmin_api\/add_admin/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/update_admin/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_admin/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/show_emoji/', $url, $matches)) {
                    $entityType = 'Emoji';
                }  elseif (preg_match('/\/superAdmin_api\/deactivate_emoji/', $url, $matches)) {
                    $entityType = 'Emoji';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_emoji/', $url, $matches)) {
                    $entityType = 'Emoji';
                } elseif (preg_match('/\/superAdmin_api\/add_emoji/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/update_emoji/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_emoji_by_id/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/show_restaurants/', $url, $matches)) {
                    $entityType = 'Restaurant';
                }  elseif (preg_match('/\/superAdmin_api\/deactivate_restaurant/', $url, $matches)) {
                    $entityType = 'Restaurant';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_restaurant/', $url, $matches)) {
                    $entityType = 'Restaurant';
                } elseif (preg_match('/\/superAdmin_api\/add_restaurant/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/update_restaurant/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_restaurant/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/update_super_admin_restaurant_id/', $url, $matches)) {
                    $action = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/restaurant_managers/', $url, $matches)) {
                    $entityType = 'Restaurant Manager';
                }  elseif (preg_match('/\/superAdmin_api\/active_restaurant_manager/', $url, $matches)) {
                    $entityType = 'Restaurant Manager';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_restaurant_manager/', $url, $matches)) {
                    $entityType = 'Restaurant Manager';
                } elseif (preg_match('/\/superAdmin_api\/restaurant_manager/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/update_restaurant_manager/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_restaurant_manager/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/show_menu_forms/', $url, $matches)) {
                    $entityType = 'Menu Template';
                }  elseif (preg_match('/\/superAdmin_api\/deactivate_menu_form/', $url, $matches)) {
                    $entityType = 'Menu Template';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_menu_form/', $url, $matches)) {
                    $entityType = 'Menu Template';
                } elseif (preg_match('/\/superAdmin_api\/add_menu_form/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_menu_form_by_id/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/show_rates/', $url, $matches)) {
                    $entityType = 'Rate';
                }  elseif (preg_match('/\/superAdmin_api\/excel/', $url, $matches)) {
                    $entityType = 'excel';
                }

                elseif (preg_match('/\/superAdmin_api\/show_packages/', $url, $matches)) {
                    $entityType = 'package';
                } elseif (preg_match('/\/superAdmin_api\/active_package/', $url, $matches)) {
                    $entityType = 'package';
                    $action = 'deactive';
                } elseif (preg_match('/\/superAdmin_api\/delete_package/', $url, $matches)) {
                    $entityType = 'package';
                } elseif (preg_match('/\/superAdmin_api\/add_package/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/update_package/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/superAdmin_api\/show_package/', $url, $matches)) {
                    $entityType = 'error';
                }
                elseif (preg_match('/\/superAdmin_api\/show_restaurant_subscription/', $url, $matches)) {
                    $entityType = 'subscription';
                } elseif (preg_match('/\/superAdmin_api\/add_subscription/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/update_super_admin/', $url, $matches)) {
                    $action = 'error';
                }

                elseif (preg_match('/\/superAdmin_api\/logs/', $url, $matches)) {
                    $entityType = 'logs';
                }
                // --------------------------Admin------------------------
                elseif (preg_match('/\/admin_api\/show_admin_categories/', $url, $matches)) {
                    $entityType = 'category';
                } elseif (preg_match('/\/admin_api\/deactivate_category/', $url, $matches)) {
                    $entityType = 'category';
                    $action = 'deactive';
                } elseif (preg_match('/\/admin_api\/reorder_categories/', $url, $matches)) {
                    $entityType = 'category';
                    $action = 'reOrder';
                } elseif (preg_match('/\/admin_api\/delete_category/', $url, $matches)) {
                    $entityType = 'category';
                } elseif (preg_match('/\/admin_api\/add_category/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_category/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/show_items/', $url, $matches)) {
                    $entityType = 'item';
                } elseif (preg_match('/\/admin_api\/deactivate_item/', $url, $matches)) {
                    $entityType = 'item';
                    $action = 'deactive';
                } elseif (preg_match('/\/admin_api\/reorder_items/', $url, $matches)) {
                    $entityType = 'item';
                    $action = 'reOrder';
                } elseif (preg_match('/\/admin_api\/delete_item/', $url, $matches)) {
                    $entityType = 'item';
                } elseif (preg_match('/\/admin_api\/add_item/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_item/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/update_admin/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_restaurant_admin/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/show_admin/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/show_orders/', $url, $matches)) {
                    $entityType = 'order';
                } elseif (preg_match('/\/admin_api\/delete_order/', $url, $matches)) {
                    $entityType = 'order';
                } elseif (preg_match('/\/admin_api\/add_order/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_order/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/show_order/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/show_users/', $url, $matches)) {
                    $entityType = 'admin or employee or data entry';
                } elseif (preg_match('/\/admin_api\/active_user/', $url, $matches)) {
                    $entityType = 'admin or employee or data entry';
                    $action = 'deactive';
                } elseif (preg_match('/\/admin_api\/delete_user/', $url, $matches)) {
                    $entityType = 'admin or employee or data entry';
                } elseif (preg_match('/\/admin_api\/add_user/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_user/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/show_user/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/show_advertisements/', $url, $matches)) {
                    $entityType = 'advertisement';
                } elseif (preg_match('/\/admin_api\/delete_advertisement/', $url, $matches)) {
                    $entityType = 'advertisement';
                } elseif (preg_match('/\/admin_api\/add_advertisement/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_advertisement/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/show_advertisement/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/show_news/', $url, $matches)) {
                    $entityType = 'news';
                } elseif (preg_match('/\/admin_api\/delete_news/', $url, $matches)) {
                    $entityType = 'news';
                } elseif (preg_match('/\/admin_api\/add_news/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_news/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/show_news_by_id/', $url, $matches)) {
                    $entityType = 'error';
                }

                elseif (preg_match('/\/admin_api\/show_rates/', $url, $matches)) {
                    $entityType = 'rates';
                } elseif (preg_match('/\/admin_api\/excel/', $url, $matches)) {
                    $entityType = 'excel';
                }

                elseif (preg_match('/\/admin_api\/show_notifications/', $url, $matches)) {
                    $entityType = 'notifications';
                }

                elseif (preg_match('/\/admin_api\/show_tables/', $url, $matches)) {
                    $entityType = 'table';
                } elseif (preg_match('/\/admin_api\/delete_table/', $url, $matches)) {
                    $entityType = 'table';
                } elseif (preg_match('/\/admin_api\/add_table/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/update_table/', $url, $matches)) {
                    $entityType = 'error';
                } elseif (preg_match('/\/admin_api\/show_table/', $url, $matches)) {
                    $entityType = 'error';
                }
            }



            if ($user) {

                if ($newData) {
                    $user->activityLogs()->create([
                        'action' => $action,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'description' => $url,
                        'original_data' => json_encode($originalData),
                        'new_data'=> json_encode($newData),
                    ]);
                }
                else
                {
                    $user->activityLogs()->create([
                        'action' => $action,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'description' => $url,
                    ]);
                }

            }
        }

        return $response;
    }
}
