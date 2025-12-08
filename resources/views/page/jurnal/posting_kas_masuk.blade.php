@extends('layouts.app')
@section('title','Posting Jurnal Kas Masuk')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Posting Jurnal Kas Masuk</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('jurnal.kasmasuk') }}">Jurnal Kas Masuk</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Posting Jurnal Kas Masuk</li>
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
                <div class="card-header"><div class="card-title">Posting Jurnal Kas Masuk</div></div>
                
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                    <input type="hidden" name='jenis' value="JKM">
                    <input type="hidden" name='status' value="draft">
                    <div class="card-body">
                        <!-- 🔄 Ganti Tahun → Periode -->
                        <div class="row mb-3">
                            @if(auth()->user()->level != "entitas")
                            <div class="col-sm-3">
                                <select id="filter_entitas" name="entitas_id" class="form-control form-select"> 
                                    <option value="">Semua Entitas</option>
                                </select>
                            </div>
                            @endif
                            <div class="col-sm-6">
                                <div class="input-group mb-3">
                                    <input type="text" id="tanggal_awal" name="tanggal_awal" class="form-control" 
                                       placeholder="Pilih Tanggal Awal (YYYY-MM-DD)" readonly>
                                    <label class="input-group-text" for="tanggal_akhir"><i class='fa fa-calendar'></i></label>
                                    <input type="text" id="tanggal_akhir" name="tanggal_akhir" class="form-control" 
                                       placeholder="Pilih Tanggal Akhir (YYYY-MM-DD)" readonly>
                                    <label class="input-group-button" for="tanggal_akhir"><button type='button' id="btnFilter" class='btn btn-outline-primary'><i class='fa fa-search'></i> Search</button></label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <table id='t_data' class='table table-bordered table-striped dt-responsive nowrap'>
                        <thead>
                            <tr class="text-center">
                                <th width="5%">No</th>
                                <th>Kode</th>
                                <th>Keterangan</th>
                                <th>Tanggal</th>
                                <th>Entitas</th>
                                <th>Partner</th>
                                <th>Cabang</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end fw-bold">TOTAL :</th>
                                <th id="footer_total_debit" class="text-end fw-bold"></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="card-footer">
                        <a href="{{ route('jurnal.kasmasuk') }}" class="btn btn-danger btn-flat btn-sm">
                            <i class="fa fa-mail-reply"></i> Kembali
                        </a>
                        <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end">
                            <i class="fa fa-bolt"></i> Posting
                        </button>
                    </div>
                </form>
            </div>  
        </div>
    </div>    
</div>
<!-- Modal Progress Posting -->
<div class="modal fade" id="modalProgress" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title" id="progressModalLabel"><i class="fa fa-spinner"></i> Proses Posting</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-2 text-muted small">Mohon tunggu, sistem sedang memproses data jurnal...</p>
        <div class="progress" style="height: 25px;">
          <div id="progressBar" 
               class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
               style="width: 0%">0%</div>
        </div>
        <p id="progressText" class="mt-2 small text-secondary">0 dari 0 batch</p>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')

<script>
$(document).ready(function() {
    load_data();
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

     @if(auth()->user()->level != "entitas")
    // 🔽 Select2 Entitas
        $('#filter_entitas').select2({
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
            width: '100%',
            // placeholder: "-- Pilih Entitas --",
            // allowClear: true
        });
    @endif

    // 🧠 Submit Form
    $("#form_data").submit(function(e){
        e.preventDefault();
        proses_data();
    });
});

// Filter tombol klik
$('#btnFilter').on('click', function() {
    $('#t_data').DataTable().ajax.reload();
});

function load_data(){
    $('#t_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('jurnal.kasmasuk.posting') }}",
            data: function (d) {
                d.tanggal_awal = $('#tanggal_awal').val(); // kirim periode ke backend
                d.tanggal_akhir = $('#tanggal_akhir').val(); // kirim periode ke backend
                d.entitas_id = $('#filter_entitas').val(); // kirim periode ke backend
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'kode_jurnal', name: 'kode_jurnal' },
            { data: 'tanggal', name: 'tanggal' },
            { 
                data: 'total_debit', 
                name: 'total_debit',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
            { data: 'entitas', name: 'entitas', orderable:false },
            { data: 'partner', name: 'partner', orderable:false },
            { data: 'cabang', name: 'cabang', orderable:false },
            
            { data: 'status', name: 'status', orderable:false },
            { data: 'keterangan', name: 'keterangan', orderable:false },
            { data: 'detail', name: 'detail', orderable:false, searchable:false }
        ],
        // FOOTER TOTAL
        footerCallback: function (row, data, start, end, display) {

            let api = this.api();

            let toNumber = function (value) {

                if (!value) return 0;

                let cleaned = value.toString();

                // buang decimal .00
                if (cleaned.includes(".")) {
                    cleaned = cleaned.split(".")[0];
                }

                // buang semua selain angka
                cleaned = cleaned.replace(/[^\d]/g, "");

                return Number(cleaned) || 0;
            };

            let totalDebit = api
                .column(7)
                .data()
                .reduce(function (a, b) {
                    return toNumber(a) + toNumber(b);
                }, 0);

            // tampilkan
            $('#footer_total_debit').html(
                new Intl.NumberFormat('id-ID', { 
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0 
                }).format(totalDebit)
            );
        }
        // order: [[2, 'desc']],
    });
    // Init tooltip setiap setelah table redraw
    $('#t_data').on('draw.dt', function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Init pertama kali
    $('[data-toggle="tooltip"]').tooltip();
}



function proses_data() {
    let iData = new FormData(document.getElementById("form_data"));
    let modalProgress = new bootstrap.Modal(document.getElementById('modalProgress'), { backdrop: 'static', keyboard: false });

    // Tampilkan modal progress
    // modalProgress.show();
    updateProgress(0, 0);

    $.ajax({
        type: "POST",
        url: "{{ route('jurnal.prepare_batch') }}",
        data: iData,
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function () {
            $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i> Menyiapkan...");
            $("#btn-submit").prop("disabled", true);
        },
        success: function(result){
            console.log("ee")
            if(result.status === "ready"){
                modalProgress.show();
                startBatchPosting(result.total_batch, modalProgress);
            } else {
                modalProgress.hide();
                Swal.fire('Tidak Ada Data', 'Tidak ditemukan jurnal untuk diposting.', 'info');
                $("#btn-submit").html("<i class='fa fa-bolt'></i> Posting");
                $("#btn-submit").prop("disabled", false);
            }
        },
        error: function(e){
            $("#btn-submit").html("<i class='fa fa-bolt'></i> Posting");
            $("#btn-submit").prop("disabled", false);
            error_message(e,'Proses Data Error');
            modalProgress.hide();
        }
    });
}

function updateProgress(current, total) {
    let percent = total > 0 ? Math.round((current / total) * 100) : 0;
    $("#progressBar").css("width", percent + "%").text(percent + "%");
    $("#progressText").text(current + " dari " + total + " batch");
}

function startBatchPosting(totalBatch, modalProgress) {
    let current = 0;
    function nextBatch(){
        $.ajax({
            type: "POST",
            url: "{{ route('jurnal.posting_batch') }}",
            data: { batch: current, _token: "{{ csrf_token() }}" },
            success: function(){
                current++;
                updateProgress(current, totalBatch);

                if(current < totalBatch){
                    nextBatch();
                } else {
                    $("#progressBar")
                        .removeClass("bg-success")
                        .addClass("bg-primary")
                        .text("Selesai!");

                    $("#progressText").text("Semua batch berhasil diposting.");
                    setTimeout(() => {
                        modalProgress.hide();
                        Swal.fire('Sukses', 'Semua jurnal berhasil diposting ke buku besar.', 'success');
                        $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
                        $("#btn-submit").prop("disabled", false);
                        $('#t_data').DataTable().ajax.reload();
                    }, 1000);
                }
            },
            error: function(err){
                $("#progressBar").removeClass("bg-success").addClass("bg-danger").text("Gagal!");
                $("#progressText").text("Terjadi kesalahan pada batch ke-" + (current + 1));
                modalProgress.hide();
                Swal.fire('Error', 'Gagal memproses batch: ' + err.responseText, 'error');
            }
        });
    }
    nextBatch();
}
</script>
@endsection
