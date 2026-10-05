<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataMagang;
use App\Models\Departemen;
use App\Models\SesiPresensi;
use App\Models\User;
use App\Services\ImporDataMagang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PesertaKelolaController extends Controller
{
    public function import(Request $r)
    {
        $r->validate(['file'=>'required|file|mimes:xlsx,xls,csv|max:5120']);

        $hasil=app(ImporDataMagang::class)->jalankan($r->file('file')->getRealPath());

        return back()
            ->with('success',"Import selesai: {$hasil['baru']} peserta baru, {$hasil['diperbarui']} diperbarui, {$hasil['tetap']} tetap.")
            ->with('import_errors',array_merge($hasil['galat'],$hasil['peringatan']));
    }

    public function template()
    {
        $ss=new Spreadsheet();
        $sheet=$ss->getActiveSheet();
        $headers=['NIS/NIM','NAMA','UNIV/SEKOLAH','PRODI','DEPARTEMEN','DIVISI','UUID','L/P','TGL_MULAI','TGL_SELESAI','KET','TEMPAT PRAKERIN'];
        $sheet->fromArray($headers,null,'A1');
        $sheet->fromArray(['MN3-2026-001','Contoh Peserta','SMK Contoh','Teknik Informatika','IT','Development','', 'L','','','','PT Mekar Armada Jaya'],null,'A2');

        return response()->streamDownload(function() use($ss){
            (new Xlsx($ss))->save('php://output');
        },'template-import-peserta.xlsx',[
            'Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    public function destroy(User $peserta)
    {
        abort_unless($peserta->peran==='peserta',404);

        $data=$peserta->dataMagang;
        foreach([$data?->foto_pas,$data?->cv_path,$data?->surat_permohonan_path] as $file){
            if($file) Storage::disk('public')->delete($file);
        }

        $nama=$peserta->name;
        $peserta->delete();
        if($data) $data->delete();

        return back()->with('success',"Peserta {$nama} dihapus.");
    }
}
