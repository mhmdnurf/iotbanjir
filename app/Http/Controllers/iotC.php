<?php

namespace App\Http\Controllers;

use App\Models\dataM;
use App\Models\logsM;
use App\Models\emailM;
use App\Models\pengaturanM;
use App\Models\alatM;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SampleMail; // Ganti dengan nama kelas mail Anda
use Illuminate\Support\Facades\Validator;
use Hash;

class iotC extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {

        
        // try {


            $token_sensor = $request->header('token-sensor');
            
            $cek = alatM::where('token_sensor', $token_sensor)->count();
            
            if($cek === 0 ){
                return abort(500, 'Kunci tidak valid');
            }
            
            $jsonData = $request->getContent();
            $json = json_decode($jsonData, true);

            $tinggi = $json["tinggi"];
            $tanggal = date("Y-m-d",$json["waktu"]);
            $jam = date("H:i",$json["waktu"]);


            $pengaturan = pengaturanM::first();
            $waspada = floor($pengaturan->ketinggianMax * ($pengaturan->waspada / 100));
            $bahaya = ceil($pengaturan->ketinggianMax * ($pengaturan->bahaya / 100));
            $ket = "normal";
            $led = 1;
            $buzzer = 0;


            if($tinggi >= $bahaya) {
                $ket = "tinggi";
                $led = 3;
                $buzzer = 1;

                $cekEmail = emailM::orderBy("idemail", "desc");

                if($cekEmail->count() == 0 || $cekEmail->first()->ket == "normal") {
                    emailM::create([
                        "tinggi" => $tinggi."Cm",
                        "ket" => $ket,
                        "tanggal" => date("Y-m-d"),
                    ]);

                    $user = User::select("email")->get();
                    $recipients = $user->pluck('email')->toArray();
                    $mailData = [
                        'title' => 'Ketinggian Air (BAHAYA)',
                        'content' => 'Ketinggian :'. $tinggi. "Cm",
                    ];
                    Mail::to($recipients)->send(new SampleMail($mailData));
                }
                
                



            }else if($tinggi > $waspada) {
                $ket = "sedang";
                $led = 2;
                $buzzer = 2;


                $cekEmail = emailM::orderBy("idemail", "desc");

                if($cekEmail->count() == 0 || $cekEmail->first()->ket == "normal") {
                    emailM::create([
                        "tinggi" => $tinggi."Cm",
                        "ket" => $ket,
                        "tanggal" => date("Y-m-d"),
                    ]);
                    $user = User::select("email")->get();
                    $recipients = $user->pluck('email')->toArray();
                    $mailData = [
                        'title' => 'Ketinggian Air (WASPADA)',
                        'content' => 'Ketinggian :'. $tinggi. "Cm",
                    ];
                    
                    Mail::to($recipients)->send(new SampleMail($mailData));
                }

            }else {
                $cekEmail = emailM::orderBy("idemail", "desc");
                if($cekEmail->count() > 0) {
                    $cekEmail = $cekEmail->first();
                    if($cekEmail->ket == "tinggi" || $cekEmail->ket == "sedang") {
                        emailM::create([
                            "tinggi" => $tinggi."Cm",
                            "ket" => "normal",
                            "tanggal" => date("Y-m-d"),
                        ]);
                    }
                }
            }
            
            $update = dataM::first();
            $update->update([
                'ket' => $ket,
                'ketinggian' => $tinggi,
            ]);

            $logs = logsM::orderBy('idlogs', 'desc')->first();
            if($logs == null) {
                $tambah = new logsM;
                $tambah->ket = $ket;
                $tambah->ketinggian = $tinggi;
                $tambah->tanggal = $tanggal;
                $tambah->jam = $jam;
                $tambah->save();
            }else {
                $tanggalKirim = strtotime(date("Y-m-d H:i", $json["waktu"])); 
                $tanggalLogs = strtotime(date("Y-m-d H:i", strtotime($logs->tanggal." ".$logs->jam)));

                if($tanggalKirim > $tanggalLogs) {
                    $tambah = new logsM;
                    $tambah->ket = $ket;
                    $tambah->ketinggian = $tinggi;
                    $tambah->tanggal = $tanggal;
                    $tambah->jam = $jam;
                    $tambah->save();
                }
            }

            $pesan = [
                'led' => $led,
                'buzzer' => $buzzer,
            ];

            return $pesan;

        // } catch (\Throwable $th) {
        //     return abort(500, 'Kunci tidak valid');
        // }
        

    }


    public function login(Request $request)
    { 
        try {
            $username = $request->username;
            $password = $request->password;

            $jumlahpassword = strlen($password);
            if($jumlahpassword<8){
                $pesan = [
                    "pesan" => "Minimal password 8 karakter",
                    "login" => 0,
                ];
                return $pesan;
            }

            $proses = User::where("username", $username);

            $pesan = [
                "pesan" => "Username dan Passord ",
                "login" => 0,
            ];

            if($proses->count() === 1) {
                if(Hash::check($password, $proses->first()->password)){
                    $data = $proses->first();
                    $alat = alatM::first();
                    $token_sensor = $alat->token_sensor;

                    $pesan = [
                        "pesan" => "Selamat datang",
                        "login" => 1,
                        "id" => $data->id,
			"posisi" => $data->posisi,
                        "name" => $data->name,
                        "token_sensor" => $token_sensor,
                        "email" => $data->email,
                    ];
                }
            }

            return $pesan;
            
        } catch (\Throwable $th) {
            $pesan = [
                "pesan" => "Error form Catch",
                "login" => 0,
            ];
            return $pesan;
        }
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:7',
                'username' => 'required|unique:users,username',
            ]);
        
            if ($validator->fails()) {
                $jumlahpassword = strlen($request->password);
                if($jumlahpassword<8){
                    $pesan = [
                        "pesan" => "Minimal password 8 karakter",
                        "daftar" => 0,
                    ];
                    return $pesan;
                }

                $pesan = [
                    "pesan" => "Silahkan memasukan identitas dengan benar, pastikan email dan username belum terdaftar",
                    "daftar" => 0,
                ];
                return $pesan;
            }

            $data = $request->all();
            $data["password"] = Hash::make($request->password);
            $data["posisi"] = "pengunjung";
            $proses = User::create($data);

            $pesan = [
                "pesan" => "Pendaftaran Berhasil Silahkan Login",
                "daftar" => 1,
            ];
            return $pesan;
            
        } catch (\Throwable $th) {
            $pesan = [
                "pesan" => "Pendaftaran Gagal, Terjadi kesalahan",
                "daftar" => 0,
            ];
            return $pesan;
        }
    }


    public function androidData(Request $request, $token_sensor)
    {
            
        $cek = alatM::where('token_sensor', $token_sensor)->count();
        
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }

        $data = dataM::orderBy("iddata", "desc")->select("ket", "ketinggian")->first();


        return $data;

    }


    public function androidLogs(Request $request, $token_sensor)
    {
            
        $cek = alatM::where('token_sensor', $token_sensor)->count();
        
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }

        $data = logsM::orderBy("idlogs", "desc")->select("ket", "ketinggian", "tanggal", "jam")->take(20)->get();


        return $data;

    }

    public function androidPengaturan(Request $request, $token_sensor)
    {
            
        $cek = alatM::where('token_sensor', $token_sensor)->count();
        
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }

        $data = pengaturanM::first();


        return $data;

    }

    public function androidPengaturanPost(Request $request, $token_sensor)
    {
            
        $cek = alatM::where('token_sensor', $token_sensor)->count();
        
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }

        $validator = Validator::make($request->all(), [
            'ketinggianMax' => 'required',
            'waspada' => 'required',
            'bahaya' => 'required',
            'menit' => 'required',
        ]);
    
        if ($validator->fails()) {
            $pesan = [
                "pesan" => "Terjadi kesalahan, inputan tidak valid",
            ];
            return $pesan;
        }
        
        $postData = $request->all();
        $data = pengaturanM::first();
        $data->update($postData);

        $pesan = [
            "pesan" => "Update Berhasil",
        ];

        return $pesan;
    }
    
    
    public function data()
    {
        $data = dataM::orderBy('iddata', 'desc')->first();
        $pengaturan = pengaturanM::first();
        
        $data = [
            "tinggi" => empty($data->ketinggian)?0:$data->ketinggian,
            "tinggiMax" => $pengaturan->ketinggianMax,
        ];

        return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function show(banjirM $banjirM)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function edit(banjirM $banjirM)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, banjirM $banjirM)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function destroy(banjirM $banjirM)
    {
        //
    }
}
