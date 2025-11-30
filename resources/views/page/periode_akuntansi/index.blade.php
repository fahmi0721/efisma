@extends('layouts.app')
@section('title','Periode Akutansi')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Periode Akutansi</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Periode Akutansi</li>
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
                    <h5 class="mb-0">Data Periode Akutansi</h5>
                    @canAccess('periode.create')
                    <a href="{{ route('periode_akuntansi.create') }}" class="btn btn-success btn-sm ms-auto">
                        <i class="fas fa-plus-square"></i> Create New
                    </a>
                    @endcanAccess
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th width='5%'>No</th>
                                <th>Periode Akutansi</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status</th>
                                <th>Close By</th>
                                @canAccess('periode.edit|periode.delete|periode.open_close')
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
@canAccess('periode.open_close')
function update_status(id, status) {
    let textConfirm = status === 'close' 
        ? "Apakah kamu yakin ingin MENUTUP periode ini? Setelah ditutup, jurnal tidak bisa diubah." 
        : "Apakah kamu yakin ingin MEMBUKA kembali periode ini?";
    
    Swal.fire({
        title: "Konfirmasi",
        text: textConfirm,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: status === 'close' ? '#d33' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: status === 'close' ? 'Ya, Tutup!' : 'Ya, Buka!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('periode_akuntansi/update-status') }}/" + id,
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    status: status
                },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Harap tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(result) {
                    Swal.close();
                    if (result.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil",
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#tb_data').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: "warning",
                            title: "Perhatian",
                            text: result.message
                        });
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let msg = xhr.responseJSON?.message || "Terjadi kesalahan sistem";
                    Swal.fire({
                        icon: "error",
                        title: "Gagal!",
                        text: msg
                    });
                }
            });
        }
    });
}
@endcanAccess
@canAccess('periode.delete')
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
                url: "{{ route('periode_akuntansi.destroy', ':id') }}".replace(':id', id),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE"
                },
                success: function(response) {
                    if(response.status == "success"){
                        Swal.fire(
                            'Deleted!',
                            'Data berhasil dihapus.',
                            'success'
                        );
                    }else{
                        Swal.fire(
                            'Deleted!',
                            response.messages,
                            'error'
                        );
                    }
                    $('#tb_data').DataTable().ajax.reload();
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
@endcanAccess
load_data = function(){
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('periode_akuntansi') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex' },
            {
                data: 'periode', 
                name: 'periode',
                render: function(data) {
                if (!data) return '-';
                    // data misalnya "2025-10"
                    const [year, month] = data.split('-');
                    const bulan = [
                        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    return `${bulan[parseInt(month)]} ${year}`;
                }
            },
            { 
                data: 'tanggal_mulai', 
                name: 'tanggal_mulai',
                orderable: false,
                render: function(data) {
                    if (!data) return '-';
                    return new Date(data).toLocaleDateString('id-ID', { 
                        day: '2-digit', month: 'long', year: 'numeric' 
                    });
                }
            },
            { 
                data: 'tanggal_selesai', 
                name: 'tanggal_selesai',
                orderable: false,
                render: function(data) {
                    if (!data) return '-';
                    return new Date(data).toLocaleDateString('id-ID', { 
                        day: '2-digit', month: 'long', year: 'numeric' 
                    });
                }
            },
            { data: 'status', name: 'status',orderable: false },
            { data: 'closed_by', name: 'closed_by',orderable: false },
            @canAccess('periode.edit|periode.delete|periode.open_close')
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

