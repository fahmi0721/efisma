@extends('layouts.app')
@section('title','Update Data Partner')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Update Data Partner</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('partner') }}">Partner</a></li>
            <li class="breadcrumb-item active" aria-current="page">Update</li>
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
                <div class="card-header"><div class="card-title">Update Data Partner</div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)'  id="form_data">
                    @csrf
                    @method("put")
                    <input type="hidden" value="{{ $id }}" id='id' name='id'>
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="nama_partner" class="col-sm-3 col-form-label">Nama Partner <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="nama_partner" value="{{ $data->nama }}" name="nama_partner" placeholder="Nama Partner" />
                            </div>
                        </div>
                        @if (auth()->user()->level != "entitas")
                        <div class="row mb-3">
                            <label for="entitas" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="entitas" id="entitas" class="form-control entitas">
                                    <option value="">-- Pilih Entitas --</option>
                                </select>
                            </div>
                        </div>
                        @endif

                        <div class="row mb-3">
                            <label for="alamat" class="col-sm-3 col-form-label">Alamat</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="alamat" value="{{ $data->alamat }}" name="alamat" placeholder="Alamat" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="no_telepon" class="col-sm-3 col-form-label">No Telepon</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="no_telepon" value="{{ $data->no_telpon }}" name="no_telepon" placeholder="No Telepon" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="vendor" class="col-sm-3 col-form-label">Vendor</label>
                            <div class="col-sm-9">
                                <input type="checkbox" id="vendor" @if($data->is_vendor == 'active') checked @endif name="vendor" value='active' />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="customer" class="col-sm-3 col-form-label">Customer</label>
                            <div class="col-sm-9">
                                <input type="checkbox" id="customer" @if($data->is_customer == 'active') checked @endif name="customer" value='active' />
                            </div>
                        </div>
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('partner') }}"  class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
                      <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end"><i class="fa fa-save"></i> Simpan</button>
                  </div>
                    <!--end::Footer-->
                </form>
                <!--end::Form-->
            </div>  
        </div>
    </div>    
</div>
@endsection
@section('js')
<script>
    $(document).ready(function(){
        @if (auth()->user()->level != "entitas")
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
    });
    @if (auth()->user()->level != "entitas")
    @if(!empty($entitas_id))
        var entitas_id = "{{ $entitas_id->id }}";
        var option = new Option("{{ $entitas_id->id }} - {{ $entitas_id->nama }}", {{ $entitas_id->id }}, true, true);
        $(".entitas").append(option).trigger('change');    
    @endif
    @endif
    proses_data = function(){
        let iData = $("#form_data").serialize();
        var id = $("#id").val();
        $.ajax({
            type    : "POST",
            url     : "{{ route('partner.update', ':id') }}".replace(':id', id),
            data    : iData,
            cache   : false,
            beforeSend  : function (){
                $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..")
                $("#btn-submit").prop("disabled",true);
            },
            success: function(result){
                console.log(result)
                if(result.status == "success"){
                    position = "bottom-left";
                    icons = result.status;
                    pesan = result.messages;
                    title = "Updated!";
                    info(title,pesan,icons,position);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    setTimeout(() => {
                        window.location.href = "{{ route('partner') }}";
                    }, 2000);
                    
                }
            },
            error: function(e){
                console.log(e)
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                $("#btn-submit").prop("disabled",false);
                error_message(e,'Server Error!');
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

