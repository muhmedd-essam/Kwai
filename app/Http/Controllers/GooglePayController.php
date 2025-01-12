<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GooglePayController extends Controller
{
  
  public function processPayment(Request $request)
  {
      // Handle the payment processing here
      // Use the $request->input() method to access the payment data sent from the frontend

      // After processing, send the response back to the frontend
      return response()->json(['status' => 'success']);
  }
}
