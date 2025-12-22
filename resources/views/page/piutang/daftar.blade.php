@extends('layouts.app')
@section('title','Daftar Piutang')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Daftar Piutang</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Daftar Piutang</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
@section('css')
<style>

</style>

@endsection
@section('content')
<div class="container">
    <!-- FILTER -->
<div class="row g-2 mb-4">
    @if(auth()->user()->level != "entitas")
    <div class="col-md-3">
        <select id="filter_entitas" class="form-select form-select-sm entitas">
            <option value="">Semua Entitas</option>
        </select>
    </div>
    @endif
    <div class="col-md-3">
        <select id="filter_cabang" class="form-select form-select-sm cabang">
            <option value="">Semua Cabang</option>
        </select>
    </div>

    <div class="col-md-3">
        {{-- 🔽 Filter Tipe Partner --}}
        <select id="filter_tipe" class="form-select partner">
            <option value="all">Semua Partner</option>
        </select>
    </div>

    <div class="col-md-3">
        <div class='btn-group'>
        @canAccess('piutang.daftar.export')
        <button id='btnExportExcel' data-toggle='tooltip' title='Export Excel' class="btn btn-success">
            <i class="fas fa-file-excel"></i> 
        </button>
        @endcanAccess
        </div>
    </div>
</div>
<div class="card card-success card-outline">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0">Daftar Piutang</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tb_data" class="table table-bordered table-striped align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Invoice</th>
                        <th>Partner</th>
                        <th class="text-end">Tagihan</th>
                        <th class="text-end">Pelunasan</th>
                        <th class="text-end">Sisa Piutang</th>
                        <th class="text-center">Umur (Hari)</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot class="table-light fw-bold text-end">
                    <tr>
                        <th colspan="4" class="text-center">TOTAL</th>
                        <th id="total_tagihan">0</th>
                        <th id="total_pelunasan">0</th>
                        <th id="total_sisa">0</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    @if(auth()->user()->level != "entitas")
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        // width: '100%',
         width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    @endif
    @canAccess('piutang.daftar.view')
    // 🔹 Select2 Partner
    $('.partner').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // teks yang diketik user
                    jenis: 'all', // teks yang diketik user
                    @if(auth()->user()->level != "entitas")
                    entitas_id: $('#entitas_id').val() || "{{ auth()->user()->entitas_id }}" // kirim data tambahan jika ada
                    @endif
                };
            },
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: q.nama }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Partner --",
        allowClear: true
    });
    $('.cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    const tb = $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
         ajax: {
            url: "{{ route('piutang.daftar') }}",
            data: function (d) {
                d.partner_id = $('#filter_tipe').val();
                d.entitas_id = $('#filter_entitas').val();
                d.cabang_id = $('#filter_cabang').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', className: 'text-center', orderable: false,searchable: false },
            { 
                data: 'tanggal', 
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    if (!data) return '-';
                    const tgl = new Date(data);
                    return tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                }
            },
            { data: 'no_invoice', className: 'text-center',orderable: false, 
                render: function(data,c,row) {
                    let html = `<b>${row.kode_jurnal}</b>`;
                    if (row.no_invoice && row.no_invoice !== '') {
                        html += `<br><small>No Invoice: ${row.no_invoice}</small>`;
                    }
                    return html;
                }
            },
            { data: 'partner_nama' ,orderable: false },
            { data: 'total_tagihan', className: 'text-end',orderable: false,searchable: false },
            { data: 'total_pelunasan', className: 'text-end',orderable: false,searchable: false },
            { data: 'sisa_piutang', className: 'text-end fw-bold',orderable: false,searchable: false },
            { data: 'umur_piutang', className: 'text-center',orderable: false,searchable: false },
        ],
        order: [[1, 'desc']],
        footerCallback: function (row, data, start, end, display) {
            let api = this.api();

            // fungsi konversi string ke angka
            let intVal = function (i) {
                return typeof i === 'string'
                    ? i.replace(/\./g, '').replace(/,/g, '.') * 1
                    : typeof i === 'number'
                        ? i
                        : 0;
            };

            // hitung total tiap kolom
            let totalTagihan = api.column(4, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
            let totalPelunasan = api.column(5, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
            let totalSisa = api.column(6, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

            // tampilkan hasil di footer
            $(api.column(4).footer()).html(totalTagihan.toLocaleString('id-ID'));
            $(api.column(5).footer()).html(totalPelunasan.toLocaleString('id-ID'));
            $(api.column(6).footer()).html(totalSisa.toLocaleString('id-ID'));
        },
        language: {
            searchPlaceholder: 'Cari partner...',
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });

    // 🔄 Reload ketika filter berubah
    $('#filter_tipe, #filter_entitas,#filter_cabang').on('change', function() {
        tb.ajax.reload();
    });
    @endcanAccess
    @canAccess('piutang.daftar.export')
    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const partner = $('#filter_tipe').val();
        @if(auth()->user()->level == 'entitas'){
            const entitas = "{{ auth()->user()->entitas_id }}";
        @else
            const entitas = $('#filter_entitas').val();
        @endif
        const cabang = $('#filter_cabang').val(); // ← ambil pilihan cabang

        const url = "{{ route('piutang.daftar.export') }}"
            + "?parter_id=" + encodeURIComponent(partner ?? '')
            + "&entitas_id=" + encodeURIComponent(entitas ?? '')
            + "&cabang_id=" + encodeURIComponent(cabang ?? '');

        window.location.href = url;
    });
    @endcanAccess
});
</script>
@endsection
