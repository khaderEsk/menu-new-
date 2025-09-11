<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesInventoryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $orders;
    public function __construct($orders)
    {
        $this->orders = $orders;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->orders;
    }

    // إعداد العناوين في ملف Excel
    public function headings(): array
    {
        return [
            'الاسم',
            // 'النوع',
            'السعر',
            'العدد',
            'التاريخ',
            // 'الحالة',
        ];
    }

    // تحديد كيفية تحويل كل صف من البيانات
    public function map($order): array
    {
        return [
            $order['name'] ?? null,
            // $order['type_en'] ?? null,
            $order['price'] ?? null,
            $order['count'] ?? null,
            $order['created_at'] ?? null,
            // $order['status'] ?? null,
        ];
    }
}
