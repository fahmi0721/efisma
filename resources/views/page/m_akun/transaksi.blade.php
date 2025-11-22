@extends('layouts.app')
@section('title','Akun GL Transkasi')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Akun GL Transkasi</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('m_akun') }}">Akun GL</a></li>
            <li class="breadcrumb-item active" aria-current="page">Akun GL Transkasi</li>
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
        <div class="col-12">
            <div class="card card-success card-outline mb-4">
                <div class="card-header d-flex  align-items-center">
                    <h5 class="mb-0">Data Akun GL Transkasi</h5>
                    <div class=' ms-auto'>
                        <a href="{{ route('m_akun') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-mail-reply"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>No Akun</th>
                                <th>Nama Akun</th>
                                <th>Path</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>  
        </div>
    </div>    
</div>
@endsection
@section('js')
<script>
$(document).ready(function() {
    load_data();
});

load_data = function(){
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('m_akun.transaksi') }}",
        columns: [
            { data: 'no_akun', name: 'no_akun', orderable: false, },
            { data: 'nama', name: 'nama', orderable: false, },
            { data: 'full_path', name: 'full_path', orderable: false, },
        ]
    });
    
}
</script>
@endsection

