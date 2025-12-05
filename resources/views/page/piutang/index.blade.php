@extends('layouts.app')
@section('title','Aging Piutang')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Aging Piutang</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Aging Piutang</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
@section('css')
<style>
.select2-container--bootstrap-5 .select2-selection--single {
    height: calc(1.5em + .5rem + 2px); /* tinggi sama dengan btn-sm */
    padding: .25rem .5rem;
    font-size: .875rem;
    line-height: 1.5;
    border-radius: .2rem;
}

.select2-container--bootstrap-5 .select2-selection__arrow {
    height: 100%;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    padding-left: .25rem;
}

.select2-container {
    min-width: 160px !important;
}

.card-header .select2 {
    margin-bottom: 0 !important;
}
</style>

@endsection
@section('content')
<div class="container-fluid">
    <div class="card card-success card-outline">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Daftar Aging Piutang per Partner</h5>

            <div class="d-flex align-items-center gap-2 ms-auto">
                @if(auth()->user()->level != "entitas")
                {{-- 🔽 Filter Entitas --}}
                <select id="filter_entitas" class="form-select form-select-sm entitas" style="width:180px">
                    <option value="">Semua Entitas</option>
                </select>
                @endif
                <select id="filter_cabang" class="form-select form-select-sm cabang" style="width:180px">
                    <option value="">Semua Cabang</option>
                </select>
                {{-- 🔽 Filter Tipe Partner --}}
                <select id="filter_tipe" class="form-select " style="width:180px">
                    <option value="all">Semua Partner</option>
                    <option value="customer">Customer</option>
                    <option value="vendor">Vendor</option>
                </select>
                @canAccess('piutang.aging.export')
                {{-- 📤 Tombol Export Excel --}}
                <button id="btnExportExcel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                @endcanAccess
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tb_data" class="table table-bordered table-striped align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Partner</th>
                            <th class="text-end">0–14 Hari</th>
                            <th class="text-end">15–30 Hari</th>
                            <th class="text-end">31–45 Hari</th>
                            <th class="text-end">46–60 Hari</th>
                            <th class="text-end">>60 Hari</th>
                            <th class="text-end">Total</th>
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
    @if(auth()->user()->level != "entitas")
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
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
        // width: '100%',
         width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        allowClear: true
    });
    @endif
    @canAccess('piutang.aging.view')
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
            url: "{{ route('piutang.aging') }}",
            data: function (d) {
                d.filter = $('#filter_tipe').val();
                d.entitas_id = $('#filter_entitas').val();
                d.cabang_id = $('#filter_cabang').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'partner_nama', name: 'partner_nama' },
            { data: 'aging_0_14', name: 'aging_0_14', className: 'text-end' },
            { data: 'aging_15_30', name: 'aging_15_30', className: 'text-end' },
            { data: 'aging_31_45', name: 'aging_31_45', className: 'text-end' },
            { data: 'aging_46_60', name: 'aging_46_60', className: 'text-end' },
            { data: 'aging_60_plus', name: 'aging_60_plus', className: 'text-end' },
            { data: 'total_piutang', name: 'total_piutang', className: 'text-end fw-bold' },
        ],
        order: [[1, 'asc']],
        language: {
            searchPlaceholder: 'Cari partner...',
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });
    @if(auth()->user()->level != "entitas")
    // 🔄 Reload ketika filter berubah
    $('#filter_entitas').on('change', function() {
        tb.ajax.reload();
    });
    @endif
     $('#filter_tipe, #filter_cabang').on('change', function() {
        tb.ajax.reload();
    });
    @endcanAccess
    @canAccess('piutang.aging.export')
    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const params = {
            filter: $('#filter_tipe').val() || '',
            entitas_id: $('#filter_entitas').val() || '',
            cabang_id: $('#filter_cabang').val() || ''
        };
        const query = new URLSearchParams(params).toString();
        window.location.href = "{{ route('piutang.aging.export') }}?" + query;
    });
    @endcanAccess
});
</script>
@endsection
