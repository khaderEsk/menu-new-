<?php

namespace App\Services;

use App\Models\SuperAdmin;

class CitySuperAdminService
{
    // to show all City active
    public function all()
    {
        $citySuperAdmins = SuperAdmin::role('citySuperAdmin')->get();
        return $citySuperAdmins;
    }

    // to show paginate City active
    public function paginate($num)
    {
        $citySuperAdmins = SuperAdmin::role('citySuperAdmin')->with('city')->paginate($num);
        return $citySuperAdmins;
    }

    // to create city
    public function create($data)
    {
        $citySuperAdmin = SuperAdmin::create($data);
        $citySuperAdmin->assignRole(['citySuperAdmin']);
        return $citySuperAdmin;
    }

    // to update  city
    public function update($data)
    {
        $citySuperAdmin = SuperAdmin::whereId($data['id'])->update($data);
        return $citySuperAdmin;
    }

    // to show a city
    public function show(string $id)
    {
        $citySuperAdmin = SuperAdmin::role('citySuperAdmin')->findOrFail($id);
        return $citySuperAdmin;
    }

    // to delete a city
    public function destroy(string $id,$admin)
    {
        return SuperAdmin::whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data,$admin)
    {
        if($data['is_active'] == 1)
        {
            $citySuperAdmin = SuperAdmin::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $citySuperAdmin = SuperAdmin::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $citySuperAdmin;
    }

    public function search($where,$num)
    {
        $citySuperAdmin=SuperAdmin::role('citySuperAdmin')->where($where)->paginate($num);
        return $citySuperAdmin;
    }
}
