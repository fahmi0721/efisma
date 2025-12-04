<table id="tblCashflow" class="table table-bordered table-striped w-100">
        <thead class="bg-light">
            <tr>
                <th>Entitas</th>
                <th>Saldo Awal</th>
                <th>OP Masuk</th>
                <th>OP Keluar</th>
                <th>Operasional</th>
                <th>INV Masuk</th>
                <th>INV Keluar</th>
                <th>Investasi</th>
                <th>PDN Masuk</th>
                <th>PDN Keluar</th>
                <th>Pendanaan</th>
                <th>Kenaikan Kas</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
    <tbody>
        @foreach($data as $row)
            <tr>
                <td>{{ $row['nama_entitas'] }}</td>
                <td>{{ $row['saldo_awal'] }}</td>
                <td>{{ $row['operasional_masuk'] }}</td>
                <td>{{ $row['operasional_keluar'] }}</td>
                <td>{{ $row['operasional'] }}</td>
                <td>{{ $row['investasi_masuk'] }}</td>
                <td>{{ $row['investasi_keluar'] }}</td>
                <td>{{ $row['investasi'] }}</td>
                <td>{{ $row['pendanaan_masuk'] }}</td>
                <td>{{ $row['pendanaan_keluar'] }}</td>
                <td>{{ $row['pendanaan'] }}</td>
                <td>{{ $row['kenaikan_kas'] }}</td>
                <td>{{ $row['saldo_akhir'] }}</td>
            </tr>
        @endforeach
    </tbody>
     <tfoot class="bg-light fw-bold">
        <tr>
            <td class="text-center">GRAND TOTAL</td>
            <td id="gt_saldo_awal"></td>
            <td id="gt_op_masuk"></td>
            <td id="gt_op_keluar"></td>
            <td id="gt_operasional"></td>
            <td id="gt_inv_masuk"></td>
            <td id="gt_inv_keluar"></td>
            <td id="gt_investasi"></td>
            <td id="gt_pdn_masuk"></td>
            <td id="gt_pdn_keluar"></td>
            <td id="gt_pendanaan"></td>
            <td id="gt_kenaikan"></td>
            <td id="gt_saldo_akhir"></td>
        </tr>
    </tfoot>
</table>
