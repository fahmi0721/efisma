@extends('layouts.app')
@section('title','Monitoring Piutang')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Monitoring Piutang</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Monitoring Piutang</li>
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
            <option value="">Semua Partner</option>
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
        <h5 class="mb-0">Monitoring Piutang</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tb_data" class="table table-bordered table-striped align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th rowspan='2'width="5%">No</th>
                        <th rowspan='2'>No Invoice</th>
                        <th rowspan='2'>Entitas</th>
                        <th rowspan='2'>Partner</th>
                        <th rowspan='2'>Deskripsi</th>
                        <th colspan='3' class="text-center">Piutang</th>
                        <th colspan='3' class="text-center">Pelunasan</th>
                    </tr>
                    <tr>
                        <th>Kode Jurnal</th>
                        <th>Tanggal</th>
                        <th class="text-end">Jumlah</th>
                        <th>Kode Jurnal</th>
                        <th>Tanggal</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                </thead>
                <tbody></tbody>
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
    @canAccess('piutang.monitoring.view')
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
        // placeholder: "-- Pilih Partner --",
        // allowClear: true
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
        responsive: true,
         ajax: {
            url: "{{ route('piutang.monitoring') }}",
            data: function (d) {
                d.partner_id = $('#filter_tipe').val();
                d.entitas_id = $('#filter_entitas').val();
                d.cabang_id = $('#filter_cabang').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', className: 'text-center', orderable: false,searchable: false },
            { data: 'invoice', className: 'text-center',orderable: false},
            { data: 'entitas' ,orderable: false },
            { data: 'partner' ,orderable: false },
            { data: 'deskripsi' ,orderable: false },
            { data: 'kode_jurnal_piutang' ,orderable: false },
            { 
                data: 'tanggal_piutang', 
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    if (!data) return '-';
                    const tgl = new Date(data);
                    return tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                }
            },
            { data: 'jumlah_piutang', className: 'text-end',orderable: false,searchable: false },
            { data: 'kode_jurnal_pelunasan' ,orderable: false },
            { 
                data: 'tanggal_pelunasan', 
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    if (!data) return '-';
                    const tgl = new Date(data);
                    return tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                }
            },
            { data: 'jumlah_pelunasan', className: 'text-end',orderable: false,searchable: false }
            
        ],
        order: [[1, 'desc']],
        language: {
            searchPlaceholder: 'Cari data...',
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });

    // 🔄 Reload ketika filter berubah
    $('#filter_tipe, #filter_entitas,#filter_cabang').on('change', function() {
        tb.ajax.reload();
    });
    @endcanAccess
    @canAccess('piutang.monitoring.view')
    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const partner = $('#filter_tipe').val();
        @if(auth()->user()->level == 'entitas')
            const entitas = "{{ auth()->user()->entitas_id }}";
        @else
            const entitas = $('#filter_entitas').val();
        @endif
        const cabang = $('#filter_cabang').val(); // ← ambil pilihan cabang

        const url = "{{ route('piutang.monitoring.export') }}"
            + "?parter_id=" + encodeURIComponent(partner ?? '')
            + "&entitas_id=" + encodeURIComponent(entitas ?? '')
            + "&cabang_id=" + encodeURIComponent(cabang ?? '');

        window.location.href = url;
    });
    @endcanAccess
});
</script>
@endsection
