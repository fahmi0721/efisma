<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Validator;
class UsersController extends Controller
{
    public function index(Request $request)
    {
        if($request->ajax()){
            $query = DB::table('users')
                ->leftJoin('m_entitas', 'm_entitas.id', '=', 'users.entitas_id')
                ->select(
                    'users.id',
                    'users.nama',
                    'users.username',
                    'users.email',
                    'users.jabatan',
                    'users.level',
                    'm_entitas.nama as entitas'
                )
                ->orderBy('users.id', 'desc')
                ->get();
            return  Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $url = route('users.edit')."?id=".$row->id;
                return '<div class="btn-group btn-group-sm">
                    <button title="Assign to Role" data-bs-toggle="tooltip" class="btn btn-warning btn-sm btn-assign" data-id="'.$row->id.'">
                        <i class="fa fa-user-shield"></i>
                    </button>
                    <a title="Update Data" data-bs-toggle="tooltip" class="btn btn-sm btn-primary btn-edit" href="'.$url.'"><i class="fa fa-edit"></i></a>
                    <button title="Hapus Data" data-bs-toggle="tooltip" class="btn btn-sm btn-danger btn-delete" onclick="hapusData('.$row->id.')"><i class="fa fa-trash"></i></button>
                    </div>
                    ';
            })
            
            ->rawColumns(['aksi'])
            
            ->make(true);
        }else{
            return view("page.users.index");
        }
    }

    public function getRole(Request $request)
    {
        $user_id = $request->user_id;

        $user = DB::table('users')->where('id', $user_id)->first();
        if (!$user) return response()->json(['status'=>'error', 'message'=>'User tidak ditemukan'], 404);

        $roles = DB::table('roles')->get();

        $userRoles = DB::table('user_roles')
            ->where('user_id', $user_id)
            ->pluck('role_id')
            ->toArray();

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles
        ]);
    }

    public function saveRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'roles'   => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            // hapus role lama
            DB::table('user_roles')->where('user_id', $request->user_id)->delete();

            // insert role baru
            if ($request->roles) {
                foreach ($request->roles as $role_id) {
                    DB::table('user_roles')->insert([
                        'user_id' => $request->user_id,
                        'role_id' => $role_id
                    ]);
                }
            }

            DB::commit();

            return response()->json(['status'=>'success']);

        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error('User Role Error', ['msg'=>$e->getMessage()]);

            return response()->json([
                'status'=>'error',
                'message'=>'Internal server error'
            ], 500);
        }
    }


    


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("page.users.tambah");
    }

   
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // VALIDASI + CUSTOM MESSAGE
        $request->validate([
            'nama'       => 'required|max:150',
            'username'   => 'required|max:100|unique:users,username',
            'email'      => 'nullable|email|max:150|unique:users,email',
            'jabatan'    => 'nullable|max:150',
            'level'      => 'required|in:admin,pusat,entitas',
            'entitas_id' => 'required_if:level,entitas|nullable|integer',

            'password'   => 'required|min:6|confirmed',
        ], [
            // NAMA
            'nama.required'   => 'Nama user wajib diisi.',
            'nama.max'        => 'Nama user maksimal 150 karakter.',

            // USERNAME
            'username.required' => 'Username wajib diisi.',
            'username.max'      => 'Username maksimal 100 karakter.',
            'username.unique'   => 'Username sudah terdaftar, gunakan username lain.',

            // EMAIL
            'email.email'       => 'Format email tidak valid.',
            'email.max'         => 'Email maksimal 150 karakter.',
            'email.unique'      => 'Email sudah terdaftar, gunakan email lain.',

            // JABATAN
            'jabatan.max'       => 'Jabatan maksimal 150 karakter.',

            // LEVEL
            'level.required'    => 'Level user wajib dipilih.',
            'level.in'          => 'Level user tidak valid.',

            // ENTITAS
            'entitas_id.required_if' => 'Entitas wajib dipilih jika level adalah entitas.',

            // PASSWORD
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 6 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',

        ]);

        DB::beginTransaction();

        try {

            DB::table('users')->insert([
                'nama'       => $request->nama,
                'username'   => $request->username,
                'email'      => $request->email,
                'jabatan'    => $request->jabatan,
                'level'      => $request->level,
                'entitas_id' => $request->level == 'entitas' ? $request->entitas_id : null,
                'password'   => Hash::make($request->password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'messages' => 'User berhasil ditambahkan.'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error("User Store Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'status'   => 'error',
                'messages' => 'Internal server error'
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $id = $request->id;
        $data = DB::table("users")->where("id",$id)->first();
        $entitas_id = DB::table("m_entitas")->where("id",$data->entitas_id)->first();
        return view("page.users.edit",compact("data","id","entitas_id"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
         $userId = $request->id;
        // VALIDASI + CUSTOM MESSAGE
        $request->validate([
            'nama'       => 'required|max:150',
            'username'   => 'required|max:100|unique:users,username,' . $userId,
            'email'      => 'nullable|email|max:150|unique:users,email,' . $userId,
            'jabatan'    => 'nullable|max:150',
            'level'      => 'required|in:admin,pusat,entitas',
            'entitas_id' => 'required_if:level,entitas|nullable|integer',
        ], [
            // NAMA
            'nama.required'   => 'Nama user wajib diisi.',
            'nama.max'        => 'Nama user maksimal 150 karakter.',

            // USERNAME
            'username.required' => 'Username wajib diisi.',
            'username.max'      => 'Username maksimal 100 karakter.',
            'username.unique'   => 'Username sudah terdaftar, gunakan username lain.',

            // EMAIL
            'email.email'       => 'Format email tidak valid.',
            'email.max'         => 'Email maksimal 150 karakter.',
            'email.unique'      => 'Email sudah terdaftar, gunakan email lain.',

            // JABATAN
            'jabatan.max'       => 'Jabatan maksimal 150 karakter.',

            // LEVEL
            'level.required'    => 'Level user wajib dipilih.',
            'level.in'          => 'Level user tidak valid.',

            // ENTITAS
            'entitas_id.required_if' => 'Entitas wajib dipilih jika level adalah entitas.',


        ]);

        // Jika checkbox change password dicentang → tambah validasi password
    if ($request->change) {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);
    }
        DB::beginTransaction();
        try {
            $data = [
                'nama'       => $request->nama,
                'username'   => $request->username,
                'email'      => $request->email,
                'jabatan'    => $request->jabatan,
                'level'      => $request->level,
                'entitas_id' => $request->level == 'entitas' ? $request->entitas_id : null,
                'updated_at' => now(),
            ];

            // Jika user ingin mengubah password
            if ($request->change) {
                $data['password'] = Hash::make($request->password);
            }

            DB::table('users')->where('id', $userId)->update($data);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'messages' => 'User berhasil diperbarui.'
            ], 200);
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
            DB::table("users")->where("id",$id)->delete();
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Users berhasil dihapus."], 200);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }
    }
}
