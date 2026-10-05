<?php

namespace App\Services;

use App\Models\DataMagang;
use App\Models\Departemen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImporDataMagang
{
    private const KOLOM = [
        'nomorInduk'=>['NIS/NIM','NIM/NIS','NIM/NISN','NISN/NIM','NIM','NISN','NIS','NOMOR INDUK'],
        'nama'=>['NAMA','NAMA LENGKAP'],
        'asalSekolah'=>['UNIV/SEKOLAH','SEKOLAH/UNIV','ASAL SEKOLAH','SEKOLAH','UNIVERSITAS'],
        'jurusan'=>['PRODI','JURUSAN','PROGRAM STUDI'],
        'departemen'=>['DEPARTEMEN','DIVISI'],
        'divisi'=>['DIVISI'],
        'uuid'=>['UUID'],
        'jenisKelamin'=>['L/P','JENIS KELAMIN','JK'],
        'tglMulai'=>['TGL_MULAI','TANGGAL MULAI','TGL MULAI'],
        'tglSelesai'=>['TGL_SELESAI','TANGGAL SELESAI','TGL SELESAI'],
        'keterangan'=>['KET','KETERANGAN'],
        'tempatPrakerin'=>['TEMPAT PRAKERIN','TEMPAT_PRAKERIN'],
        'statusMagang'=>['STATUS','STATUS MAGANG'],
        'tipeMagang'=>['TIPE MAGANG','TIPE_MAAGNG'],
    ];

    public function jalankan(string $pathFile,bool $hanyaUji=false): array
    {
        $baris=IOFactory::load($pathFile)->getSheet(0)->toArray(null,true,true,false);
        if(count($baris)<2) throw new \InvalidArgumentException('File kosong atau hanya berisi judul kolom.');

        $peta=$this->petakanKolom($baris[0]);
        $hasil=['hanyaUji'=>$hanyaUji,'baru'=>0,'diperbarui'=>0,'tetap'=>0,'uuidBaru'=>0,'departemenBaru'=>[],'peringatan'=>[],'galat'=>[]];
        $duplikat=[]; $cache=[];

        DB::beginTransaction();
        try {
            for($i=1;$i<count($baris);$i++){
                $no=$i+1;
                $ambil=fn($k)=>isset($peta[$k])?Str::squish((string)($baris[$i][$peta[$k]]??'')):'';
                $nim=$ambil('nomorInduk'); $nama=$ambil('nama');
                $asal=$ambil('asalSekolah'); $jurusan=$ambil('jurusan');
                $dep=$ambil('departemen'); $divisi=$ambil('divisi'); $uuid=strtolower($ambil('uuid'));
                if(($nim.$nama.$asal.$jurusan.$dep.$divisi.$uuid)==='') continue;
                if($nim===''||$nama===''){ $hasil['galat'][]="Baris {$no}: NIS/NIM dan NAMA wajib diisi."; continue; }
                $key=strtolower($nim);
                if(isset($duplikat[$key])){ $hasil['galat'][]="Baris {$no}: NIS/NIM {$nim} dobel di file."; continue; }
                $duplikat[$key]=$no;
                if($uuid!==''&&!Str::isUuid($uuid)){ $hasil['galat'][]="Baris {$no}: UUID tidak valid."; continue; }

                $depId=null;
                if($dep!==''){
                    $dk=strtolower($dep);
                    if(!isset($cache[$dk])){
                        $d=Departemen::firstOrCreate(['nama'=>$dep]);
                        if($d->wasRecentlyCreated)$hasil['departemenBaru'][]=$d->nama;
                        $cache[$dk]=$d->id;
                    }
                    $depId=$cache[$dk];
                }

                $jk=$ambil('jenisKelamin');
                $jk=in_array(strtoupper($jk),['L','P'],true)?strtoupper($jk):null;
                $parseDate=function($v){
                    $v=trim((string)$v); if($v==='') return null;
                    try { return is_numeric($v) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d') : date('Y-m-d',strtotime($v)); }
                    catch(\Throwable){ return null; }
                };
                $start=$parseDate($ambil('tglMulai')); $end=$parseDate($ambil('tglSelesai'));

                try {
                    DB::transaction(function() use(&$hasil,$nim,$nama,$asal,$jurusan,$depId,$divisi,$uuid,$jk,$start,$end,$ambil,$no){
                        $rekam=DataMagang::where('nomor_induk',$nim)->first();
                        $nilai=[
                            'nama'=>$nama,'asal_sekolah'=>$asal?:null,'jurusan'=>$jurusan?:null,
                            'departemen_id'=>$depId,'divisi'=>$divisi?:null,'jenis_kelamin'=>$jk,
                            'tgl_mulai'=>$start,'tgl_selesai'=>$end,
                            'keterangan'=>$ambil('keterangan')?:null,
                            'tempat_prakerin'=>$ambil('tempatPrakerin')?:null,
                            'status_magang'=>strtoupper($ambil('statusMagang')?:'ACTIVE'),
                        ];

                        if(!$rekam){
                            $uuidPakai=$uuid!==''?$uuid:(string)Str::uuid();
                            if(DataMagang::where('uuid',$uuidPakai)->exists()){
                                $hasil['galat'][]="Baris {$no}: UUID sudah dipakai peserta lain."; return;
                            }
                            DataMagang::create($nilai+[
                                'nomor_induk'=>$nim,'uuid'=>$uuidPakai,
                                'tipe_magang'=>in_array(strtolower($ambil('tipeMagang')),['kuliah','smk'],true)?strtolower($ambil('tipeMagang')):'smk',
                                'status_kehadiran_awal'=>'hadir'
                            ]);
                            $hasil['baru']++; if($uuid==='')$hasil['uuidBaru']++; return;
                        }

                        if($uuid!=='' && strtolower($rekam->uuid)!==$uuid)
                            $hasil['peringatan'][]="Baris {$no} ({$nim}): UUID Excel berbeda, UUID lama dipertahankan.";

                        $rekam->fill($nilai);
                        if($rekam->isDirty()){ $rekam->save(); $hasil['diperbarui']++; }
                        else $hasil['tetap']++;
                    });
                } catch(\Throwable $e){ $hasil['galat'][]="Baris {$no} ({$nim}): {$e->getMessage()}"; }
            }
            $hanyaUji?DB::rollBack():DB::commit();
        } catch(\Throwable $e){DB::rollBack();throw $e;}

        return $hasil;
    }

    private function petakanKolom(array $judul): array
    {
        $peta=[];
        foreach($judul as $i=>$teks){
            $bersih=strtoupper(Str::squish(str_replace("\xEF\xBB\xBF",'',(string)$teks)));
            foreach(self::KOLOM as $k=>$alias){
                if(!isset($peta[$k])&&in_array($bersih,$alias,true))$peta[$k]=$i;
            }
        }
        if(!isset($peta['nomorInduk'])||!isset($peta['nama'])){
            throw new \InvalidArgumentException('Kolom wajib tidak ditemukan. Dibutuhkan NIS/NIM dan NAMA.');
        }
        return $peta;
    }
}
