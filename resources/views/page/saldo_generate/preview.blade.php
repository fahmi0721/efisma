
@php
    $rows = $hasil['rows'] ?? [];

    $metode = $hasil['metode_saldo'] ?? '-';
    $perlakuan = $hasil['perlakuan_tahun_buku'] ?? '-';

    $labelMetode = [
        'akun' => 'Per Akun',
        'partner' => 'Per Partner',
    ][$metode] ?? $metode;

    $labelPerlakuan = [
        'carry_forward' => 'Carry Forward',
        'reset_tahunan' => 'Reset Tahunan',
    ][$perlakuan] ?? $perlakuan;
@endphp

<div class="card card-outline card-primary mb-3">

    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calculator mr-2"></i>
            Hasil Preview Saldo Awal
        </h3>

        <div class="card-tools">
            <span class="badge badge-info">
                Preview
            </span>
        </div>
    </div>

    <div class="card-body">

        {{-- Informasi Parameter --}}
        <div class="row mb-3">

            <div class="col-md-3 col-sm-6 mb-2">
                <div class="text-muted small">Periode</div>
                <strong>
                    {{ $hasil['periode'] ?? '-' }}
                </strong>
            </div>

            <div class="col-md-3 col-sm-6 mb-2">
                <div class="text-muted small">Entitas Nama</div>
                <strong>
                    {{ $hasil['entitas_nama'].'('.$hasil['entitas_id'].')' ?? '-' }}
                </strong>
            </div>

            <div class="col-md-3 col-sm-6 mb-2">
                <div class="text-muted small">Metode Saldo</div>
                <span class="badge bg-primary">
                    {{ $labelMetode }}
                </span>
            </div>

            <div class="col-md-3 col-sm-6 mb-2">
                <div class="text-muted small">Perlakuan Tahun Buku</div>
                <span class="badge bg-secondary">
                    {{ $labelPerlakuan }}
                </span>
            </div>

        </div>

        <hr>

        {{-- Tabel Preview --}}
        <div class="table-responsive">

            <table id="tablePreviewSaldo"
                   class="table table-bordered table-striped table-hover table-sm">

                <thead class="thead-light">
                    <tr>
                        <th width="50">No.</th>
                        <th>No. Akun</th>
                        <th>Nama Akun</th>
                        <th>Partner</th>
                        <th class="text-right">Saldo Awal</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                        <th class="text-right">Saldo Akhir</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <strong>
                                    {{ $row['no_akun'] }}
                                </strong>
                            </td>

                            <td>
                                {{ $row['nama_akun'] }}
                            </td>

                            <td>
                                {{ $row['partner_nama'].'('.$row['partner_id'].')' ?? '-' }}
                            </td>

                            <td class="text-right">
                                {{ number_format((float)($row['saldo'] ?? 0), 2, ',', '.') }}
                            </td>

                            <td class="text-right">
                                {{ number_format((float)($row['total_debit'] ?? 0), 2, ',', '.') }}
                            </td>

                            <td class="text-right">
                                {{ number_format((float)($row['total_kredit'] ?? 0), 2, ',', '.') }}
                            </td>

                            <td class="text-right font-weight-bold">
                                {{ number_format((float)($row['saldo_akhir'] ?? 0), 2, ',', '.') }}
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8"
                                class="text-center text-muted py-4">
                                Tidak ada data saldo yang dapat dihitung.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>
    </div>

</div>


@php
    $periodeBerikutnya = \Carbon\Carbon::createFromFormat(
            '!Y-m',
            $hasil['periode']
        )->addMonth()->format('Y-m');
    @endphp

    <div class="alert alert-info mb-0">
        <h6 class="font-weight-bold mb-2">
            <i class="fas fa-info-circle mr-1"></i>
            Informasi Generate Saldo Bulanan
        </h6>

        <p class="mb-2">
            Preview ini belum melakukan perubahan data.
            Nilai debit, kredit, dan saldo akhir dihitung
            berdasarkan mutasi Buku Besar.
        </p>

        <p class="mb-0">
            Jika Generate dijalankan, sistem akan menyimpan
            hasil perhitungan periode
            <strong>{{ $hasil['periode'] }}</strong>
            dan membentuk saldo awal periode
            <strong>{{ $periodeBerikutnya }}</strong>
            dari saldo akhir periode berjalan.
        </p>
    </div>