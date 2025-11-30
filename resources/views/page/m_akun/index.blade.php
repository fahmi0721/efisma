@extends('layouts.app')
@section('title','Akun GL')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Akun GL</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Akun GL</li>
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
                    <h5 class="mb-0">Data Akun GL</h5>
                    <div class=' ms-auto'>
                        <a href="{{ route('m_akun.transaksi') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-book"></i> Akun Transkasi
                        </a>
                        <a href="{{ route('m_akun.map') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-map"></i> Maping Akun
                        </a>
                        @canAccess('m_akun.create')
                        <a href="{{ route('m_akun.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-square"></i> Create New
                        </a>
                        @endcanAccess
                    </div>
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th width='5%'>No</th>
                                <th>Id</th>
                                <th>Nomor</th>
                                <th>Nama</th>
                                <th>Tipe</th>
                                <th>Kategori</th>
                                <th>Saldo Normal</th>
                                @canAccess('m_akun.edit|m_akun.delete')
                                <th width='5%'>Aksi</th>
                                @endcanAccess
                            </tr>
                        </thead>
                    </table>
                </div>
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
                url: "{{ route('m_akun.destroy', ':id') }}".replace(':id', id),
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
                    $('#tb_data').DataTable().ajax.reload(null, false);
                },
                error: function(err) {
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
        ajax: "{{ route('m_akun') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex' },
            { data: 'id', name: 'id',orderable: false },
            { data: 'no_akun', name: 'no_akun',orderable: false },
            { data: 'nama', name: 'nama', orderable: false, },
            { data: 'tipe_akun', name: 'tipe_akun', orderable: false, },
            { data: 'kategori', name: 'kategori', orderable: false, },
            { data: 'saldo_normal', name: 'saldo_normal', orderable: false, },
            @canAccess('m_akun.edit|m_akun.delete')
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
            @endcanAccess

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

