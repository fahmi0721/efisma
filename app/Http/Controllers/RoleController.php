<?php

namespace App\Http\Controllers;


use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Validator;
use Str;


class RoleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = DB::table('roles')
                ->select('id', 'name', 'description')
                ->orderBy("id", "desc")
                ->get();

            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $url = route('role.edit')."?id=".$row->id;
                    return '<div class="btn-group btn-group-sm">
                        <button title="Premissions" data-bs-toggle="tooltip" 
                            class="btn btn-sm btn-success btn-detail-role" 
                            data-id="'.$row->id.'">
                            <i class="fa fa-eye"></i>
                        </button>
                        <a title="Update Data" data-bs-toggle="tooltip" 
                            class="btn btn-sm btn-primary btn-edit" 
                            href="'.$url.'">
                            <i class="fa fa-edit"></i>
                        </a>
                        <button title="Hapus Data" data-bs-toggle="tooltip" 
                            class="btn btn-sm btn-danger btn-delete" 
                            onclick="hapusData('.$row->id.')">
                            <i class="fa fa-trash"></i>
                        </button></div>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);

        } else {
            return view('page.roles.index');
        }
    }

    public function select(Request $request)
    {
        $query = $request->get('q');
        $data = DB::table("roles")->where('name','like','%'.$query.'%')->orderBy('name','asc')->get();
       
        return response()->json($data);
    }


    public function create()
    {
        return view('page.roles.tambah');
    }

    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'nama'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',

            'permissions'        => 'required|array|min:1',
            'permissions.*'      => 'required|integer|exists:permissions,id',
        ], [
            'nama.required'      => 'Nama role wajib diisi.',
            'nama.max'           => 'Nama terlalu panjang, maksimal 100 karakter.',
            'description.max'    => 'Deskripsi terlalu panjang, maksimal 255 karakter.',
            'permissions.required' => 'Permission minimal 1 dipilih.',
            'permissions.*.required' => 'Permission tidak valid.',
        ]);

        DB::beginTransaction();

        try {

            // CEK DUPLIKAT PERMISSION PADA INPUT
            $permissionArray = $request->permissions;
            if (count($permissionArray) !== count(array_unique($permissionArray))) {
                return response()->json([
                    'status'   => 'error',
                    'messages' => 'Terdapat permission yang duplikat dalam input.'
                ], 422);
            }

            // INSERT ROLE
            $roleId = DB::table('roles')->insertGetId([
                'name'        => $request->nama,
                'description' => $request->description,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

            // INSERT ROLE PERMISSIONS
            foreach ($permissionArray as $permId) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'messages' => 'Role berhasil disimpan.'
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            // log error asli
            Log::error('Role Store Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'status'   => 'error',
                'messages' => 'Internal server error'
            ], 500);
        }
    }


    public function detail_permission(Request $request)
    {
        $role_id = $request->role_id;

        $permissions = DB::table('role_permissions as rp')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->join('modules as m', 'm.id', '=', 'p.module_id')
            ->where('rp.role_id', $role_id)
            ->select(
                'm.name as module_name',
                'p.name as permission_name',
                'p.slug as permission_slug'
            )
            ->orderBy('m.name')
            ->get();

        return datatables()->of($permissions)
            ->addIndexColumn()
            ->make(true);
    }



    public function edit(Request $request)
    {
        $id = $request->id;

        // Ambil role
        $data = DB::table('roles')->where('id', $id)->first();
        if (!$data) abort(404);

        // Ambil permission milik role
        $rolePermissions = DB::table('role_permissions')
            ->where('role_id', $id)
            ->pluck('permission_id')
            ->toArray();

        // Ambil info permission secara detail
        $permissions = DB::table('permissions as p')
            ->leftJoin('modules as m', 'm.id', '=', 'p.module_id')
            ->select('p.id', 'p.name', 'p.slug', 'm.name as module_name')
            ->whereIn('p.id', $rolePermissions)
            ->get();

        return view('page.roles.edit', compact('data', 'permissions', 'id'));
    }

    public function update(Request $request)
    {
        // Validasi
        $id = $request->id;
        $request->validate([
            'nama'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',

            'permissions'        => 'required|array|min:1',
            'permissions.*'      => 'required|integer|exists:permissions,id',
        ], [
            'nama.required'      => 'Nama role wajib diisi.',
            'nama.max'           => 'Nama terlalu panjang, maksimal 100 karakter.',
            'description.max'    => 'Deskripsi terlalu panjang, maksimal 255 karakter.',
            'permissions.required' => 'Permission minimal 1 dipilih.',
            'permissions.*.required' => 'Permission tidak valid.',
        ]);
        DB::beginTransaction();
        try {

            // UPDATE ROLE
            DB::table('roles')->where('id', $id)->update([
                'name'        => $request->nama,
                'description' => $request->description,
                'updated_at'  => now(),
            ]);

            // HAPUS permission lama
            DB::table('role_permissions')->where('role_id', $id)->delete();

            // INSERT permission baru
            foreach ($request->permissions as $permId) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $id,
                    'permission_id' => $permId,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'messages' => 'Role berhasil diupdate.'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Role Update Error', ['msg' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'messages' => 'Internal server error'
            ], 500);
        }
    }




    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {

            DB::table('modules')->where('id', $request->id)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'messages' => 'Module berhasil dihapus.'
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            // Log detail error (aman)
            Log::error('Module Delete Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'messages' => 'Internal server error'
            ], 500);
        }
    }

}
