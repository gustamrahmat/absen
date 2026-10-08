<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataMagang;
use App\Models\LogPerubahanStatus;
use App\Models\SesiPresensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PresensiController extends Controller
{
    public function index(Request $r)
    {
        $tanggal = Carbon::parse($r->input('tanggal', today()->toDateString()))->startOfDay();

        // dataMagang di-eager load: dipakai untuk foto dan tanggal mulai/selesai magang.
        $peserta = User::with(['departemen', 'dataMagang'])
            ->where('peran', 'peserta')
            ->when($r->q, fn($q,$v)=>$q->where('name','like',"%{$v}%"))
            ->when($r->departemen, fn($q,$v)=>$q->whereHas('departemen', fn($d)=>$d->where('nama',$v)))
            ->orderBy('name')->get();

        $ids = $peserta->pluck('id');
        $sesi = SesiPresensi::with('laporanHarian')
            ->whereDate('tanggal',$tanggal)
            ->whereIn('user_id',$ids)->get()
            ->groupBy('user_id');

        // Ambil log TERBARU per peserta. latest('id') mengurutkan terbaru lebih dulu,
        // dan unique('user_id') menyimpan yang pertama (terbaru). Jangan memakai keyBy
        // langsung: keyBy menyimpan item TERAKHIR, yaitu log yang paling lama.
        $logs = LogPerubahanStatus::whereDate('tanggal',$tanggal)
            ->whereIn('user_id',$ids)->latest('id')->get()->unique('user_id')->keyBy('user_id');

        // Rekap kehadiran satu bulan (bulan dari tanggal terpilih), mengikuti filter nama & departemen.
        $rekap = $this->rekapBulanan($peserta, $tanggal);

        $rows = $peserta->map(fn(User $u)=>$this->payload(
            $u, $sesi->get($u->id,collect()), $logs->get($u->id), $tanggal, $rekap['per_user'][$u->id] ?? null
        ));

        $stat=[
            'hadir'=>$rows->where('status','hadir')->count(),
            'izin'=>$rows->where('status','izin')->count(),
            'sakit'=>$rows->where('status','sakit')->count(),
            'alpha'=>$rows->where('status','alpha')->count(),
            'acc'=>$rows->where('catatan_state','menunggu_acc')->count(),
        ];

        if($r->status){
            $rows=$rows->filter(fn($x)=>$r->status==='kosong' ? $x['status']===null : $x['status']===$r->status);
        }

        $qrBaru=null;
        if($id=session('qr_peserta_id')){
            $u=User::where('peran','peserta')->find($id);
            $qrBaru=$u ? $this->payload($u,collect(),null,$tanggal) : null;
        }

        return view('admin.presensi.index',[
            'rows'=>$rows->values(),'stat'=>$stat,'tanggal'=>$tanggal,'rekap'=>$rekap,
            'prev'=>$tanggal->copy()->subDay()->toDateString(),
            'next'=>$tanggal->copy()->addDay()->toDateString(),
            'departemen'=>\App\Models\Departemen::orderBy('nama')->pluck('nama'),
            'qrBaru'=>$qrBaru,
        ]);
    }

    public function approve(SesiPresensi $presensi)
    {
        $presensi->load('laporanHarian');

        if(!$presensi->laporanHarian){
            return back()->with('error','Laporan belum diisi, belum bisa disetujui.');
        }

        DB::transaction(function() use($presensi){
            $presensi->update([
                'status_persetujuan'=>'disetujui',
                'disetujui_oleh'=>null,
                'catatan_approval'=>null,
            ]);
            $lap=$presensi->laporanHarian;
            $lap->update([
                'status_review'=>'disetujui',
                'direview_oleh'=>null,
                'direview_pada'=>now(),
            ]);
        });

        return back()->with('success','Catatan harian disetujui.');
    }

    public function approveSemua(Request $r)
    {
        $d=$r->validate([
            'tanggal'=>'required|date',
            'departemen'=>'nullable|string',
            'q'=>'nullable|string',
        ]);

        $query=SesiPresensi::with('laporanHarian')
            ->whereDate('tanggal',$d['tanggal'])
            ->where('sesi','sore')
            ->whereHas('user',function($q) use($d){
                $q->where('peran','peserta')
                  ->when($d['departemen']??null,fn($q,$v)=>$q->whereHas('departemen',fn($dd)=>$dd->where('nama',$v)))
                  ->when($d['q']??null,fn($q,$v)=>$q->where('name','like',"%{$v}%"));
            })
            ->whereHas('laporanHarian',fn($q)=>$q->whereIn('status_review',['menunggu','disetujui']));

        $items=$query->get()->filter(fn($s)=>$s->laporanHarian && $s->laporanHarian->status_review==='menunggu');
        if($items->isEmpty()) return back()->with('error','Tidak ada catatan yang menunggu ACC.');

        DB::transaction(function() use($items){
            foreach($items as $s){
                $s->update(['status_persetujuan'=>'disetujui','disetujui_oleh'=>null]);
                $s->laporanHarian->update([
                    'status_review'=>'disetujui','direview_oleh'=>null,'direview_pada'=>now()
                ]);
            }
        });

        return back()->with('success',$items->count().' catatan harian disetujui.');
    }

    public function updateStatus(Request $r)
    {
        $d=$r->validate([
            'user_id'=>'required|exists:users,id',
            'tanggal'=>'required|date',
            'status'=>['required',Rule::in(['hadir','izin','sakit','cuti','alpha'])],
            'catatan_admin'=>'nullable|string|max:500',
        ]);

        $u=User::where('peran','peserta')->findOrFail($d['user_id']);

        LogPerubahanStatus::create([
            'user_id'=>$u->id,
            'tanggal'=>$d['tanggal'],
            'status_baru'=>$d['status'],
            'catatan'=>$d['catatan_admin']??null,
            'diubah_oleh'=>null,
        ]);

        if($d['status']!=='hadir'){
            SesiPresensi::where(['user_id'=>$u->id,'tanggal'=>$d['tanggal']])
                ->update(['status'=>$d['status']]);
        }

        return back()->with('success','Status presensi diperbarui.');
    }

    /**
     * Status satu hari untuk satu peserta. Aturannya sama dengan di payload():
     * log admin terbaru menang; kalau tidak ada, lihat sesi (alpha > izin/sakit/cuti > hadir).
     */
    private function statusHarian(?string $log, Collection $sesi): ?string
    {
        if($log) return $log;
        if($sesi->contains(fn($s)=>$s->status==='alpha')) return 'alpha';
        $bukanHadir=$sesi->first(fn($s)=>$s->status!=='hadir');
        if($bukanHadir) return $bukanHadir->status;
        return $sesi->isNotEmpty() ? 'hadir' : null;
    }

    /**
     * Rekap kehadiran satu bulan untuk semua peserta di $peserta (sudah terfilter).
     * Hari kerja = Senin-Jumat, dari awal bulan sampai hari ini (atau akhir bulan kalau sudah lewat),
     * dibatasi tgl_mulai / tgl_selesai magang masing-masing peserta. Hanya 2 query untuk seluruh peserta.
     */
    private function rekapBulanan(Collection $peserta, Carbon $tgl): array
    {
        $awal=$tgl->copy()->startOfMonth();
        $akhir=$tgl->copy()->endOfMonth();
        $batas=$akhir->copy()->min(today());

        $hariKerja=[];
        for($d=$awal->copy(); $d->lte($batas); $d->addDay()){
            if($d->isWeekday()) $hariKerja[]=$d->toDateString();
        }

        $ids=$peserta->pluck('id');
        $rentang=[$awal->toDateString(),$akhir->toDateString()];

        $sesi=SesiPresensi::whereBetween('tanggal',$rentang)
            ->whereIn('user_id',$ids)
            ->get(['user_id','tanggal','status'])
            ->groupBy(fn($s)=>$s->user_id.'|'.Carbon::parse($s->tanggal)->toDateString());

        // Urut id naik: log yang lebih baru menimpa yang lama, jadi yang tersisa adalah log terbaru.
        $log=[];
        LogPerubahanStatus::whereBetween('tanggal',$rentang)
            ->whereIn('user_id',$ids)
            ->orderBy('id')
            ->get(['id','user_id','tanggal','status_baru'])
            ->each(function($l) use(&$log){
                $log[$l->user_id.'|'.Carbon::parse($l->tanggal)->toDateString()]=$l->status_baru;
            });

        $kosong=['hadir'=>0,'izin'=>0,'sakit'=>0,'cuti'=>0,'alpha'=>0,'tanpa_data'=>0,'hari_kerja'=>0];
        $total=$kosong;
        $perUser=[];

        foreach($peserta as $u){
            $dm=$u->dataMagang;
            $mulai=$dm?->tgl_mulai ? Carbon::parse($dm->tgl_mulai)->toDateString() : null;
            $selesai=$dm?->tgl_selesai ? Carbon::parse($dm->tgl_selesai)->toDateString() : null;

            $h=$kosong;
            foreach($hariKerja as $t){
                if(($mulai && $t<$mulai) || ($selesai && $t>$selesai)) continue;
                $key=$u->id.'|'.$t;
                $st=$this->statusHarian($log[$key]??null,$sesi->get($key,collect()));
                $h['hari_kerja']++;
                $h[$st ?? 'tanpa_data']++;
            }
            $h['persen']=$h['hari_kerja']>0 ? (int)round($h['hadir']/$h['hari_kerja']*100) : null;
            $perUser[$u->id]=$h;

            foreach($kosong as $k=>$_) $total[$k]+=$h[$k];
        }

        $total['persen']=$total['hari_kerja']>0 ? (int)round($total['hadir']/$total['hari_kerja']*100) : null;

        return [
            'per_user'=>$perUser,
            'total'=>$total,
            'label'=>$tgl->translatedFormat('F Y'),
            'jumlah_peserta'=>$peserta->count(),
            'hari_kerja'=>count($hariKerja),
            'sampai_label'=>$batas->gte($awal) ? $batas->translatedFormat('d F') : null,
        ];
    }

    private function payload(User $u,Collection $sesi,?LogPerubahanStatus $log,Carbon $tgl,?array $bulan=null): array
    {
        $pagi=$sesi->firstWhere('sesi','pagi');
        $sore=$sesi->firstWhere('sesi','sore');
        $lap=$sore?->laporanHarian;

        $status=$log?->status_baru;
        if(!$status){
            if($sesi->contains(fn($s)=>$s->status==='alpha')) $status='alpha';
            elseif($sesi->contains(fn($s)=>in_array($s->status,['izin','sakit','cuti'],true))) $status=$sesi->first(fn($s)=>$s->status!=='hadir')->status;
            elseif($pagi || $sore) $status='hadir';
        }

        $catatanState=null;
        if($lap){
            $catatanState=$lap->status_review==='menunggu'?'menunggu_acc':'disetujui';
        } elseif($pagi && !$sore){
            $catatanState='belum_absen_pulang';
        }

        $foto=$u->foto_profil_url;
        $fotoPath=$u->dataMagang?->foto_pas;
        if(!$foto && $fotoPath){
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk=Storage::disk('public');
            $foto=$disk->url($fotoPath);
        }

        return [
            'user_id'=>$u->id,
            'nama'=>$u->name,
            'departemen'=>optional($u->departemen)->nama ?? '-',
            'nip'=>$u->nomor_induk,
            'inisial'=>collect(preg_split('/\s+/',$u->name))->filter()->map(fn($x)=>strtoupper($x[0]))->take(2)->implode(''),
            'foto'=>$foto,
            'tanggal'=>$tgl->toDateString(),
            'tanggal_label'=>$tgl->translatedFormat('d F Y'),
            'tanggal_panjang'=>$tgl->translatedFormat('l, d F Y'),
            'status'=>$status,
            'warning'=>(bool)($pagi xor $sore),
            'catatan_admin'=>$log?->catatan,
            'masuk'=>$pagi?->waktu_absen?->format('H:i'),
            'keluar'=>$sore?->waktu_absen?->format('H:i'),
            'laporan'=>$lap?->laporan,
            'catatan_state'=>$catatanState,
            'bulan'=>$bulan,
            'approve_url'=>$sore?->id ? route('admin.presensi.approve',$sore) : null,
            'qr_url'=>route('admin.peserta.qr.image',$u),
            'qr_download'=>route('admin.peserta.qr.download',$u),
            'hapus_url'=>route('admin.peserta.destroy',$u),
        ];
    }
}