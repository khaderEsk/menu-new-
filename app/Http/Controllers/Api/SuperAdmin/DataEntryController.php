<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataEntry\AddRequest;
use App\Http\Requests\DataEntry\IdRequest;
use App\Http\Requests\DataEntry\ShowRequest;
use App\Http\Requests\DataEntry\UpdateRequest;
use App\Http\Resources\DataEntryResource;
use App\Services\DataEntryService;

class DataEntryController extends Controller
{
    public function __construct(private DataEntryService $dataEntryService)
    {
    }

    // Show All Data Entry
    public function showAll(ShowRequest $request){

        $data =  $this->dataEntryService->all();
        if (\count($data) == 0) {
            return $this->successResponse([],[],"Dont Have Data Entry",200);
        }

        $data = $request->validated();
        $where = [];

        // Filter By Search
        if(\array_key_exists('search',$data))
        {
            $where = \array_merge($where,[['name','like','%'.$data['search'].'%']]);
            $data_entries =  $this->dataEntryService->search($where,$request->input('per_page', 25));
            $data = DataEntryResource::collection($data_entries);
            $meta = [
                'total' => $data->total(),
                'count' => $data->count(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'total_pages' => $data->lastPage(),
            ];
            return $this->successResponse($data,$meta,"data Entries Found Successfully",200);
        }

        $data_entries = $this->dataEntryService->paginate($request->input('per_page', 25));
        $data = DataEntryResource::collection($data_entries);
        $meta = [
            'total' => $data->total(),
            'count' => $data->count(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
        ];
        return $this->successResponse($data,$meta,"data Entries Found Successfully",200);
    }

    // Add Data Entry User
    public function create(AddRequest $request)
    {
        // $id = auth()->user()->id;
        $this->dataEntryService->create($request->validated());
        return $this->messageSuccessResponse("Data entry Created Successfully",200);
    }

    // update Data Entry User
    public function update(UpdateRequest $request)
    {
        $id = auth()->user()->id;
        $item = $this->dataEntryService->update($request->validated());
        if($item == 0)
        {
            return $this->messageErrorResponse("Invalid Item",403);
        }
        return $this->messageSuccessResponse("Data entry Updated Successfully",200);

    }

    // Active Or DisActive Data Entry User
    public function deactivate(IdRequest $request)
    {
        $dataEntry = $this->dataEntryService->show($request->id);
        $item = $this->dataEntryService->activeOrDesactive($dataEntry);
        if($item == 0)
        {
            return $this->messageErrorResponse("Invalid Item",403);
        }
        return $this->messageSuccessResponse("Successfully",200);
    }

    // Delete Data Entry User
    public function delete(IdRequest $request){

        $admin = auth()->user()->id;
        $restaurant = $this->dataEntryService->destroy($request->id,$admin);
        if($restaurant == 0)
        {
            return $this->messageErrorResponse("Invalid Item",403);
        }
        return $this->messageSuccessResponse("Data Entry Deleted Successfully",200);
    }
}
