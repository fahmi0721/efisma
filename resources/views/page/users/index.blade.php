@extends('layouts.app')
@section('title','Users')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Users</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Users</li>
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
                    <h5 class="mb-0">Data Users</h5>
                    <a href="{{ route('users.create') }}" class="btn btn-success btn-sm ms-auto">
                        <i class="fas fa-plus-square"></i> Create New
                    </a>
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th width='5%'>No</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Level</th>
                                <th width='5%'>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>  
        </div>
    </div>    
</div>
<div class="modal fade" id="modalAssignRole">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Assign Role ke User</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <h5 id="userName"></h5>

                <div id="roleList">
                    {{-- akan di-load via AJAX --}}
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-success" id="btnSaveRole">Simpan</button>
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
$(document).on('click', '.btn-assign', function () {

    let user_id = $(this).data('id');

    $.get("{{ route('users.getRole') }}", { user_id:user_id }, function(res){

        if (res.status == 'success') {

            $('#userName').text("User: " + res.user.nama);

            let html = `<form id='formAssignRole'>`;

            res.roles.forEach(r => {

                let checked = res.userRoles.includes(r.id) ? 'checked' : '';

                html += `
                    <div class="form-check">
                        <input class="form-check-input role-check" type="checkbox"
                            value="${r.id}" name="roles[]" ${checked}>
                        <label class="form-check-label">${r.name}</label>
                    </div>
                `;
            });

            html += `</form>`;

            $('#roleList').html(html);
            $('#modalAssignRole').modal('show');

            // save handler
            $('#btnSaveRole').off().on('click', function(){
                $.post("{{ route('users.saveRole') }}", {
                    _token: "{{ csrf_token() }}",
                    user_id: user_id,
                    roles: $('#formAssignRole').serializeArray().map(r => r.value)
                }, function(resp){
                    if (resp.status == 'success') {
                        position = "bottom-left";
                        icons = resp.status;
                        pesan = "Role berhasil diperbarui";
                        title = "Roles!";
                        info(title,pesan,icons,position);
                        $('#modalAssignRole').modal('hide');
                    }
                });
            });

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
                url: "{{ route('users.destroy', ':id') }}".replace(':id', id),
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
        ajax: "{{ route('users') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex' },
            { data: 'nama', name: 'nama' },
            { data: 'jabatan', name: 'jabatan' },
            { data: 'username', name: 'username' },
            { data: 'email', name: 'email' },
            { data: 'level', name: 'level' },
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

