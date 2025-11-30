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


class ModuleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = DB::table('modules')
                ->select('id', 'name', 'slug', 'group')
                ->orderBy("id", "desc")
                ->get();

            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    $url = route('module.edit')."?id=".$row->id;
                    return '<div class="btn-group btn-group-sm">
                        <button title="Premissions" data-bs-toggle="tooltip" 
                            class="btn btn-sm btn-success btn-detail-module" 
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
            return view('page.modules.index');
        }
    }

    public function select(Request $request)
    {
        $query = $request->get('q');
        $data = DB::table("modules")->where('name','like','%'.$query.'%')->orderBy('name','asc')->get();
       
        return response()->json($data);
    }


    public function create()
    {
        return view('page.modules.tambah');
    }

    public function store(Request $request)
    {
        // Validasi custom
        $request->validate([
            'nama'  => 'required|string|max:150',
            'slug'  => 'nullable|string|max:100',
            'group' => 'nullable|string|max:100',

            // VALIDASI PERMISSION ARRAY
            'permissions'                 => 'required|array|min:1',
            'permissions.*.name'          => 'required|string|max:100',
            'permissions.*.slug'          => 'nullable|string|max:150',
        ], [
            'nama.required'  => 'Nama module wajib diisi.',
            'nama.max'       => 'Nama terlalu panjang, maksimal 150 karakter.',
            'slug.max'       => 'Slug terlalu panjang, maksimal 100 karakter.',
            'group.max'      => 'Group terlalu panjang, maksimal 100 karakter.',

                // Permissions
            'permissions.required'        => 'Permission minimal 1 baris.',
            'permissions.*.name.required' => 'Nama permission wajib diisi.',
            'permissions.*.name.max'      => 'Nama permission terlalu panjang.',
            'permissions.*.slug.max'      => 'Slug permission terlalu panjang.',
        ]);

        DB::beginTransaction();

        try {

            $slug = $request->slug ?: Str::slug($request->name);
            
            // Cek slug unik
            if (Module::where('slug', $slug)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'messages' => 'Slug Module sudah digunakan, silakan gunakan slug lain.'
                ], 401);
            }

            // Cek slug permission duplikat dalam input
            $slugs = [];
            foreach ($request->permissions as $p) {
                $slug = $p['slug'] ?: Str::slug($p['name']);
                if (in_array($slug, $slugs)) {
                    return response()->json([
                        'status' => 'error',
                        'messages' => 'Terdapat slug permission yang duplikat: ' . $slug
                    ], 401);
                }
                $slugs[] = $slug;
            }

            foreach ($request->permissions as $p) {
                $permSlug = $p['slug'] ?: Str::slug($p['name']);

                if (DB::table('permissions')->where('slug', $permSlug)->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'messages' => 'Slug permission already exists: ' . $permSlug
                    ], 401);
                    
                }
            }

            // Data disiapkan
            $data = [
                'name'       => $request->nama,
                'slug'       => $request->slug,
                'group'      => $request->group,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            // Insert module (pakai query builder sesuai gaya kamu)
            $moduleId  = DB::table('modules')->insertGetId($data);
            // Save Permissions
            foreach ($request->permissions as $p) {
                DB::table('permissions')->insert([
                    'module_id'  => $moduleId,
                    'name'       => $p['name'],
                    'slug'       => $p['slug'] ?: Str::slug($p['name']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'messages' => 'Module berhasil disimpan.'
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'messages' => $e->errorInfo
            ], 500);
        }
    }

    public function detail_permission(Request $request)
    {
        $module_id = $request->id;

        // Ambil module
        $module = DB::table('modules')->where('id', $module_id)->first();

        if (!$module) {
            return response()->json([
                'status' => 'error',
                'message' => 'Module tidak ditemukan'
            ], 404);
        }

        // Ambil semua permissions berdasarkan module_id
        $permissions = DB::table('permissions')
            ->where('module_id', $module_id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'status'     => 'success',
            'data'     => $module,
            'permissions'=> $permissions
        ], 200);
    }



    public function edit(Request $request)
    {
        $id = $request->id;
        // Ambil module
        $data = DB::table('modules')->where('id', $id)->first();
        if (!$data) abort(404, 'Module tidak ditemukan.');

        // Ambil semua permission milik module
        $permissions = DB::table('permissions')
            ->where('module_id', $id)
            ->orderBy('id')
            ->get();

        return view('page.modules.edit', compact('data', 'permissions', 'id'));
    }

    public function update(Request $request)
    {
        // ─────────────────────────────────────────
        // VALIDASI DASAR MODULE
        // ─────────────────────────────────────────
        $request->validate([
            'nama'               => 'required|string|max:150',
            'slug'               => 'nullable|string|max:100|unique:modules,slug,'.$request->id,
            'group'              => 'nullable|string|max:100',

            'permissions'                 => 'required|array|min:1',
            'permissions.*.name'          => 'required|string|max:100',
            'permissions.*.slug'          => 'nullable|string|max:150',
            'permissions.*.id'            => 'nullable|integer',
        ], [
            'nama.required'               => 'Nama module wajib diisi.',
            'slug.unique'                 => 'Slug module sudah digunakan.',
            'permissions.required'        => 'Minimal 1 permission diperlukan.',
            'permissions.*.name.required' => 'Nama permission wajib diisi.',
            'permissions.*.name.max'      => 'Nama permission terlalu panjang.',
        ]);

        DB::beginTransaction();

        try {

            // Cek slug unik untuk module lain
            if (Module::where('slug', $request->slug)
                    ->where('id', '!=', $request->id)
                    ->exists()) 
            {
                return response()->json([
                    'status' => 'error',
                    'messages' => 'Slug sudah digunakan, silakan pakai slug lain.'
                ], 401);
                
            }

            // Data update
            $data = [
                'name'       => $request->nama,
                'slug'       => $request->slug,
                'group'      => $request->group,
                'updated_at' => Carbon::now()
            ];

            DB::table('modules')->where('id', $request->id)->update($data);
             // 2. HAPUS SEMUA PERMISSION LAMA
            DB::table('permissions')->where('module_id', $request->id)->delete();
            // 3. VALIDASI DUPLIKAT SLUG DALAM FORM
            $slugList = [];
            foreach ($request->permissions as $p) {

                $slug = $p['slug'] ?: Str::slug($p['name']);

                if (in_array($slug, $slugList)) {
                    return response()->json([
                        'status'   => 'error',
                        'messages' => "Slug permission duplikat dalam form: {$slug}"
                    ], 422);
                }

                $slugList[] = $slug;
            }
                // 4. INSERT SEMUA PERMISSION BARU
            foreach ($request->permissions as $p) {
                DB::table('permissions')->insert([
                    'module_id'  => $request->id,
                    'name'       => $p['name'],
                    'slug'       => $p['slug'] ?: Str::slug($p['name']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }



            DB::commit();

            return response()->json([
                'status' => 'success',
                'messages' => 'Module berhasil diupdate.'
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            // simpan error asli ke log
            Log::error('Module Update Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // user hanya melihat error umum (aman)
            return response()->json([
                'status'  => 'error',
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
