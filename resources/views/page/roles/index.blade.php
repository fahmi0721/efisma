@extends('layouts.app')
@section('title','Roles')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Roles</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Roles</li>
        </ol>
        </div>
    </div>
    <!--end::Row-->
    </div>
    <!--end::Container-->
</div>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-success card-outline mb-4">
                <div class="card-header d-flex  align-items-center">
                    <h5 class="mb-0">Data Roles</h5>
                    <a href="{{ route('role.create') }}" class="btn btn-success btn-sm ms-auto">
                        <i class="fas fa-plus-square"></i> Create New
                    </a>
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th width='5%'>No</th>
                                <th>Nama</th>
                                <th>Deskripsi</th>
                                <th width='5%'>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>  
        </div>
    </div>    
</div>
 <div class="modal fade" id="modalViewPermission" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Permissions for Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                
                <table class="table table-bordered" id="permRoleTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Module</th>
                            <th>Permission Name</th>
                            <th>Slug</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

            </div>

        </div>
    </div>
</div>
@endsection
@section('js')
<script>
$(document).ready(function() {
    load_data();
});
let permTable;
$(document).on('click', '.btn-detail-role', function() {
    let id = $(this).data('id');

     let roleId = $(this).data('id');

    // Destroy DataTable jika sudah ada
    if ($.fn.DataTable.isDataTable('#permRoleTable')) {
        $('#permRoleTable').DataTable().clear().destroy();
    }

    // Inisialisasi DataTable
    permTable = $('#permRoleTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('role.detail_permission') }}",
            data: { role_id: roleId }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'module_name', name: 'module_name' },
            { data: 'permission_name', name: 'permission_name' },
            { data: 'permission_slug', name: 'permission_slug' },
        ]
    });

    $('#modalViewPermission').modal('show');
});
function hapusData(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data ini akan dihapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('role.destroy', ':id') }}".replace(':id', id),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE"
                },
                success: function(response) {
                    Swal.fire(
                        'Deleted!',
                        'Data berhasil dihapus.',
                        'success'
                    );
                    // Reload DataTable
                    $('#tb_data').DataTable().ajax.reload();
                },
                error: function(err) {
                    console.log(err);
                    Swal.fire(
                        'Gagal!',
                        'Terjadi kesalahan saat menghapus data.',
                        'error'
                    );
                }
            });
        }
    });
}
load_data = function(){
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('role') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex',orderable: false, searchable: false   },
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
        ]
    });
    // Init tooltip setiap setelah table redraw
    $('#tb_data').on('draw.dt', function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // Init pertama kali
    $('[data-bs-toggle="tooltip"]').tooltip();
}
</script>
@endsection

