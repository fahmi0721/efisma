@extends('layouts.app')
@section('title', 'Laporan Buku Besar')
@section('css')
<style>

</style>
@endsection

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h4 class="mb-2">Laporan Buku Besar</h4></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Laporan Buku Besar</li>
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
        <div class="col-md-2">
            <input type="text" id="periode" class="form-control form-control flatpickr-input" placeholder="Pilih Periode" style="width: 200px;" />
        </div>

        <div class="col-md-3">
            <div class='btn-group'>
            @canAccess('buku_besar.index')
            <button id='btn-filter' class="btn btn-primary">Tampilkan</button>
            @endcanAccess
            @canAccess('buku_besar.export')
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
                    <th>Akun GL</th>
                    <th>Entitas</th>
                    <th>Partner</th>
                    <th>Keterangan</th>
                    <th>Tanggal</th>
                    <th>Debet</th>
                    <th>Kredit</th>
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
$(function() {
    $("[data-toggle='tooltip']").tooltip();
       // 🔹 Flatpickr Month Picker dengan default bulan ini
    const now = new Date();
    const defaultDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    flatpickr("#periode", {
        altInput: true,
        altFormat: "F Y",   // tampil misalnya: Oktober 2025
        dateFormat: "Y-m",  // dikirim ke backend: 2025-10
        defaultDate: defaultDate,
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ],
        static: true, 
        allowInput: false,
        locale: "id"
    });
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
        allowClear: true,
        minimumResultsForSearch: -1, // -1 = search box selalu disembunyikan
        escapeMarkup: markup => markup
    });
});
@canAccess('buku_besar.index')
$('#filter_entitas').val('').trigger('change');

 // Reload saat filter berubah
$('#btn-filter').on('click', function() {
    table.ajax.reload();
});
@endcanAccess
@canAccess('buku_besar.export')
 // Export Excel
$('#btnExportExcel').click(function() {
    let entitas = $('#filter_entitas').val();
    let periode = $('#periode').val();
    @if(auth()->user()->level != "entitas")
        window.location.href = "{{ route('laporan.bukubesar.export') }}?entitas_id=" + entitas + "&periode=" + periode;
    @else
        window.location.href = "{{ route('laporan.bukubesar.export') }}?entitas_id=&periode=" + periode;
    @endif
});
@endcanAccess
function rupiah(x) {
    return 'Rp ' + Number(x).toLocaleString('id-ID');
}

@canAccess('buku_besar.index')
 const table =    $('#t_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('laporan.bukubesar') }}",
            data: function (d) {
                d.entitas_id = $('#filter_entitas').val();
                d.periode = $('#periode').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'kode_jurnal', name: 'kode_jurnal' },
            { data: 'akun_gl', name: 'akun_gl', orderable:false },
            { data: 'entitas', name: 'entitas' },
            { data: 'partner', name: 'partner' },
            { data: 'keterangan', name: 'keterangan' },
            { data: 'tanggal', name: 'tanggal' },
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
        ],
        // order: [[2, 'desc']],
    });
@endcanAccess



</script>
@endsection
