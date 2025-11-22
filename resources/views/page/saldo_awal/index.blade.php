@extends('layouts.app')
@section('title','Saldo Awal Akun GL')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Saldo Awal Akun GL</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Saldo Awal Akun GL</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card card-success card-outline mb-4">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Data Saldo Awal Akun GL</h5>
                    <div class="ms-auto">
                        <a href="{{ route('saldo_awal.form_import') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload"></i> Import Excel
                        </a>
                        <a href="{{ route('saldo_awal.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-square"></i> Create New
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <!-- 🔍 Filter Periode -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filter_periode" class="form-label">Filter Periode</label>
                            <div class="input-group date" id="periode_picker">
                                <input type="text" id="filter_periode" class="form-control" placeholder="Pilih periode (YYYY-MM)" readonly />
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button id="btnFilter" class="btn btn-primary w-100 mt-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button id="btnReset" class="btn btn-secondary w-100 mt-2">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- 📊 Data Table -->
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr class="text-center">
                                <th width="5%">No</th>
                                <th>Akun GL</th>
                                <th>Periode</th>
                                <th>Entitas</th>
                                <th>Saldo</th>
                                <th width="5%">Aksi</th>
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
{{-- ✅ Flatpickr Month Picker --}}



<script>
document.addEventListener('DOMContentLoaded', function () {

    // ✅ Inisialisasi Flatpickr Month Picker
    flatpickr("#filter_periode", {
        altInput: true,
        altFormat: "F Y",      // tampil di input → contoh: Oktober 2025
        dateFormat: "Y-m",     // dikirim ke backend → contoh: 2025-10
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ],
        allowInput: false,
        locale: "id"
    });

    // 🔄 Load DataTable
    load_data();

    // Filter tombol klik
    $('#btnFilter').on('click', function() {
        $('#tb_data').DataTable().ajax.reload();
    });

    // Reset filter
    $('#btnReset').on('click', function() {
        // Ambil instance Flatpickr dari elemen
        const picker = document.querySelector('#filter_periode')._flatpickr;
        picker.clear(); // ✅ Kosongkan nilai flatpickr dengan benar
        $('#tb_data').DataTable().ajax.reload();
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
                url: "{{ route('saldo_awal.destroy', ':id') }}".replace(':id', id),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE"
                },
                success: function(response) {
                    Swal.fire('Deleted!', 'Data berhasil dihapus.', 'success');
                    $('#tb_data').DataTable().ajax.reload(null, false);
                },
                error: function(err) {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                }
            });
        }
    });
}

// DataTables
function load_data() {
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('saldo_awal') }}",
            data: function (d) {
                d.periode = $('#filter_periode').val(); // kirim periode ke backend
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'akun_gl', name: 'akun_gl' },
            { data: 'periode', name: 'periode' },
            { data: 'entitas', name: 'entitas' },
            { 
                data: 'saldo', 
                name: 'saldo',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0 }).format(data);
                }
            },
            { data: 'aksi', name: 'aksi', orderable:false, searchable:false, className:'text-center' },
        ],
        order: [[2, 'desc']],
    });
}
</script>
@endsection