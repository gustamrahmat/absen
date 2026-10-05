<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SesiPresensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PresensiExportController extends Controller
{
    public function download(Request $r)
    {
        $v=$r->validate([
            'dari'=>'nullable|date',
            'sampai'=>'nullable|date|after_or_equal:dari',
            'departemen'=>'nullable|string',
        ]);

        $dari=$v['dari']??now()->toDateString();
        $sampai=$v['sampai']??$dari;

        $users=User::with('departemen')->where('peran','peserta')
            ->when($v['departemen']??null,fn($q,$dep)=>$q->whereHas('departemen',fn($d)=>$d->where('nama',$dep)))
            ->orderBy('name')->get();

        $sesi=SesiPresensi::with('laporanHarian')
            ->whereBetween('tanggal',[$dari,$sampai])
            ->whereIn('user_id',$users->pluck('id'))
            ->orderBy('tanggal')->get()->groupBy(fn($s)=>$s->user_id.'|'.$s->tanggal->toDateString());

        $ss=new Spreadsheet();
        $sh=$ss->getActiveSheet();
        $sh->setTitle('Rekap Presensi');
        $headers=['Tanggal','Nomor Induk','Nama','Departemen','Jam Masuk','Jam Pulang','Status Pagi','Status Sore','Status Persetujuan','Laporan'];
        $sh->fromArray($headers,null,'A1');

        $row=2;
        $period=new \DatePeriod(new \DateTime($dari),new \DateInterval('P1D'),(new \DateTime($sampai))->modify('+1 day'));
        foreach($period as $date){
            $tgl=$date->format('Y-m-d');
            foreach($users as $u){
                $items=$sesi->get($u->id.'|'.$tgl,collect());
                $p=$items->firstWhere('sesi','pagi');
                $s=$items->firstWhere('sesi','sore');
                $sh->fromArray([[
                    $tgl,$u->nomor_induk,$u->name,optional($u->departemen)->nama,
                    $p?->waktu_absen?->format('H:i'),
                    $s?->waktu_absen?->format('H:i'),
                    $p?->status,$s?->status,
                    $s?->status_persetujuan,
                    $s?->laporanHarian?->laporan
                ]],null,'A'.$row);
                $row++;
            }
        }

        foreach(range('A','J') as $col) $sh->getColumnDimension($col)->setAutoSize(true);

        $nama='rekap-presensi-'.$dari.'_sd_'.$sampai.'.xlsx';
        return response()->streamDownload(function() use($ss){(new Xlsx($ss))->save('php://output');},$nama,[
            'Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
