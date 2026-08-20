@extends('layouts.app')
@section('title','Jurnal Rupa-Rupa')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Jurnal Rupa-Rupa</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Jurnal Rupa-Rupa</li>
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
                    <h5 class="mb-0">Jurnal Rupa-Rupa</h5>
                    <div class="ms-auto">
                        @canAccess('penyesuaian.unposting')
                        <a href="{{ route('jurnal.penyesuaian.unposting') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-bolt"></i> Unposting
                        </a>
                        @endcanAccess
                        @canAccess('penyesuaian.posting')
                        <a href="{{ route('jurnal.penyesuaian.posting') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-bolt"></i> Posting
                        </a>
                        @endcanAccess
                        @canAccess('penyesuaian.create')
                        <a href="{{ route('jurnal.penyesuaian.upload') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-upload"></i> Upload
                        </a>
                        <a href="{{ route('jurnal.penyesuaian.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-square"></i> Create New
                        </a>
                        @endcanAccess
                    </div>
                </div>

                <div class="card-body">

                    <!-- 🔍 Filter Periode -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filter_periode" class="form-label">Filter</label>
                            <div class="input-group date" id="periode_picker">
                                <input type="text" id="filter_from" class="form-control" placeholder="Date From" readonly />
                                <span class="input-group-text">s.d</span>
                                <input type="text" id="filter_to" class="form-control" placeholder="Date To" readonly />
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                        @if(auth()->user()->level != "entitas")
                        <div class="col-md-3">
                            <select id="filter_entitas" class="form-select form-select entitas">
                                <option value="">Semua Entitas</option>
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <div class='btn-group'>
                                <button id="btnFilter" class="btn btn-primary mt-2">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <button id="btnReset" class="btn btn-secondary mt-2">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            </div>
                        </div>
                    </div>

                    <!-- 📊 Data Table -->
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr class="text-center">
                                <th width="5%">No</th>
                                @canAccess('penyesuaian.posting|penyesuaian.unposting|penyesuaian.edit|penyesuaian.delete|penyesuaian.view')
                                <th width="5%">Aksi</th>
                                @endcanAccess
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Entitas</th>
                                <th>Partner</th>
                                <th>Cabang</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-light fw-bold">
                                <th colspan="4" class="text-center">TOTAL</th>
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
</div>

<div class="modal fade" id="DetailTransaksi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Detail Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="DetailTransaksiBody">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-danger" data-bs-dismiss="modal">Close</button>
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
    flatpickr("#filter_from", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });

    flatpickr("#filter_to", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
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
        const picker = document.querySelector('#filter_to')._flatpickr;
        const picker1 = document.querySelector('#filter_from')._flatpickr;
        picker.clear(); // ✅ Kosongkan nilai flatpickr dengan benar
        picker1.clear(); // ✅ Kosongkan nilai flatpickr dengan benar
        $("#filter_entitas").val("").trigger("change");
        $('#tb_data').DataTable().ajax.reload();
    });
});
@canAccess('penyesuaian.view')
function detail_transaksi(id,is_multi_cabang=null){
    $.ajax({
        url: "{{ route('jurnal.detail_transaksi') }}?id="+id,
        type: 'GET',
        success: function (res) {
            console.log(res);
            let modal = $("#DetailTransaksi");
            let html ="";
            html += "<div class='table-responsive'>";
            html += "<table class='table table-striped table-bordered'>";
                html += "<thead>";
                    html += "<tr>";
                        html += "<th>No</th>";
                        html += "<th>Akun GL</th>";
                        if(is_multi_cabang == "1"){
                            html += "<th>Cabang</th>";    
                        }
                        html += "<th>Deskripsi</th>";
                        html += "<th>Debet</th>";
                        html += "<th>Kredit</th>";
                    html += "</tr>";
                html += "</thead>";
                html += "<tbody>";
                    if (res.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>');
                        return;
                    }
                    let no=1;
                    let totDebet=0;
                    let totKredit=0;
                    res.forEach(function(item) {
                        // pastikan debit & kredit selalu angka valid
                        let debit = parseIDR(item.debit);
                        let kredit = parseIDR(item.kredit);

                        let cabang =null;
                        if(is_multi_cabang == "1"){
                            cabang = "<td>"+item.cabang+"</td>";
                        }
                        html += `
                            <tr>
                                <td>${no}</td>
                                <td>${item.akun_gl}</td>
                                ${cabang}
                                <td>${item.deskripsi ?? '-'}</td>
                                <td class="text-end">${Number(item.debit).toLocaleString('id-ID')}</td>
                                <td class="text-end">${Number(item.kredit).toLocaleString('id-ID')}</td>
                            </tr>
                        `;
                        no++;                        
                        totDebet += debit;
                        totKredit += kredit;
                    });
                    
                html += "</tbody>";
                html += "<tfoot>";
                    html += "<tr>";
                        let cols = is_multi_cabang == "1" ? 4 : 3;
                        html += "<th colspan='"+cols+"' class='text-end'>TOTAL</th>";
                        html += "<th class='text-end'>"+totDebet.toLocaleString('id-ID')+"</th>";
                        html += "<th class='text-end'>"+totKredit.toLocaleString('id-ID')+"</th>";
                    html += "</tr>";
                html += "</tfoot>";
            html += "</table>";
            html += "<div>";
            $("#DetailTransaksiBody").html(html);
            modal.modal("show");
            console.log(totKredit);
        },
        error: function (err) {
            console.error(err);
            alert('Gagal mengambil data jurnal.');
        }
    });

    
}
@endcanAccess
function parseIDR(val) {
    if (val === null || val === undefined) return 0;

    if (typeof val === 'number') return val;

    val = val.toString().trim();

    // format: 1.000.000,00 (ID)
    if (val.includes(',') && val.includes('.')) {
        val = val.replace(/\./g, '').replace(',', '.');
    }
    // format: 1,000,000 (EN)
    else if (val.includes(',')) {
        val = val.replace(/,/g, '');
    }

    return parseFloat(val) || 0;
}

@canAccess('penyesuaian.delete')
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
                url: "{{ route('jurnal.delete', ':id') }}".replace(':id', id),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE"
                },
                success: function(response) {
                    Swal.fire('Deleted!', response.message, 'success');
                    $('#tb_data').DataTable().ajax.reload(null, false);
                },
                error: function(err) {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                }
            });
        }
    });
}
@endcanAccess
@canAccess('penyesuaian.posting')
function posting(id){
    Swal.fire({
        title: 'Posting Jurnal?',
        text: 'Setelah diposting, jurnal tidak bisa diubah atau dihapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Posting Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('jurnal.posting') }}",
                type: "POST",
                data: {
                    id: id,
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Berhasil!', res.message, 'success');
                        $('#tb_data').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Gagal!', res.message || 'Posting gagal', 'error');
                    }
                },
                error: function (xhr) {
                    let message = 'Terjadi kesalahan server';

                    if (xhr.status === 419) {
                        message = 'Sesi habis, silakan refresh halaman';
                    } else if (xhr.status === 403) {
                        message = 'Anda tidak punya akses';
                    } else if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Error!', message, 'error');
                }
            });
            
        }
    });
}
@endcanAccess
@canAccess('penyesuaian.unposting')
function unposting(id){
    Swal.fire({
        title: 'Batalkan Posting?',
        text: 'Jurnal akan kembali ke status draft.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Unposting',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("{{ route('jurnal.unposting') }}", {id: id, _token: '{{ csrf_token() }}'}, function(res) {
                if (res.status) {
                    Swal.fire('Berhasil!', res.message, 'success');
                    $('#tb_data').DataTable().ajax.reload();
                } else {
                    console.log(res);
                    error_message(res,'Proses Data Error');
                }
            });
        }
    });
}
@endcanAccess
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
        width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        // dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
@endif
@canAccess('penyesuaian.view')
// DataTables
function load_data() {
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('jurnal.penyesuaian') }}",
            data: function (d) {
                d.from = $('#filter_from').val(); // kirim periode ke backend
                d.to = $('#filter_to').val(); // kirim periode ke backend
                @if(auth()->user()->level != "entitas")
                    d.entitas_id = $("#filter_entitas").val();
                @else
                    d.entitas_id = null;
                @endif
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            @canAccess('penyesuaian.posting|penyesuaian.unposting|penyesuaian.edit|penyesuaian.delete|penyesuaian.view')
            { data: 'aksi', name: 'aksi', orderable:false, searchable:false },
            @endcanAccess   
            { data: 'kode_jurnal', name: 'kode_jurnal' },
            { data: 'tanggal', name: 'tanggal' },
            { 
                data: 'total_kredit', 
                name: 'total_kredit',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(data));
                },orderable:false, 
            },
            { data: 'entitas', name: 'entitas',orderable:false, },
            { data: 'partner', name: 'partner',orderable:false, },
            { data: 'cabang', name: 'cabang',orderable:false, },
            { data: 'status', name: 'status',orderable:false,  },
            { data: 'keterangan', name: 'keterangan',orderable:false, }
            
        ],
        drawCallback: function(settings) {
            let api = this.api();
            let json = api.ajax.json(); // Mengambil data tambahan dari server
            
            if (json.totalFooter) {
                let res = json.totalFooter;
                let format = new Intl.NumberFormat('id-ID');

                $(api.column(4).footer()).html("Rp. "+format.format(res.total_kredit));
            }
        },
        // order: [[2, 'desc']],
    });
    // Init tooltip setiap setelah table redraw
    $('#tb_data').on('draw.dt', function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Init pertama kali
    $('[data-toggle="tooltip"]').tooltip();
}
@endcanAccess
</script>
@endsection