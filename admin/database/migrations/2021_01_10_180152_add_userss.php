<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserss extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->insert(array(
            'name' =>'supAdmin',
            'email' => 'supAdmin@gmail.com',
            'user_type' => 'admin',
            'password' => Hash::make('admin@123'),
            'created_at' =>date('Y-m-d H:m:s'),
            'updated_at' =>date('Y-m-d H:m:s')

        ));
        DB::table('users')->insert(array(
            'name' =>'supUsers',
            'email' => 'supUsers@gmail.com',
            'user_type' => 'user',
            'password' => Hash::make('users@123'),
            'created_at' =>date('Y-m-d H:m:s'),
            'updated_at' =>date('Y-m-d H:m:s')

        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB:: table('users')->where('name','=','supAdmin')->delete();
        DB:: table('users')->where('name','=','supUsers')->delete();
    }
}
