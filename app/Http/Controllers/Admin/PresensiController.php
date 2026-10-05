<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataMagang;
use App\Models\LogPerubahanStatus;
use App\Models\SesiPresensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PresensiController extends Controller
{
    public function index(Request $r)
    {
        $tanggal = Carbon::parse($r->input('tanggal', today()->toDateString()))->startOfDay();

        $peserta = User::with('departemen')
            ->where('peran', 'peserta')
            ->when($r->q, fn($q,$v)=>$q->where('name','like',"%{$v}%"))
            ->when($r->departemen, fn($q,$v)=>$q->whereHas('departemen', fn($d)=>$d->where('nama',$v)))
            ->orderBy('name')->get();

        $ids = $peserta->pluck('id');
        $sesi = SesiPresensi::with('laporanHarian')
            ->whereDate('tanggal',$tanggal)
            ->whereIn('user_id',$ids)->get()
            ->groupBy('user_id');

        $logs = LogPerubahanStatus::whereDate('tanggal',$tanggal)
            ->whereIn('user_id',$ids)->latest('id')->get()->keyBy('user_id');

        $rows = $peserta->map(fn(User $u)=>$this->payload($u,$sesi->get($u->id,collect()),$logs->get($u->id),$tanggal));

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
            'rows'=>$rows->values(),'stat'=>$stat,'tanggal'=>$tanggal,
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

    private function payload(User $u,$sesi,$log,Carbon $tgl): array
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
        if(!$foto && $fotoPath) $foto=Storage::disk('public')->url($fotoPath);

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
            'approve_url'=>$sore?->id ? route('admin.presensi.approve',$sore) : null,
            'qr_url'=>route('admin.peserta.qr.image',$u),
            'qr_download'=>route('admin.peserta.qr.download',$u),
            'hapus_url'=>route('admin.peserta.destroy',$u),
        ];
    }
}
