<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection; // Import Collection
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceExport implements FromCollection, WithHeadings, WithMapping
{
    // The constructor now accepts a Collection of Eloquent models.
    public function __construct(protected Collection $invoices)
    {
    }

    /**
     * The collection method is now extremely simple.
     * It just returns the data it was given.
     */
    public function collection(): Collection
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'رقم الفاتورة',
            'تاريخ الفاتورة',
            'المبلغ الاجمالي',
            'رقم الطاولة',
            'اسم النادل',
            'حالة الفاتورة',
        ];
    }

    public function map($invoice): array
    {
        $total = ($invoice->total ?? 0) + ($invoice->delivery_price ?? 0) - ($invoice->discount ?? 0);
        $formattedTotal = number_format($total);

        return [
            $invoice->num,
            // Format the date for better readability in Excel
            $invoice->created_at->format('Y-m-d H:i:s'),
            $formattedTotal,
            // ✅ EFFICIENT: Access the eager-loaded table relationship
            $invoice->table->number_table ?? 'طلب خارجي',
            // ✅ EFFICIENT: Access the eager-loaded admin relationship
            $invoice->admin->name ?? null,
            // Assuming 'status' is an Eloquent Enum, this is efficient.
            $invoice->status->name ?? null,
        ];
    }
}
