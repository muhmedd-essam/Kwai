<?php

namespace App\Traits;

trait MobileTrait
{

  public function error($msg, $statusCode)
  {
    return response()->json([
      'success' => false,
      'error' => [
        'message' => $msg,
        'status_code' => $statusCode,
      ],
    ], $statusCode);
  }

  public function error500()
  {
    return response()->json([
      'success' => false,
      'error' => [
        'message' => 'something went wrong, Please try again later',
      ],
    ], 500);
  }

  public function success($data = '', $message = '')
  {
    return response()->json([
      'success' => true,
      'data' => $data,
      'message' => $message,
    ]);
  }

  public function successWithoutData($message = null)
  {
    return response()->json([
      'success' => true,
      'message' => $message,
    ]);
  }

  public function data($data, $message = null)
  {
    return response()->json([
      'success' => true,
      'data' => $data,
      'message' => $message,
    ]);
  }

  public function dataPaginated($data)
  {
    return response()->json($data);
  }

  public function validationError($code, $msg, $validator)
  {
    return response()->json([
      'success' => false,
      'message' => $msg,
      'errors' => $validator->errors(),
    ],
     $code);
  }
  
}
