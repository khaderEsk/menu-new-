<?php

namespace App\Http\Controllers;

use App\Http\Resources\LogsResource;
use App\Models\ActivityLog;
use App\Services\RecordCleanupService;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogController extends Controller
{
    public function __construct(private RecordCleanupService $recordCleanupService)
    {
    }

    public function index(Request $request)
    {
        try{
            $logs = ActivityLog::query()->latest();

            if ($request->has('entity_type')) {
                $logs->where('entity_type', $request->input('entity_type'));
            }

            if ($request->has('action')) {
                $logs->where('action', $request->input('action'));
            }

            $log = $logs->paginate($request->input('per_page', 25));

            $data = LogsResource::collection($log);
            return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

    }

    public function deleteOldRecords()
    {
        try{
            $this->recordCleanupService->deleteOldRecords('activity_logs', 'created_at');
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
