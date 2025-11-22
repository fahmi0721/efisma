<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Validator;
class M_AkunGLController extends Controller
{
    public function index(Request $request)
    {
        if($request->ajax()){
            $query = DB::table("m_akun_gl")->select('id',"nama","tipe_akun","saldo_normal","no_akun","kategori")->orderBy("id","asc")->get();
            return  Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $url = route('m_akun.edit')."?id=".$row->id;
                return '
                    <a title="Update Data" data-bs-toggle="tooltip" class="btn btn-sm btn-primary btn-edit" href="'.$url.'"><i class="fa fa-edit"></i></a>
                    <button title="Hapus Data" data-bs-toggle="tooltip" class="btn btn-sm btn-danger btn-delete" onclick="hapusData('.$row->id.')"><i class="fa fa-trash"></i></button>
                ';
            })
            ->rawColumns(['aksi'])
            
            ->make(true);
        }else{
            return view("page.m_akun.index");
        }
    }

    public function map(Request $request)
    {
        if($request->ajax()){
            $query = DB::table("view_akun_hirarki")->get();
            return  Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    if($row->level == 1 || $row->level == 2){
                        $res = "<b>".$row->id." - ".$row->no_akun." - ".$row->nama."</b>";
                    }else{
                        $res = $row->id." - ".$row->no_akun." - ".$row->nama;
                    }
                    return $res ;
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }else{
            return view("page.m_akun.maping");
        }
    }

    public function transaksi(Request $request)
    {
        if($request->ajax()){
            $query = DB::table("view_akun_transaksi_only")->get();
            return  Datatables::of($query)->make(true);
        }else{
            return view("page.m_akun.transaksi");
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("page.m_akun.tambah");
    }


    public function search(Request $request)
    {
        $query = $request->get('q');
        $m_akun = DB::table("m_akun_gl")->where('nama','like','%'.$query.'%')->orWhere('no_akun','like','%'.$query.'%')->get();
        return response()->json($m_akun);
    }
   
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validates 	= [
            "nomor_akun"  => "required|string|max:9",
            "nama_akun"  => "required",
            "tipe_akun"  => "required",
            "kategori"  => "required",
            "saldo_normal"  => "required"
            
        ];
        
        
        $validation = Validator::make($request->all(), $validates);
        if($validation->fails()) {
            return response()->json([
                "status"    => "warning",
                "messages"   => $validation->errors()->first()
            ], 401);
        }

        
        DB::beginTransaction();
        try {
            $data['no_akun'] = $request->nomor_akun;
            $data['nama'] = $request->nama_akun;
            $data['tipe_akun'] = $request->tipe_akun;
            $data['kategori'] = $request->kategori;
            $data['saldo_normal'] = $request->saldo_normal;
            $data['parent_id'] = $request->parent_akun;
            $data['created_at'] = Carbon::now();
            $id = DB::table("m_akun_gl")->insert($data);
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Data berhasil disimpan."], 200);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }  
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $id = $request->id;
        $data = DB::table("m_akun_gl")->where("id",$id)->first();
        if(!empty($data->parent_id)){
            $selected = DB::table('m_akun_gl')->where("id",$data->parent_id)->first();
        }else{
            $selected = null;
        }
        return view("page.m_akun.edit",compact("data","id","selected"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
       $validates 	= [
            "nomor_akun"  => "required|string|max:9",
            "nama_akun"  => "required",
            "tipe_akun"  => "required",
            "kategori"  => "required",
            "saldo_normal"  => "required"
            
        ];
        
        $validation = Validator::make($request->all(), $validates);
        if($validation->fails()) {
            return response()->json([
                "status"    => "warning",
                "messages"   => $validation->errors()->first()
            ], 401);
        }

        DB::beginTransaction();
        try {
            $data['no_akun'] = $request->nomor_akun;
            $data['nama'] = $request->nama_akun;
            $data['tipe_akun'] = $request->tipe_akun;
            $data['kategori'] = $request->kategori;
            $data['saldo_normal'] = $request->saldo_normal;
            $data['parent_id'] = $request->parent_akun;
            $data['updated_at'] = Carbon::now();
            $id = DB::table("m_akun_gl")->where("id",$request->id)->update($data);
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Data berhasil disimpan."], 200);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }  
    }

    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        DB::beginTransaction();
        try {
            DB::table("m_akun_gl")->where("id",$id)->delete();
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Data berhasil dihapus."], 200);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }
    }
}
