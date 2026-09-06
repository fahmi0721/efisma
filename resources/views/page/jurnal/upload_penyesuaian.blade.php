@extends('layouts.app')
@section('title','Upload Jurnal Rupa-Rupa')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Upload Jurnal Rupa-Rupa</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('jurnal.penyesuaian') }}">Jurnal Rupa-Rupa</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Upload Jurnal Rupa-Rupa</li>
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
                    <h5 class="mb-0">Upload Jurnal Rupa-Rupa</h5>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                Download Template
                            </button>

                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0)" onclick="get_template('piutang')">
                                    <!-- <i class='fa fa-file-excel'></i> Pelunasan Piutang
                                </a>

                                <a class="dropdown-item" href="javascript:void(0)" onclick="get_template('uang_muka')">
                                    <i class='fa fa-file-excel'></i> Pertanggung Jawaban Uang Muka
                                </a> -->
                                <a class="dropdown-item" href="javascript:void(0)" onclick="get_template('acs')">
                                    <i class='fa fa-file-excel'></i> Jurnal ACS
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                    <input type="hidden" name="is_multi_cabang" id="is_multi_cabang" value='0'>
                    <div class="card-body">
                        
                        @if(auth()->user()->level != "entitas")
                        <div class="row mb-3">
                            <label for="entitas_id" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="entitas_id" id="entitas_id" class="form-control entitas">
                                    <option value="">-- Pilih Entitas --</option>
                                </select>
                            </div>
                        </div>
                        @endif

                        <div class="row mb-3">
                            <label for="jenis_upload" class="col-sm-3 col-form-label">Jenis Upload <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="jenis_upload" id="jenis_upload" class="form-control jenis_upload">
                                    <option value="">-- Pilih Jenis Upload --</option>
                                    <option value="acs">Jurnal ACS</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="files" class="col-sm-3 col-form-label">File Import <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="file"  class="form-control" id="file" name="file" placeholder="File Import" />
                            </div>
                        </div>
                        
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('jurnal.penyesuaian') }}" class="btn btn-danger btn-flat btn-sm">
                            <i class="fa fa-mail-reply"></i> Kembali
                        </a>
                        <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end">
                            <i class="fa fa-save"></i> Upload
                        </button>
                    </div>
                </form>
                
            </div>  
        </div>
    </div>    
</div>


@endsection

@section('js')

<script>
$("#form_data").submit(function(e){
    e.preventDefault();
    proses_data();
});
$('.jenis_upload').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: "-- Pilih Jenis Upload --",
    allowClear: true
});
@if(auth()->user()->level != "entitas")
// 🔽 Select2 Entitas
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
    width: '100%',
    placeholder: "-- Pilih Entitas --",
    allowClear: true
});
@endif

function proses_data(){
    let iData = new FormData(document.getElementById("form_data"));
    $.ajax({
        type: "POST",
        url: "{{ route('jurnal.penyesuaian.upload.save') }}",
        data: iData,
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function (){
            $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..");
            $("#btn-submit").prop("disabled", true);
        },
        success: function(result){
            console.log(result);
            if(result.status == "success"){
                position = "bottom-left";
                icons = result.status;
                pesan = result.message;
                title = "Uploaded!";
                info(title, pesan, icons, position);
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
                $("#btn-submit").prop("disabled", false);
                setTimeout(() => {
                    window.location.href = "{{ route('jurnal.penyesuaian') }}";
                }, 1500);
            }
        },
        error: function(e){
            console.log(e);
            $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
            $("#btn-submit").prop("disabled", false);
            error_message(e,'Proses Data Error');
        }
    });
}


function get_template(jenis){
    @if(auth()->user()->level != "entitas")
        var entitas_id = $(".entitas").val();
        if(entitas_id == ""){
            Swal.fire("Perhatian!", "Entitas Wajib dipilih!", "warning");
        }else{
            window.open("{{ route('jurnal.penyesuaian.template') }}?jenis="+jenis+"&entitas_id="+entitas_id, '_blank');
        } 
    @else
      window.open("{{ route('jurnal.penyesuaian.template') }}", '_blank');
    @endif
}


</script>
@endsection
