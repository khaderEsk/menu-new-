<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\TableUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Table\AddRequest;
use App\Http\Requests\Table\IdRequest;
use App\Http\Requests\Table\ShowRequest;
use App\Http\Requests\Table\UpdateRequest;
use App\Http\Resources\InvoiceResources;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TableResource;
use App\Models\Admin;
use App\Models\EmployeeTable;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Services\FirebaseService;
use App\Services\InvoiceService;
use App\Services\TableService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class TableController extends Controller
{
    public function __construct(private TableService $tableService, private FirebaseService $firebaseService, private InvoiceService $invoiceService)
    {
    }

    // Show All Tables For Admin
    public function showAll(ShowRequest $request)
    {
        try{
            $admin = auth()->user();

            $tables= $this->tableService->paginate($admin->restaurant_id,$request->input('per_page', 50));
            if (\count($tables) == 0) {
                return $this->successResponse([],trans('locale.dontHaveTables'),200);
            }
            $data = TableResource::collection($tables);
            return $this->paginateSuccessResponse($data,trans('locale.tablesFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Table
    public function create(AddRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $table = $this->tableService->create($restaurant_id,$request->validated());
            $data = TableResource::make($table);
            // $tables = Table::whereRestaurantId($restaurant_id)->get();
            // $allTable = TableResource::collection($tables);
            $tables= Table::whereRestaurantId($restaurant_id)->paginate($request->input('per_page', 50));
            $allTable = TableResource::collection($tables);
            $t = $this->paginateSuccessResponse($allTable,trans('locale.created'),200);
            event(new TableUpdatedEvent($t->getData(true)));
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update Table
    public function update(UpdateRequest $request)
    {
        try{
            $admin = auth()->user();
            $item = $this->tableService->update($admin->restaurant_id,$request->validated());
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $showItem = $this->tableService->show($admin->restaurant_id,$request->validated());
            $data = TableResource::make($showItem);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show Table By Id
    public function showById(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $table = $this->tableService->show($admin->restaurant_id,$request->validated());
            $data = TableResource::make($table);
            return $this->successResponse($data,trans('locale.tableFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Table
    public function delete(IdRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $table = $this->tableService->destroy($request->validated(),$restaurant_id);
            if($table == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            // $tables = Table::whereRestaurantId($restaurant_id)->get();
            // $allTable = TableResource::collection($tables);
            // event(new TableUpdatedEvent($allTable));
            $tables= Table::whereRestaurantId($restaurant_id)->paginate($request->input('per_page', 50));
            $allTable = TableResource::collection($tables);
            $t = $this->paginateSuccessResponse($allTable,trans('locale.created'),200);
            event(new TableUpdatedEvent($t->getData(true)));
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update status table
    public function updateStatus(Request $request, $id)
    {
        try{
            $table = Table::findOrFail($id);
            if($table->restaurant->is_table == 0)
                return $this->messageErrorResponse(trans('locale.youCantOrder'),403);

            switch($request->type)
            {
                case $request->type == 'waiter':
                    $table->waiter = 1;
                    $title = 'waiter';
                    break;

                case $request->type == 'arakel':
                    $table->arakel = 1;
                    $title = 'arakel';
                    break;

                case $request->type == 'invoice':
                    $table->invoice = 1;
                    $title = 'invoice';
                    break;
            }
            $table->save();
            $body = "$table->number_table ";

            // $body = $table->id ;
            $data = ['title' => $title, 'number_table' => $table->number_table, 'table_id' => $table->id, 'restaurant' =>  $table->restaurant->name];
            if($request->type == 'waiter' || $request->type == 'invoice')
            {
                if($request->type == 'invoice')
                {
                    $inv = Invoice::whereTableId($table->id)->whereStatus(2)->whereDate('created_at',now())->latest()->first();
                    if($inv)
                    {
                        $orders = Order::where('invoice_id', $inv->id)->latest()->get();
                        if($orders)
                        {
                            foreach($orders as $order)
                            {
                                if($order->status != 'done')
                                {
                                    $table->invoice = 0;
                                    $table->save();
                                    return response()->json(['status' => false,'message' => trans('locale.youCantRequestInvoice')],400);

                                }
                            }
                        }
                        $sum = 0;
                        foreach($orders as $order)
                        {
                            $sum += $order['price'] * $order['count'];
                        }
                        $restaurant = Restaurant::whereId($table->restaurant_id)->first();
                        $consumer_spending = $sum * $restaurant->consumer_spending/100;
                        $local_administration = $sum * $restaurant->local_administration/100;
                        $reconstruction = $sum * $restaurant->reconstruction/100;
                        $total = $sum + $consumer_spending + $local_administration + $reconstruction;
                        $invoice_res = [
                            'price' => round($sum, 0),
                            'consumer_spending' => round($consumer_spending, 0),
                            'local_administration' => round($local_administration, 0),
                            'reconstruction' => round($reconstruction, 0),
                            'total' => round($total, 0),
                        ];

                        $inv->update([
                            'price' => round($sum, 0),
                            'consumer_spending' => round($consumer_spending, 0),
                            'local_administration' => round($local_administration, 0),
                            'reconstruction' => round($reconstruction, 0),
                            'total' => round($total, 0)
                        ]);

                        $order_res = OrderResource::collection($orders);
                        $invoice_res = InvoiceResources::make($inv);
                        //$data = [
                        //'orders' => $order_res,
                        //   'invoice' => $invoice_res,
                        // ];
                        $employee = Admin::role('employee')->whereRestaurantId($table->restaurant_id)->whereTypeId(5)->orWhere('type_id',3)->whereRestaurantId($table->restaurant_id)->get();
                        for($i=0;$i< count($employee);$i++)
                        {
                            $firstElement = $employee->get($i);
                            if ($firstElement->fcm_token )
                                $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data);
                        }
                        return $this->messageSuccessResponse(trans('locale.successfully'),200);
                    }
                    $orders = Order::whereRestaurantId($table->restaurant_id)->whereNull('invoice_id')->whereDate('created_at',now()->format('Y-m-d'))->whereTableId($table->id)->get();
                    if(count($orders) == 0)
                    {
                        $table->invoice = 0;
                        $table->save();
                        return response()->json(['status' => false,'message' => trans('locale.youCantRequestInvoice')],400);
                    }

                    if($orders)
                    {
                        foreach($orders as $order)
                        {
                            if($order->status != 'done')
                            {
                                $table->invoice = 0;
                                $table->save();
                                return response()->json(['status' => false,'message' => trans('locale.youCantRequestInvoice')],400);
                            }
                        }
                    }
                    $data2 = ['table_id' => $table->id, 'status' => 2];
                    // $data['customer_id'] = $user->id;
                    $invoice = $this->invoiceService->create($table->restaurant_id,$data2);
                    // $data = InvoiceResources::make($invoice);
                    foreach($orders as $order)
                    {
                        $order->update([
                            'invoice_id' => $invoice->id,
                        ]);
                    }

                    $sum = 0;
                    $orders = Order::where('invoice_id', $invoice->id)->latest()->get();
                    $order_res = OrderResource::collection($orders);
                    $restaurant = Restaurant::where('id', $table->restaurant_id)->first();
                    $consumer_spending = $restaurant['consumer_spending'];
                    $local_administration = $restaurant['local_administration'];
                    $reconstruction = $restaurant['reconstruction'];
                    $invoice = Invoice::where('id', $invoice->id)->first();
                    $invoice_res = InvoiceResources::make($invoice);
                    foreach($orders as $order)
                    {
                        $sum += $order['price'] * $order['count'];
                    }

                    $consumer_spending = $sum * $consumer_spending/100;
                    $local_administration = $sum * $local_administration/100;
                    $reconstruction = $sum * $reconstruction/100;
                    $total = $sum + $consumer_spending + $local_administration + $reconstruction;

                    $invoice->update([
                        'price' => round($sum, 0),
                        'consumer_spending' => round($consumer_spending, 0),
                        'local_administration' => round($local_administration, 0),
                        'reconstruction' => round($reconstruction, 0),
                        'total' => round($total, 0)
                    ]);
                }

                $employee = Admin::role('employee')->whereRestaurantId($table->restaurant_id)->whereTypeId(5)->get();
                for($i=0;$i< count($employee);$i++)
                {
                    $firstElement = $employee->get($i);
                    if ($firstElement->fcm_token )
                        $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data);
                }
            }
            else
            {
                $employee = Admin::role('employee')->whereRestaurantId($table->restaurant_id)->whereTypeId(6)->get();
                for($i=0;$i< count($employee);$i++)
                {
                    $firstElement = $employee->get($i);
                    if ($firstElement->fcm_token )
                        $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data);
                }
            }
            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        } catch(Throwable $th){
            //return $this->messageErrorResponse(trans('locale.errorFirebase'),400);
             $message = $th->getMessage();
             return $this->messageErrorResponse($message);
        }
    }

    public function accept(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'id' => ['required',Rule::exists('tables','id')->where('restaurant_id',$user->restaurant_id)],
            'type' =>['required','in:waiter,arakel,invoice'],
        ]);
        $type = $request->type;
        $table = Table::whereId($data['id'])->first();
        if($table->$type == 0)
            return $this->messageErrorResponse(trans('locale.youCantResposeThisTable'),400);
        if($type != $user->type->name)
        {
            if($user->type->name == 'waiter' && $request->type == 'arakel')
                return $this->messageErrorResponse(trans('locale.youCantResposeThisTable'),400);
            elseif($user->type->name == 'shisha' && $request->type == 'invoice' || $request->type == 'waiter')
                return $this->messageErrorResponse(trans('locale.youCantResposeThisTable'),400);
        }
        if($type == 'invoice')
        {
            $invoice = Invoice::whereNull('admin_id')->whereTableId($table->id)->orderByDESC('updated_at')->first();
            $invoice->admin_id = $user->id;
            $invoice->save();
        }

        $time1 = Carbon::now();
        $time2 = Carbon::parse($table->updated_at);

        // Calculate the difference
        $diff = $time1->diff($time2);

        // Extract and format hours, minutes, and seconds
        $formattedHours = str_pad($diff->h, 2, '0', STR_PAD_LEFT);
        $formattedMinutes = str_pad($diff->i, 2, '0', STR_PAD_LEFT);
        $formattedSeconds = str_pad($diff->s, 2, '0', STR_PAD_LEFT);
        $totalMinutes = $formattedHours.":".$formattedMinutes.":".$formattedSeconds;
        $table->timestamps = false;
        $table->save();
        $t = $user->type->name;
        if($user->hasRole('employee') && $t == 'arakel')
        {
            EmployeeTable::create([
                'order_time' => $totalMinutes,
                'table_id' => $table->id,
                'admin_id' => $user->id,
                'restaurant_id' => $user->restaurant_id,
            ]);
            $table->update([
                'arakel' => 0,
            ]);

            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        }
        elseif($user->hasRole('employee') && $t == 'waiter')
        {
            EmployeeTable::create([
                'order_time' => $totalMinutes,
                'table_id' => $table->id,
                'admin_id' => $user->id,
                'restaurant_id' => $user->restaurant_id,
            ]);
            switch($request->type)
            {
                case $request->type == 'waiter':
                    $table->waiter = 0;
                    $title = 'waiter';
                    break;

                case $request->type == 'invoice':
                    $table->invoice = 0;
                    $table->waiter = 0;
                    $title = 'invoice';
                    break;
            }
            $table->timestamps = true;
            $table->save();
            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        }
        return $this->messageErrorResponse(trans('locale.youCantResposeThisTable'),400);

    }
}
