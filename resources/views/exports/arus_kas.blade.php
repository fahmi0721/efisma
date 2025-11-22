<table>
    <thead>
        <tr>
            <th colspan="5" style="font-weight:bold; font-size:14px;">
                Laporan Arus Kas
            </th>
        </tr>
        <tr>
            <th colspan="5">
                Periode: {{ \Carbon\Carbon::parse($periode . '-01')->translatedFormat('F Y') }}
            </th>
        </tr>
        <tr>
            <th>No Akun</th>
            <th>Nama Akun</th>
            <th>Debit</th>
            <th>Kredit</th>
            <th>Saldo Akhir</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
            <tr>
                <td>{{ $row->no_akun }}</td>
                <td>{!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, $row->level - 1)) !!}{{ $row->akun_nama }}</td>
                <td align="right">{{ $row->total_debit}}</td>
                <td align="right">{{ $row->total_kredit }}</td>
                <td align="right"><strong>{{ $row->saldo_akhir }}</strong></td>
            </tr>
        @endforeach
    </tbody>
     <tfoot>
        <tr class="table-light fw-bold">
            <td colspan="4"  style='text-align:right !important'>Total Pendapatan</td>
            <td id="totalPendapatan" class="text-end">{{ $total_pendapatan }}</td>
        </tr>
        <tr class="table-light fw-bold">
            <td colspan="4" class="text-end">Total Beban</td>
            <td id="totalBeban" class="text-end">{{ $total_beban }}</td>
        </tr>
        <tr class="table-success fw-bold">
            <td colspan="4" class="text-end">Laba / (Rugi) Bersih</td>
            <td id="labaBersih" class="text-end">{{ $laba_bersih }}</td>
        </tr>
    </tfoot>
</table>
