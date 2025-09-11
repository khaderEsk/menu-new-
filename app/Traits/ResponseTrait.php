<?php

namespace App\Traits;

trait ResponseTrait
{

    public function returnError($errNum, $msg)
    {
        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'message' => $msg
        ], intval($errNum));
    }
    public function returnData($value, $msg = "successfully")
    {
        return response()->json([
            'status' => true,
            'errNum' => "200",
            'message' => $msg,
            'data' => $value
        ], 200);
    }
    public function paginateSuccessResponse($data, $message, $code)
    {
        $meta = [
            'total' => $data->total(),
            'count' => $data->count(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
        ];
        return response()->json(['status' => true, 'data' => $data, 'meta' => $meta, 'message' => $message], $code);
    }

    public function paginateErrorResponse($data, $message, $code)
    {
        $meta = [
            'total' => $data->total(),
            'count' => $data->count(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
        ];
        return response()->json(['status' => false, 'data' => $data, 'meta' => $meta, 'message' => $message], $code);
    }

    public function successResponse($data, $message, $code)
    {
        $meta = [
            'total' => 0,
            'count' => 0,
            'per_page' => 0,
            'current_page' => 0,
            'total_pages' => 0,
        ];
        return response()->json(['status' => true, 'data' => $data, 'meta' => $meta, 'message' => $message], $code);
    }

    public function errorResponse($data, $message, $code)
    {
        return response()->json(['status' => false, 'data' => $data, 'message' => $message], $code);
    }

    public function messageSuccessResponse($message, $code)
    {
        return response()->json(['status' => true, 'message' => $message], $code);
    }

    public function messageErrorResponse($message, $code = 400)
    {
        return response()->json(['status' => false, 'message' => $message], $code);
    }
}
