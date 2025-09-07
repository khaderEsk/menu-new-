<?php

namespace App\Services;

use App\Models\DataEntry;
use App\Models\SuperAdmin;
use Spatie\Permission\Models\Role;

class DataEntryService
{
    // to show all dataEntry active
    public function all()
    {
        // $role = Role::where('name','dataEntry')->first();
        $dataEntries = SuperAdmin::role('dataEntry')->get();
        return $dataEntries;
    }

    // to show paginate dataEntry active
    public function paginate($num)
    {
        $dataEntries = SuperAdmin::role('dataEntry')->paginate($num);
        return $dataEntries;
    }

    // to create dataEntry
    public function create($data)
    {
        // $data['data_entryable_id'] = $id;
        // $data['data_entryable_type'] = 'App\Models\SuperAdmin';
        $dataEntry = SuperAdmin::create($data);
        $dataEntry->assignRole(['dataEntry']);
        return $dataEntry;
    }

    // to update dataEntry
    public function update($data)
    {
        $dataEntry = SuperAdmin::role('dataEntry')->whereId($data['id'])->update($data);
        return $dataEntry;
    }

    // to show a dataEntry
    public function show(string $id)
    {
        $dataEntry = SuperAdmin::role('dataEntry')->findOrFail($id);
        return $dataEntry;
    }

    // to delete a dataEntry
    public function destroy(string $id,$admin)
    {
        return SuperAdmin::role('dataEntry')->whereSuperAdminId($admin)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data)
    {
        if($data['is_active'] == 1)
        {
            $dataEntry = SuperAdmin::role('dataEntry')->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $dataEntry = SuperAdmin::role('dataEntry')->whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $dataEntry;
    }

    public function search($where,$num)
    {
        $dataEntries = SuperAdmin::role('dataEntry')->where($where)->paginate($num);
        return $dataEntries;
    }
}
