<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Validator;
class M_CabangController extends Controller
{
    public function index(Request $request)
    {
        if($request->ajax()){
            $query = DB::table("m_cabang")->select('id',"nama","deskripsi")->orderBy("id","desc")->get();
            return  Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $url = route('cabang.edit')."?id=".$row->id;
                return '
                    <a title="Update Data" data-bs-toggle="tooltip" class="btn btn-sm btn-primary btn-edit" href="'.$url.'"><i class="fa fa-edit"></i></a>
                    <button title="Hapus Data" data-bs-toggle="tooltip" class="btn btn-sm btn-danger btn-delete" onclick="hapusData('.$row->id.')"><i class="fa fa-trash"></i></button>
                ';
            })
            ->rawColumns(['aksi'])
            
            ->make(true);
        }else{
            return view("page.m_cabang.index");
        }
    }

    public function cabang_select(Request $request)
    {
        $query = $request->get('q');
        $data = DB::table("m_cabang")->where('nama','like','%'.$query.'%')->orderBy('nama','asc')->get();
        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("page.m_cabang.tambah");
    }

   
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validates 	= [
            "nama_cabang"  => "required",
            
        ];
        $messages = [
            'nama_cabang.required'   => 'Nama Cabang wajib diisi.',
        ];

        $validation = Validator::make($request->all(), $validates,$messages);
        if($validation->fails()) {
            return response()->json([
                "status"    => "warning",
                "messages"   => $validation->errors()->first()
            ], 401);
        }
        DB::beginTransaction();
        try {
            $data['nama'] = $request->nama_cabang;
            $data['deskripsi'] = $request->deskripsi;
            $data['created_at'] = Carbon::now();
            $id = DB::table("m_cabang")->insert($data);
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
        $data = DB::table("m_cabang")->where("id",$id)->first();
        return view("page.m_cabang.edit",compact("data","id"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validates 	= [
            "nama_cabang"  => "required",
        ];
        $messages = [
            'nama_cabang.required'   => 'Nama Cabang wajib diisi.',
        ];
        $validation = Validator::make($request->all(), $validates,$messages);
        if($validation->fails()) {
            return response()->json([
                "status"    => "warning",
                "messages"   => $validation->errors()->first()
            ], 401);
        }
        DB::beginTransaction();
        try {
            $data['nama'] = $request->nama_cabang;
            $data['deskripsi'] = $request->deskripsi;
            $data['updated_at'] = Carbon::now();
            $id = DB::table("m_cabang")->where("id",$request->id)->update($data);
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
            DB::table("m_cabang")->where("id",$id)->delete();
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Data berhasil dihapus."], 200);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }
    }
}
