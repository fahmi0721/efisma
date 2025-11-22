@extends('layouts.app')
@section('title','Import Saldo Awal Akun GL')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Import Saldo Awal Akun GL</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('saldo_awal') }}">Saldo Awal Akun GL</a></li>
            <li class="breadcrumb-item active" aria-current="page">Import</li>
        </ol>
        </div>
    </div>
    <!--end::Row-->
    </div>
    <!--end::Container-->
</div>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-8">
            <div class="card card-success card-outline mb-4">
                <div class="card-header d-flex  align-items-center">
                    <div class="card-title">Import Saldo Awal Akun GL</div>
                    <div class='ms-auto'>
                        <a title='Download template excel' data-bs-toggle='tooltip' href="{{ route('saldo_awal.template') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i> Dwonload Template
                        </a>
                    </div>
                </div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)' enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                        <label for="file" class="col-sm-3 col-form-label">File</label>
                            <div class="col-sm-9">
                                <div class="input-group mb-3">
                                <input type="file" class="form-control" accept='.xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"' name='file' placeholder='File' id="file" />
                                <label class="input-group-text" for="logo">Upload</label>
                            </div>
                        </div>
                        </div>

                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('saldo_awal') }}"  class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
                      <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end"><i class="fa fa-save"></i> Simpan</button>
                  </div>
                    <!--end::Footer-->
                </form>
                <!--end::Form-->
            </div>  
        </div>
    </div>    
</div>

<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Terjadi Kesalahan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="errorModalBody">
        <!-- isi error nanti diisi dari JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection
@section('js')
<script>

    $(document).ready(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    })
    proses_data = function(){
        let iData = new FormData(document.getElementById("form_data"));
        $.ajax({
            type    : "POST",
            url     : "{{ route('saldo_awal.import') }}",
            data    : iData,
            cache   : false,
            processData: false,
            contentType: false,
            beforeSend  : function (){
                $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..")
                $("#btn-submit").prop("disabled",true);
            },
            success: function(result){
                if(result.status == "success"){
                    icons = result.status;
                    pesan = result.messages;
                    title = "Saved!";
                    info(title,pesan,icons);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    setTimeout(() => {
                        window.location.href = "{{ route('saldo_awal') }}";
                    }, 2000);
                    
                }else{
                    icons = result.status;
                    pesan = result.messages;
                    title = "Import File Error!";
                    // info(title,pesan,icons);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    let errorHtml = '';
                    if (Array.isArray(result.detail)) {
                        result.detail.forEach(function (err) {
                            errorHtml += `<p class="text-danger mb-1">${err.message}</p>`;
                        });
                    } else {
                        errorHtml += `<p class="text-danger mb-1">${result.detail}</p>`;
                    }
                    

                    $('#errorModalBody').html(errorHtml);
                    $('#errorModal').modal('show');
                }
            },
            error: function(e){
                console.log(e)
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                $("#btn-submit").prop("disabled",false);
                error_message(e,'Proses Data Error');
            }
        })
    }

    $(function() {
        $("#form_data").submit(function(e){
            e.preventDefault();
            proses_data();
        });
    });
</script>
@endsection

