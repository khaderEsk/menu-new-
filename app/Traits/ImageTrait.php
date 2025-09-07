<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait ImageTrait
{
    public function uploadSingleImage(Model $model, string $nameRequest, string $nameCollection)
    {
        if (Request()->hasFile($nameRequest)) {
            $model->clearMediaCollection($nameCollection);
            $extension = Request()->file($nameRequest)->getClientOriginalExtension();
            $randomFileName = str()->random(10) . "-" . Date('Y-d-m')  . '.' . $extension;
            $model->addMediaFromRequest($nameRequest)->usingFileName($randomFileName)
                ->usingName($model->name)->toMediaCollection($nameCollection);
        }
    }
}
