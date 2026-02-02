@extends('layouts.app')
@section('title','Aging Hutang')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Aging Hutang</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Aging Hutang</li>
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
            <h5 class="mb-0">Daftar Aging Hutang per Partner</h5>

            <div class="d-flex align-items-center gap-2 ms-auto">
                @if(auth()->user()->level != "entitas")
                {{-- 🔽 Filter Entitas --}}
                <select id="filter_entitas" class="form-select form-select-sm entitas" style="width:180px">
                    <option value="">Semua Entitas</option>
                </select>
                @endif
                {{-- 🔽 Filter Akun Hutang --}}
                <select id="filter_hutang" class="form-select hutang" style="width:180px">
                    <option value="">Semua Hutang</option>
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
                            <th>Akun Hutang</th>
                            <th class="text-end">0–14 Hari</th>
                            <th class="text-end">15–30 Hari</th>
                            <th class="text-end">31–45 Hari</th>
                            <th class="text-end">46–60 Hari</th>
                            <th class="text-end">>60 Hari</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr class="bg-light fw-bold">
                        <th colspan="3" class="text-center">TOTAL</th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
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
    @canAccess('hutang.aging.view')
    $('.hutang').select2({
        ajax: {
            url: '{{ route("hutang.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // teks yang diketik user
                };
            },
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: q.nama }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        // minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    const tb = $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive:true,
         ajax: {
            url: "{{ route('hutang.aging') }}",
            data: function (d) {
                d.hutang_id = $('#filter_hutang').val();
                d.entitas_id = $('#filter_entitas').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'partner_nama', name: 'partner_nama' },
            { 
                data: 'akun_nama', 
                searchable: false,
                className: 'text-center',
                render: function(data,type, row) {
                    if (!data) return '-';
                    let res = row.akun_kode+" - "+row.akun_nama;
                    return res;
                }
            },
            { data: 'aging_0_14', name: 'aging_0_14', className: 'text-end' },
            { data: 'aging_15_30', name: 'aging_15_30', className: 'text-end' },
            { data: 'aging_31_45', name: 'aging_31_45', className: 'text-end' },
            { data: 'aging_46_60', name: 'aging_46_60', className: 'text-end' },
            { data: 'aging_60_plus', name: 'aging_60_plus', className: 'text-end' },
            { data: 'total_hutang', name: 'total_hutang', className: 'text-end fw-bold' },
        ],
        order: [[1, 'asc']],
        drawCallback: function(settings) {
            let api = this.api();
            let json = api.ajax.json(); // Mengambil data tambahan dari server
            
            if (json.totalFooter) {
                let res = json.totalFooter;
                let format = new Intl.NumberFormat('id-ID');

                $(api.column(3).footer()).html(res.aging_0_14);
                $(api.column(4).footer()).html(res.aging_15_30);
                $(api.column(5).footer()).html(res.aging_31_45);
                $(api.column(6).footer()).html(res.aging_46_60);
                $(api.column(7).footer()).html(res.aging_60_plus);
                $(api.column(8).footer()).html(res.total_hutang);
            }
        },
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
     $('#filter_hutang').on('change', function() {
        tb.ajax.reload();
    });
    @endcanAccess
    @canAccess('hutang.aging.export')
    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const params = {
            hutang_id: $('#filter_hutang').val() || '',
            entitas_id: $('#filter_entitas').val() || '',
        };
        const query = new URLSearchParams(params).toString();
        window.location.href = "{{ route('hutang.aging.export') }}?" + query;
    });
    @endcanAccess
});
</script>
@endsection
