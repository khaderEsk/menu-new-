<?php

namespace App\Services;

use App\Models\MenuTemplate;
use App\Models\Restaurant;

class MenuTemplateService
{
    // to show all menuForm active
    public function all()
    {
        $menuTemplates = MenuTemplate::orderBy('id')->get();
        return $menuTemplates;
    }

    // to show paginate City active
    public function paginate($num)
    {
        $menuTemplates = MenuTemplate::orderBy('id')->paginate($num);
        return $menuTemplates;
    }

    // to create menuForm
    public function create($id,$data)
    {
        $data['super_admin_id'] = $id;
        $menuTemplate = MenuTemplate::create($data);
        return $menuTemplate;
    }

    // to update menuForm
    // public function update($id,$data)
    // {
        // $menuForm = MenuForm::whereSuperAdminId($id)->whereId($data['id'])->update($data);
        // return $menuForm;
    // }

    // to show a menuForm
    public function show(string $id)
    {
        $menuTemplate = MenuTemplate::findOrFail($id);
        return $menuTemplate;
    }


    // to delete a menuForm
    public function destroy(string $id)
    {
        $cityRestaurant = count(Restaurant::where('menu_template_id',$id)->get());
        if($cityRestaurant != 0)
        {
            return -10;
        }
        return MenuTemplate::whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data,$admin)
    {
        if($data['is_active'] == 1)
        {
            $menuTemplate = MenuTemplate::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $menuTemplate = MenuTemplate::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $menuTemplate;
    }

    public function search($where,$num)
    {
        $menuTemplate=MenuTemplate::where($where)->orderBy('id')->paginate($num);
        return $menuTemplate;
    }
}
