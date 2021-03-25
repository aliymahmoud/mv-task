<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function apiValidate(Request $request, $rules = [])
    {
        $validation = Validator::make($request->all(), $rules);
        if($validation->fails()){
            $response = response()->json([
                'message' => $validation->errors()->first(),
            ]);
            return $response;
        }
        else return false;
    }
}
