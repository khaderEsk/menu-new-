<?php

namespace App\Exports;

use App\Models\Rate;
use App\Models\Restaurant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RateExport implements FromQuery, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $admin = auth()->user();
        if($admin->hasAnyRole(['admin','employee','restaurantManager']))
            $query = Rate::query()->with('customer')->where('restaurant_id', $admin->restaurant_id);
        else
            $query = Rate::query()->with('customer');

        if ($this->request->has('gender')) {
            $query->whereHas('customer', function ($q) {
                $q->where('gender', $this->request->gender);
            });
        }


        if ($this->request->has('type') )
        {
            if ($this->request->type === 'person') {
                $query->whereHas('customer', function ($q) {
                    $q->where('name','!=', null);
                });
            }
            else{
                $query->whereHas('customer', function ($q) {
                    $q->where('name','=', null);
                });
            }
        }

        if ($this->request->has('from_age')) {
            $query->whereHas('customer', function ($q) {
                $q->where('birthday', '>=', $this->request->from_age)->where('birthday', '<=', $this->request->to_age);
            });
        }

        if ($this->request->has('from_date') || $this->request->has('to_date')) {
            if($this->request->has('from_date') && $this->request->has('to_date'))
            {
                $query->whereHas('customer', function ($q) {
                    $q->where('created_at', '>=', $this->request->from_date)->where('created_at', '<=', $this->request->to_date);
                });
            }
            else if ($this->request->has('from_date'))
            {
            $query->whereHas('customer', function ($q) {
                    $q->where('created_at', '>=', $this->request->from_date);
                });
            }
            else if ($this->request->has('to_date'))
            {
            $query->whereHas('customer', function ($q) {
                    $q->where('created_at', '<=', $this->request->to_date);
                });
            }
        }

        if ($this->request->has('rate')) {
            $query->where('rate', $this->request->rate);
        }

        if ($this->request->has('restaurant_id')) {
            $query->where('restaurant_id', $this->request->restaurant_id);
        }
        return $query;
    }

    public function headings(): array
    {
        $admin = auth()->user();
        $restaurant = Restaurant::where('id',$admin->restaurant_id)->first();
        if($restaurant->rate_format->value == 1)
        {
            return [
                // 'ID',
                'Rate',
                'Note',
                'Customer Name',
                'Restaurant Name',
                'Service',
                'Arakel',
                'Foods',
                'Drinks',
                'Sweets',
                'Games_room',
                // 'Customer ID',
                // 'Restaurant ID',
            ];
        }
        else
        {
            return [
                // 'ID',
                'Rate',
                'Note',
                // 'Customer ID',
                'Customer Name',
                // 'Restaurant ID',
                'Restaurant Name',
            ];
        }
    }

    public function map($review): array
    {
        $admin = auth()->user();
        $restaurant = Restaurant::where('id',$admin->restaurant_id)->first();
        if($restaurant->rate_format->value == 1)
        {
            return [
                // $review->id,
                $review->rate,
                $review->note,
                // $review->customer_id,
                $review->customer ? $review->customer->name : 'null',
                // $review->restaurant_id,
                $review->restaurant ? $review->restaurant->name : 'null',
                $review->service,
                $review->arakel,
                $review->foods,
                $review->drinks,
                $review->sweets,
                $review->games_room,
            ];
        }
        else
        {
            return [
                // $review->id,
                $review->rate,
                $review->note,
                // $review->customer_id,
                $review->customer ? $review->customer->name : 'null',
                // $review->restaurant_id,
                $review->restaurant ? $review->restaurant->name : 'null',
            ];
        }
    }
}
