<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IoT extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('idlogs');
            $table->enum('ket', ['normal','sedang','tinggi']);
            $table->Integer('ketinggian');
            $table->date('tanggal');
            $table->String('jam');
            $table->timestamps();
        });

        Schema::create('data', function (Blueprint $table) {
            $table->bigIncrements('iddata');
            $table->enum("ket", ["normal", "sedang", "tinggi"]);
            $table->Integer("ketinggian");
            $table->timestamps();
        });

        DB::table("data")->insert([
            "ket" => "normal",
            "ketinggian" => 0,
        ]);

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->bigIncrements('idpengaturan');
            $table->integer("ketinggianMax");
            $table->integer("waspada");
            $table->integer("bahaya");
            $table->integer("menit");
            $table->timestamps();
        });

        DB::table("pengaturan")->insert([
            "ketinggianMax" => 4,
            "menit" => 1,
            "waspada" => 60,
            "bahaya" => 70,
        ]);

        Schema::create('alat', function (Blueprint $table) {
            $table->bigIncrements('idalat');
            $table->String('token_sensor')->unique();
            $table->timestamps();
        });

        DB::table("alat")->insert([
            "token_sensor" => uniqid()."_".strtotime(now()),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
