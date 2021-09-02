<?php

namespace App\Http\Controllers;
use DB;
class TestController extends Controller
{
	public function index()
	{
	$arrRecords=DB::table('users')
        ->select('name','email','user_type','created_at','updated_at','google_id')->get();

        return response()->json($arrRecords) ;

	}
}
