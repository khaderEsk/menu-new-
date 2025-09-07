<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class TableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //$orders = Order::where('id', $this->id)->whereIn('status',['waiting','accepted','preparation'])->get();
       //dd($orders);
        $beforeAccepted = Order::whereTableId($this->id)->where(function($query) {
            $query->whereDate('created_at', now()->toDateString())
            ->orWhereDate('created_at', Carbon::yesterday()->toDateString());
        })->whereIn('status',['waiting','accepted'])->count();

        $preparation = Order::whereTableId($this->id)->where(function($query) {
            $query->whereDate('created_at', now()->toDateString())
            ->orWhereDate('created_at', Carbon::yesterday()->toDateString())
            ->orWhereDate('created_at', Carbon::tomorrow()->toDateString());
        })->whereIn('status',['preparation'])->count();

        $done = Order::whereDoesntHave('invoice')->whereTableId($this->id)->where(function($query) {
            $query->whereDate('created_at', now()->toDateString())
            ->orWhereDate('created_at', Carbon::yesterday()->toDateString())
            ->orWhereDate('created_at', Carbon::tomorrow()->toDateString());
        })->where('status','done')->count();

        // if($beforeAccepted > 0)
        //     $new = 1;
        // elseif($preparation > 0 && $beforeAccepted == 0)
        //     $new = 2;
        // elseif($preparation == 0 && $beforeAccepted == 0)
        //     $new = 0;

        // if($done > 0 && $beforeAccepted == 0 && $preparation == 0)
        //     $new = 3;

        if ($done > 0 && $beforeAccepted == 0 && $preparation == 0)
            $new = 3;
        elseif ($beforeAccepted > 0 && $preparation == 0)
            $new = 1;
        elseif ($preparation > 0)
            $new = 2;
        elseif ($preparation == 0 && $beforeAccepted == 0)
            $new = 0;

        $data = [
            'id' => $this->id,
            'number_table' => $this->number_table,
            'num' => $this->num,
            'waiter' => $this->waiter,
            'arakel' => $this->arakel,
            'invoice' => $this->invoice,
            'new_order' => $this->new_order,
            'is_qr_table' => $this->is_qr_table,
            'new' => $new,
            'restaurant_id' => $this->restaurant_id,

            // 'qr_code' => env('APP_URL')."/".$this->qr_code,
            // 'qr_code' => env('APP_URL')."/".str_replace('public', 'storage', $this->qr_code),
        ];
        //if($orders > 0)
          //  $data['order'] = $orders;

       // if($orders == 0)
           // $data['order'] = $orders;

        if($this->is_qr_table == 1)
            $data['qr_code'] = env('APP_URL')."/".str_replace('public', 'storage', $this->qr_code);
        return $data;
    }
}
