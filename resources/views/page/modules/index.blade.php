@extends('layouts.app')
@section('title','Module')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Module</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Module</li>
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
                    <h5 class="mb-0">Data Module</h5>
                    <a href="{{ route('module.create') }}" class="btn btn-success btn-sm ms-auto">
                        <i class="fas fa-plus-square"></i> Create New
                    </a>
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th width='5%'>No</th>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Group</th>
                                <th width='5%'>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>  
        </div>
    </div>    
</div>
<div class="modal fade" id="modalDetailPermission" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Permission Module</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <table class="table table-bordered mb-3">
                    <tr><th>Module Name</th><td id="d_module_name"></td></tr>
                    <tr><th>Module Slug</th><td id="d_module_slug"></td></tr>
                    <tr><th>Group</th><td id="d_module_group"></td></tr>
                </table>

                <h6>Permissions</h6>
                <table class="table table-bordered" id="table_permission_list">
                    <thead>
                        <tr>
                            <th>No</th>
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
$(document).on('click', '.btn-detail-module', function() {
    let id = $(this).data('id');

    $.get("{{ route('module.detail_permission') }}", { id: id }, function(res){

        if(res.status === 'success') {

            // Tampilkan data module
            $('#d_module_name').text(res.data.name);
            $('#d_module_slug').text(res.data.slug);
            $('#d_module_group').text(res.data.group);

            // Render permissions list
            let html = '';
            res.permissions.forEach((p, i) => {
                html += `
                    <tr>
                        <td>${i+1}</td>
                        <td>${p.name}</td>
                        <td>${p.slug}</td>
                    </tr>
                `;
            });

            $('#table_permission_list tbody').html(html);
            $('#modalDetailPermission').modal('show');
        }

    });
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
                url: "{{ route('module.destroy', ':id') }}".replace(':id', id),
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
        ajax: "{{ route('module') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex',orderable: false, searchable: false   },
            { data: 'name', name: 'name' },
            { data: 'slug', name: 'slug' },
            { data: 'group', name: 'group' },
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

