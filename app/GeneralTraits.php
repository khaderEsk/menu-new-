<?php

namespace App;

trait GeneralTrait
{
    
    public function returnError($errNum, $msg)
    {
        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'message' => $msg
        ],intval($errNum));
    }


    public function returnSuccessMessage($msg = "", $errNum = "200")
    {
        return response()->json([
            'status' => true,
            'errNum' => $errNum,
            'message' => $msg
        ],intval($errNum));
    }

    public function returnData($value, $msg = "successfully")
    {
        return response()->json([
            'status' => true,
            'errNum' => "200",
            'message' => $msg,
            'data' => $value
        ],200);
    }


    public function returnValidationError($code = "400", $validator)
    {
        return $this->returnError($code, $validator->errors());
    }



    function saveAnyFile($file, $folder)
    {
        try {
            $file_extension = $file->getClientOriginalExtension();
            $file_name = time() . rand() . '.' . $file_extension;
            $file->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in file save ");
        }
    }

    function saveImage($photo, $folder)
    {
        try {
            $file_extension = $photo->getClientOriginalExtension();
            $file_name = time() . rand() . '.' . $file_extension;
            $photo->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in image save ");
        }
    }

    function saveImageByName($photo, $folder,$name)
    {
        try {
            $file_extension = $photo->getClientOriginalExtension();
            $file_name = $name. '.' . $file_extension;
            $photo->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in image save ");
        }
    }
    // public function deleteImage($photo)
    // {

    //     try {
    //         if (\File::exists(public_path($photo))) {
    //             unlink($photo);
    //         }
    //     } catch (\Exception $ex) {
    //         //throw new HttpResponseException($this->returnError('500', "This image Not found"));
    //         return null;
    //     }
    // }


}
