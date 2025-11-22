<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use Image;
class PengaturanUmumController extends Controller
{
    protected  $path;
    
    public function __construct()
    {
        $this->path = public_path(config('custom.upload_images'));
    }
    public function index()
    {
        $base_sistem = DB::table('base_sistem')->first();
        $akun_lr_berjalan = DB::table('m_akun_gl')->where("id",$base_sistem->akun_lr_berjalan_id)->first();
        $akun_lr_tahun_lalu = DB::table('m_akun_gl')->where("id",$base_sistem->akun_lr_lalu_id)->first();
        return view('page.pengaturan',compact('base_sistem','akun_lr_berjalan','akun_lr_tahun_lalu'));
    }
    


     public function store(Request $request)
    {
        $validates  = [
            "nama_aplikasi"  => "required",
            "akun_gl_laba_rugi_berjalan"  => "required",
            "akun_gl_laba_rugi_lalu"  => "required",
        ];
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $validates += ["logo" => 'image|mimes:jpeg,png,jpg|max:2048'];
        }

        if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
            $validates += ["favicon" => 'image|mimes:jpeg,png,jpg|max:2048'];
        }
        $messages = [
            'akun_gl_laba_rugi_berjalan.required'   => 'Akun GL Laba Rugi Tahun Berjalan wajib diisi.',
            'akun_gl_laba_rugi_lalu.required' => 'Akun GL Laba Rugi Tahun Lalu wajib diisi.',
        ];
        $validation = Validator::make($request->all(), $validates,$messages);
        if($validation->fails()) {
            return response()->json([
                "status"    => "error",
                "messages"   => $validation->errors()->first()
            ], 401);
        }

        DB::beginTransaction();
        try {
            /** Create Objek Data */
            $data = [
                'nama_aplikasi' => $request->nama_aplikasi,
                'akun_lr_berjalan_id' => $request->akun_gl_laba_rugi_berjalan,
                'akun_lr_lalu_id' => $request->akun_gl_laba_rugi_lalu,
                'created_at'=> Carbon::now(),
            ];

            /** Upload File Logo */
            $logo = $request->file('logo');
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $logoname = uniqid() . '.' . $logo->getClientOriginalExtension();
                $logo->move($this->path,$logoname);
                $data += ['logo' => $logoname];
            }

            /** Upload File Favicon */
            $favicon = $request->file('favicon');
            if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
                $faviconname = uniqid() . '.' . $favicon->getClientOriginalExtension();
                $favicon->move($this->path,$faviconname);
                $data += ['favicon' => $faviconname];
            }
            DB::table('base_sistem')->update($data);
            DB::commit();
            return response()->json([
                'status'   => 'success',
                'messages' => 'Data berhasil tersimpan',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'   => 'error',
                'messages' => $e->getMessage()
            ], 500);
        }
        
    }
}
