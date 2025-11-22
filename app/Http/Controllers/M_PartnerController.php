<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Validator;
class M_PartnerController extends Controller
{
    public function index(Request $request)
    {
        if($request->ajax()){
            $query = DB::table("m_partner")
                ->join("m_entitas","m_entitas.id","m_partner.entitas_id")
                ->select('m_partner.id',"m_partner.nama","m_partner.is_vendor","m_partner.is_customer","m_partner.alamat","m_partner.no_telpon","m_entitas.nama as entitas")
                ->orderBy("id","desc")
                ->get();
            return  Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $url = route('partner.edit')."?id=".$row->id;
                return '
                    <a title="Update Data" data-bs-toggle="tooltip" class="btn btn-sm btn-primary btn-edit" href="'.$url.'"><i class="fa fa-edit"></i></a>
                    <button title="Hapus Data" data-bs-toggle="tooltip" class="btn btn-sm btn-danger btn-delete" onclick="hapusData('.$row->id.')"><i class="fa fa-trash"></i></button>
                ';
            })
            ->rawColumns(['aksi'])
            
            ->make(true);
        }else{
            return view("page.m_partner.index");
        }
    }

    public function partner_select(Request $request)
    {
        $query = $request->get('q');
        $entitas_id = $request->get('entitas_id');
        $jenis = $request->get('jenis'); // ✅ ambil dari request (opsional)

        $m_partner = DB::table('m_partner')
            // Filter jenis partner jika dikirim (vendor / customer)
            ->when($jenis, function ($q) use ($jenis) {
                if ($jenis === 'vendor') {
                    $q->where('is_vendor', 'active');
                } elseif ($jenis === 'customer') {
                    $q->where('is_customer', 'active');
                }
            })

            // Filter berdasarkan entitas (selalu aktif)
            ->when($entitas_id, function ($q) use ($entitas_id) {
                $q->where('entitas_id', $entitas_id);
            })

            // Filter berdasarkan teks pencarian
            ->when($query, function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            })

            ->select('id', 'nama', 'entitas_id')
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json($m_partner);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("page.m_partner.tambah");
    }

   
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validates 	= [
            "nama_partner"  => "required",
            "entitas"  => "required",
            
        ];
        
        
        $validation = Validator::make($request->all(), $validates);
        if($validation->fails()) {
            return response()->json([
                "status"    => "warning",
                "messages"   => $validation->errors()->first()
            ], 401);
        }

        if((!$request->has('vendor') && !$request->has('customer'))){
            return response()->json([
                "status"    => "warning",
                "messages"   => "Data Vendor atau Customer belum dipilih"
            ], 401);
        }
        
        DB::beginTransaction();
        try {
            $data['nama'] = $request->nama_partner;
            $data['alamat'] = $request->alamat;
            $data['entitas_id'] = $request->entitas;
            $data['no_telpon'] = $request->no_telepon;
            $data['is_vendor'] = $request->has('vendor') ? $request->vendor : "inactive";
            $data['is_customer'] = $request->has('customer') ? $request->customer : "inactive";
            $data['created_at'] = Carbon::now();
            $id = DB::table("m_partner")->insert($data);
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
        $data = DB::table("m_partner")->where("id",$id)->first();
        $entitas_id = DB::table('m_entitas')->where("id",$data->entitas_id)->first();
        return view("page.m_partner.edit",compact("data","id","entitas_id"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validates 	= [
            "nama_partner"  => "required",
            "entitas"  => "required",
        ];
        $validation = Validator::make($request->all(), $validates);
        if($validation->fails()) {
            return response()->json([
                "status"    => "warning",
                "messages"   => $validation->errors()->first()
            ], 401);
        }

         if((!$request->has('vendor') && !$request->has('customer'))){
            return response()->json([
                "status"    => "warning",
                "messages"   => "Data Vendor atau Customer belum dipilih"
            ], 401);
        }

        DB::beginTransaction();
        try {
            $data['nama'] = $request->nama_partner;
            $data['alamat'] = $request->alamat;
            $data['entitas_id'] = $request->entitas;
            $data['no_telpon'] = $request->no_telepon;
            $data['is_vendor'] = $request->has('vendor') ? $request->vendor : "inactive";
            $data['is_customer'] = $request->has('customer') ? $request->customer : "inactive";
            $data['updated_at'] = Carbon::now();
            $id = DB::table("m_partner")->where("id",$request->id)->update($data);
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
            DB::table("m_partner")->where("id",$id)->delete();
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Data berhasil dihapus."], 200);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }
    }
}
