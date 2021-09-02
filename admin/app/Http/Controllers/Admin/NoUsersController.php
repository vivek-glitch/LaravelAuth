<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use Auth;
use DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class NoUsersController extends AppController
{
    public function index()
    {
        $this->viewVars['cont']='NoUsers';
        $isViewAll   = (request('hdn_IsViewAll') != '') ? request('hdn_IsViewAll') : 0;
        $arrRecords=DB::table('users')
        ->select('name','email','user_type','created_at','updated_at','google_id');

        if(request('search')){
            $txtuserName =(request('txtuserName')!=''?request('txtuserName'):'');
            $this->viewVars['txtuserName']=$txtuserName;

            $txtGoogleId =(request('txtGoogleId') > 0 ?request('txtGoogleId'): '');
            $this->viewVars['txtGoogleId']=$txtGoogleId;
            if(request('txtuserName') != '')
            {
                $arrRecords->where('name',request('txtuserName'));
            }
            if(request('txtGoogleId') > 0)
            {
                $arrRecords->where('google_id',request('txtGoogleId'));
            }
            $this->viewVars['openFlag']         = ($this->viewVars['txtuserName'] != '' || $this->viewVars['txtGoogleId'] > 0 ) ? 'S' : 'C';
        }
        if ($isViewAll > 0) {
            $arrRecords = $arrRecords->get();
        } else {
            $arrRecords = $arrRecords->paginate(10);
        }
        $this->viewVars['arrRecords']=$arrRecords;
        $this->viewVars['arrRecordCount']=count($arrRecords);
        $this->viewVars['isViewAll']       = $isViewAll;
        // dd($this->viewVars['openFlag']);
        return view('admin.payment',$this->viewVars);
    }
    public function view($strParam = '')
    {
        
        $arrParam = Crypt::decrypt($strParam);
        
        dd($arrParam);
    }
}