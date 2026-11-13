@extends('layouts.app')
@section('title','Konfigurasi Massal Saldo Awal')

@section('content')
<div class="container-fluid py-3">

    <h2>Konfigurasi Massal Saldo Awal</h2>
    <p class="text-muted">
        Atur metode saldo dan perlakuan tahun buku
        untuk beberapa akun sekaligus.
    </p>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ implode(' ', $errors->all()) }}
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('akuntansi.saldo-setting-bulk.index') }}">

                <label class="form-label">Entitas</label>

                <div class="row g-2">
                    <div class="col-md-9">
                        <select name="entitas_id"
                                class="form-select" required>

                            <option value="">Pilih Entitas</option>

                            @foreach($entitas as $e)
                                <option value="{{ $e->id }}"
                                    @selected($entitasId == $e->id)>
                                    {{ $e->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-primary w-100">
                            Tampilkan
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    @if($entitasId)

    <form method="POST"
          action="{{ route('akuntansi.saldo-setting-bulk.update') }}"
          id="bulkForm">

        @csrf

        <input type="hidden"
               name="entitas_id"
               value="{{ $entitasId }}">

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Pengaturan Massal</h3>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">
                            Metode Saldo
                        </label>

                        <select name="metode_saldo"
                                class="form-select" required>
                            <option value="">Pilih</option>
                            <option value="akun">Per Akun</option>
                            <option value="partner">Per Partner</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Perlakuan Tahun Buku
                        </label>

                        <select name="perlakuan_tahun_buku"
                                class="form-select" required>
                            <option value="">Pilih</option>
                            <option value="carry_forward">
                                Carry Forward
                            </option>
                            <option value="reset_tahunan">
                                Reset Tahunan
                            </option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    Pilih Akun Transaksi
                </h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox"
                                       id="selectAll">
                            </th>
                            <th>No. Akun</th>
                            <th>Nama Akun</th>
                            <th>Saldo Normal</th>
                            <th>Metode Existing</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($akuns as $akun)
                            @php
                                $locked = $akun->memiliki_saldo
                                    || $akun->metode_saldo !== null;
                            @endphp

                            <tr>
                                <td>
                                    <input type="checkbox"
                                           name="akun_ids[]"
                                           value="{{ $akun->id }}"
                                           class="akun-checkbox"
                                           @disabled($locked)>
                                </td>

                                <td>{{ $akun->no_akun }}</td>
                                <td>{{ $akun->nama }}</td>
                                <td>{{ $akun->saldo_normal }}</td>

                                <td>
                                    {{ $akun->metode_saldo ?? '-' }}
                                </td>

                                <td>
                                    @if($akun->memiliki_saldo)
                                        <span class="badge bg-warning text-dark">
                                            Ada Saldo Existing
                                        </span>
                                    @elseif($akun->metode_saldo)
                                        <span class="badge bg-info">
                                            Sudah Diatur
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Belum Diatur
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer text-end">
                <button type="submit"
                        class="btn btn-primary">
                    Simpan Pengaturan Massal
                </button>
            </div>

        </div>
    </form>

    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');

    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        document.querySelectorAll(
            '.akun-checkbox:not(:disabled)'
        ).forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
    });

    const form = document.getElementById('bulkForm');

    form.addEventListener('submit', function (event) {
        const checked = document.querySelectorAll(
            '.akun-checkbox:checked'
        );

        if (checked.length === 0) {
            event.preventDefault();
            alert('Pilih minimal satu akun transaksi.');
            return;
        }

        if (!confirm(
            'Simpan konfigurasi untuk ' +
            checked.length + ' akun?'
        )) {
            event.preventDefault();
        }
    });
});
</script>
@endpush