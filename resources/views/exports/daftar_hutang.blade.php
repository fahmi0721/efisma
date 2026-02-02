<table>
    <thead>
        <tr>
            <th colspan="9" style="text-align:center; font-weight:bold; font-size:14pt">
                LAPORAN DAFTAR HUTANG PER INVOICE
            </th>
        </tr>
        <tr><th colspan="9"></th></tr>
        <tr style="font-weight:bold; background:#d9ead3;">
            <th>No</th>
            <th>Tanggal</th>
            <th>Kode Jurnal</th>
            <th>Partner</th>
            <th>Umur Hutang (hari)</th>
            <th class="text-end">Total Tagihan</th>
            <th class="text-end">Total Pelunasan</th>
            <th class="text-end">Sisa Hutang</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($data as $row)
        <tr>
            <td>{{ $no++ }}</td>
            <td>{{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d F Y') }}</td>
            <td>{{ $row->kode_jurnal }}</td>
            <td>{{ $row->partner_nama }}</td>
            <td class="text-center">{{ $row->umur_hutang }}</td>
            <td style="text-align:right">{{ $row->total_tagihan }}</td>
            <td style="text-align:right">{{ $row->total_pelunasan }}</td>
            <td style="text-align:right; font-weight:bold">
                {{ $row->sisa_hutang }}
            </td>
        </tr>
        @endforeach

        <tr style="font-weight:bold; background:#f0f0f0;">
            <td colspan="5" style="text-align:right">TOTAL</td>
            <td style="text-align:right">
                {{ $data->sum('total_tagihan') }}
            </td>
            <td style="text-align:right">
                {{ $data->sum('total_pelunasan') }}
            </td>
            <td style="text-align:right">
                {{ $data->sum('sisa_hutang') }}
            </td>
        </tr>
    </tbody>
</table>
