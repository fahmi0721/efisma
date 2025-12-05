@extends('layouts.app')
@section('title', 'Laporan Arus Kas')
@section('css')
<style>

</style>
@endsection

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h4 class="mb-2">Laporan Arus Kas</h4></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Laporan Arus Kas</li>
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
            <select id="filter_cabang" name="cabang" class="form-select">
                <option value="">Semua Cabang</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" id="periode" class="form-control form-control flatpickr-input" placeholder="Pilih Periode" style="width: 200px;" />
        </div>

        <div class="col-md-3">
            <div class='btn-group'>
            @canAccess('arus_kas.view')
            <button id='btn-filter' class="btn btn-primary">Tampilkan</button>
            @endcanAccess
            @canAccess('arus_kas.export')
            <button id='btn-export' data-toggle='tooltip' title='Export Excel' class="btn btn-success">
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
        <table id="tblCashflow" class="table table-bordered table-striped w-100">
            <thead class="bg-light">
                <tr>
                    <th>Entitas</th>
                    <th>Saldo Awal</th>
                    <th>OP Masuk</th>
                    <th>OP Keluar</th>
                    <th>Operasional</th>
                    <th>INV Masuk</th>
                    <th>INV Keluar</th>
                    <th>Investasi</th>
                    <th>PDN Masuk</th>
                    <th>PDN Keluar</th>
                    <th>Pendanaan</th>
                    <th>Kenaikan Kas</th>
                    <th>Saldo Akhir</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot class="bg-light fw-bold">
                <tr>
                    <td class="text-center">GRAND TOTAL</td>
                    <td id="gt_saldo_awal"></td>
                    <td id="gt_op_masuk"></td>
                    <td id="gt_op_keluar"></td>
                    <td id="gt_operasional"></td>
                    <td id="gt_inv_masuk"></td>
                    <td id="gt_inv_keluar"></td>
                    <td id="gt_investasi"></td>
                    <td id="gt_pdn_masuk"></td>
                    <td id="gt_pdn_keluar"></td>
                    <td id="gt_pendanaan"></td>
                    <td id="gt_kenaikan"></td>
                    <td id="gt_saldo_akhir"></td>
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
    $('#filter_cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
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

$('#filter_entitas').val('').trigger('change');
@canAccess('arus_kas.view')
 // Reload saat filter berubah
$('#btn-filter').on('click', function() {
    table.ajax.reload();
});
@endcanAccess 
@canAccess('arus_kas.export')
 // Export Excel
$('#btn-export').click(function() {
    let entitas = $('#filter_entitas').val();
    let cabang_id = $('#filter_cabang').val();
    let periode = $('#periode').val();
    @if(auth()->user()->level != "entitas")
        window.location.href = "{{ route('laporan.arus_kas.export') }}?entitas_id=" + entitas + "&periode=" + periode + "&cabang_id=" + cabang_id;
    @else
        window.location.href = "{{ route('laporan.arus_kas.export') }}?entitas_id=&periode=" + periode + "&cabang_id=" + cabang_id;
    @endif
});
@endcanAccess 
function rupiah(x) {
    return 'Rp ' + Number(x).toLocaleString('id-ID');
}
@canAccess('arus_kas.view')
const table = $('#tblCashflow').DataTable({
    processing: true,
    serverSide: false,
    responsive: true,
    ajax: {
        url: "{{ route('laporan.arus_kas.data') }}",  // ← sesuaikan route Anda
        data: function(d) {
            d.entitas_id = $('#filter_entitas').val();
            d.periode = $('#periode').val();
            d.cabang_id = $('#filter_cabang').val();
        },
        dataSrc: function(json) {

            // Set Grand Total di Footer
            $('#gt_saldo_awal').text(rupiah(json.grand_total.saldo_awal));
            $('#gt_op_masuk').text(rupiah(json.grand_total.operasional_masuk));
            $('#gt_op_keluar').text(rupiah(json.grand_total.operasional_keluar));
            $('#gt_operasional').text(rupiah(json.grand_total.operasional));
            $('#gt_inv_masuk').text(rupiah(json.grand_total.investasi_masuk));
            $('#gt_inv_keluar').text(rupiah(json.grand_total.investasi_keluar));
            $('#gt_investasi').text(rupiah(json.grand_total.investasi));
            $('#gt_pdn_masuk').text(rupiah(json.grand_total.pendanaan_masuk));
            $('#gt_pdn_keluar').text(rupiah(json.grand_total.pendanaan_keluar));
            $('#gt_pendanaan').text(rupiah(json.grand_total.pendanaan));
            $('#gt_kenaikan').text(rupiah(json.grand_total.kenaikan_kas));
            $('#gt_saldo_akhir').text(rupiah(json.grand_total.saldo_akhir));

            return json.per_entitas;
        }
    },
    columns: [
        { data: 'nama_entitas' },
        { data: 'saldo_awal', render: rupiah },
        { data: 'operasional_masuk', render: rupiah },
        { data: 'operasional_keluar', render: rupiah },
        { data: 'operasional', render: rupiah },
        { data: 'investasi_masuk', render: rupiah },
        { data: 'investasi_keluar', render: rupiah },
        { data: 'investasi', render: rupiah },
        { data: 'pendanaan_masuk', render: rupiah },
        { data: 'pendanaan_keluar', render: rupiah },
        { data: 'pendanaan', render: rupiah },
        { data: 'kenaikan_kas', render: rupiah },
        { data: 'saldo_akhir', render: rupiah },
    ],
    order: [[0, 'asc']]
});
@endcanAccess


</script>
@endsection
