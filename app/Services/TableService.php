<?php

namespace App\Services;

use App\Models\Table;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class TableService
{
    // to show all Table active
    public function all($id)
    {
        $tables = Table::whereRestaurantId($id)->latest()->get();
        return $tables;
    }

    // to show paginate table active
    public function paginate($id, $num)
    {
        $tables = Table::whereRestaurantId($id)->orderByDESC('updated_at')->paginate($num);
        return $tables;
    }

    public function qr($table)
    {
        // // إنشاء QR code
        $appUrl = env('APP_URL');
        $restaurantUrlTakeOut = $appUrl . "/qr_table/" . $table->id;

        // توليد QR Code من النص
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($restaurantUrlTakeOut)
            ->size(200)
            ->margin(10)
            ->build();

        // تحديد مسار للحفظ في التخزين
        $qrCodePath = 'public/qr_table/' . $table->id . '.png';

        // حفظ الصورة في التخزين
        Storage::put($qrCodePath, $qrCode->getString());

        // تحديث مسار الصورة في قاعدة البيانات
        $table->qr_code = $qrCodePath;
        $table->save();
    }

    // to create table
    public function create($id, $data)
    {
        $data['restaurant_id'] = $id;
        $table = Table::create($data);
        // $tableData = $table->toJson();
        if ($table->is_qr_table == 1)
            $this->qr($table);
        return $table;
    }

    // to update table
    public function update($id, $data)
    {
        $data['restaurant_id'] = $id;
        $table = Table::whereRestaurantId($id)->whereId($data['id'])->update($data);
        $t = Table::whereId($data['id'])->first();
        if ($t->is_qr_table == 1 && $t->qr_code == null)
            $this->qr($t);
        return $table;
    }

    // to show a table
    public function show($id, $data)
    {
        $table = Table::whereRestaurantId($id)->findOrFail($data['id']);
        return $table;
    }

    // to delete a table
    public function destroy($id, $restaurant_id)
    {
        $table = Table::whereRestaurantId($restaurant_id)->whereId($id)->first();
        if ($table->qr_code) {
            Storage::delete($table->qr_code);
        }
        return $table->delete();
        // return $table->forceDelete();
    }

    public function search($data, $num)
    {
        $table = Table::whereTranslationLike('name', "%$data%")->latest()->paginate($num);
        return $table;
    }
}
