@extends('layouts.app')
@section('title', 'Kaetun Akun')
@section('css')
<style>

</style>
@endsection

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h4 class="mb-2">Kartu Akun</h4></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kartu Akun</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container">
    <!-- FILTER -->
    <div class="row g-2 mb-4">
        @if(auth()->user()->level != "entitas")
        <div class="col-md-3">
            <select id="filter_entitas" name="entitas" class="form-select">
                <option value="">Semua Entitas</option>
            </select>
        </div>
        @endif
        <div class="col-md-3">
            <select id="filter_akun" name="akun_gl_id" class="form-select">
                <option value="">..:: Pillih Akun GL</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" id="tanggal_awal" class="form-control form-control flatpickr-input" placeholder="Tanggal Awal" style="width: 200px;" />
        </div>
        <div class="col-md-2">
            <input type="text" id="tanggal_akhir" class="form-control form-control flatpickr-input" placeholder="Tanggal Akhir" style="width: 200px;" />
        </div>

        <div class="col-md-2">
            <div class='btn-group'>
            @canAccess('kartuakun.view')
            <button id='btn-filter' class="btn btn-primary">Tampilkan</button>
            @endcanAccess
            @canAccess('kartuakun.export')
            <button id='btnExportExcel' data-toggle='tooltip' title='Export Excel' class="btn btn-success">
                <i class="fas fa-file-excel"></i> 
            </button>
            @endcanAccess
            </div>
        </div>
    </div>


    <!-- TABLE RESULT -->
    <!-- LOADER -->
    <div class="text-center my-3 d-none" id="loader">
        <div class="spinner-border text-primary"></div>
        <div>Loading data...</div>
    </div>

    <!-- TABEL -->
    <div class="card">
    <div class="card-body">
        <div class='table-striped'>
        <table id="t_data" class="table table-bordered table-striped w-100">
            <thead class="bg-light">
                <tr>
                    <th>No</th>
                    <th>Kode Jurnal</th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Debet</th>
                    <th>Kredit</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot class="table-light fw-bold text-end">
                <tr>
                    <th colspan="4" class="text-center">TOTAL</th>
                    <th id="total_debet">0</th>
                    <th id="total_kredit">0</th>
                    <th id="total_saldo">0</th>
                </tr>
            </tfoot>
        </table>
    </div>
    </div>
</div>

</div>
@endsection

@section('js')
<script>
let table;
let isLoaded = false;
@canAccess('kartuakun.view')
 table =    $('#t_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('laporan.kartuakun') }}",
            data: function (d) {
                d.entitas_id = $('#filter_entitas').val();
                d.akun_gl_id = $('#filter_akun').val();
                d.tanggal_awal = $('#tanggal_awal').val();
                d.tanggal_akhir = $('#tanggal_akhir').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'kode_jurnal', name: 'kode_jurnal' },
            { data: 'tanggal', name: 'tanggal' },
            { data: 'keterangan', name: 'keterangan' },
            { 
                data: 'debit', 
                name: 'debit',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
            { 
                data: 'kredit', 
                name: 'kredit',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
            { 
                data: 'saldo', 
                name: 'saldo',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
        ],
        drawCallback: function(settings) {
            let api = this.api();
            let json = api.ajax.json(); // Mengambil data tambahan dari server
            
            if (json.totalFooter) {
                let res = json.totalFooter;
                let format = new Intl.NumberFormat('id-ID');

                $(api.column(4).footer()).html(format.format(res.total_debit));
                $(api.column(5).footer()).html(format.format(res.total_kredit));
                $(api.column(6).footer()).html(format.format(res.saldo_akhir));
            }
             // Sembunyikan pagination jika data kosong atau hanya 1 halaman
            var rowCount = api.rows().data().length;
            if (rowCount === 0) {
                $('.dataTables_paginate').hide();
                $('.dataTables_info').hide();
            } else {
                $('.dataTables_paginate').show();
                $('.dataTables_info').show();
            }
        },
        // order: [[2, 'desc']],
    });
@endcanAccess
$(function() {
    $("[data-toggle='tooltip']").tooltip();
       // 🔹 Flatpickr Month Picker dengan default bulan ini
    flatpickr("#tanggal_awal", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });
    flatpickr("#tanggal_akhir", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });
    // 🔽 Select2 Akun GL
    $('#filter_akun').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Akun GL --",
        allowClear: true
    });
    @if(auth()->user()->level != "entitas")
    $('#filter_entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: q.nama }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true,
        minimumResultsForSearch: 0, // -1 = search box selalu disembunyikan
        escapeMarkup: markup => markup
    });
    @endif
});
@canAccess('kartuakun.view')
$('#filter_entitas').val('').trigger('change');

 // Reload saat filter berubah
$('#btn-filter').on('click', function() {
    if (!$('#filter_akun').val()) {
        // alert('Silakan pilih Akun GL terlebih dahulu');
        return;
    }
    isLoaded = true;
    table.ajax.reload();
});

table.on('draw', function () {
    $('#loader').addClass('d-none');
});
@endcanAccess
@canAccess('kartuakun.export')
$('#btnExportExcel').on('click', function () {
    @if(auth()->user()->level == "admin")
        let entitas = $('#filter_entitas').val();
    @else
        let entitas = "{{ auth()->user()->entitas_id }}";
    @endif
    let akun    = $('#filter_akun').val();
    let tanggal_awal = $('#tanggal_awal').val();
    let tanggal_akhir = $('#tanggal_akhir').val();

    if (!akun) {
        alert('Pilih akun terlebih dahulu');
        return;
    }

    window.location.href =
        "{{ route('laporan.kartuakun.export') }}" +
        "?entitas_id=" + entitas +
        "&akun_gl_id=" + akun +
        "&tanggal_akhir=" + tanggal_akhir +
        "&tanggal_awal=" + tanggal_awal;
});
@endcanAccess
function rupiah(x) {
    return 'Rp ' + Number(x).toLocaleString('id-ID');
}





</script>
@endsection
