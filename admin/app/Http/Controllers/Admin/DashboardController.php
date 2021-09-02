<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use Auth;
use DB;
use Illuminate\Support\Facades\Session;

class DashboardController extends AppController
{
    public function index()
    {
        if (Auth::User()->user_type == "admin") {

            $arr = DB::table('users')
            ->select(DB::raw('count(*) as total_users')) ->get();
            // dd($arr);
                // ->select('id')->get();
            $this->viewVars['arr'] = $arr;
            $this->viewVars['arrCount'] = count($arr);
            // dd($this->viewVars['arrCount']);
            return view('admin.home', $this->viewVars);

        } else {

            Auth::logout();
            Session::flush();
            return redirect('/');
        }

    }
}
