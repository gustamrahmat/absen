<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceController;
use App\Models\SesiPresensi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TutupHariPresensi extends Command
{
    protected $signature = 'presensi:tutup-hari {tanggal? : Format Y-m-d, bawaan hari ini}';

    protected $description = 'Tandai presensi yang hanya 1 sesi (lupa absen / izin setengah hari) sebagai "menunggu" persetujuan HRD.';

    public function handle(): int
    {
        $tanggal = $this->argument('tanggal')
            ? Carbon::createFromFormat('!Y-m-d', $this->argument('tanggal'))
            : now()->startOfDay();

        if (! AttendanceController::hariKerja($tanggal)) {
            $this->info('Bukan hari kerja, tidak ada yang diproses.');
            return self::SUCCESS;
        }

        $jamTutup = $tanggal->copy()->setTime(config('prakerin.jendela.sore.selesai'), 0);
        if (now()->lt($jamTutup)) {
            $this->error('Hari ini belum ditutup (sesi sore berakhir pukul ' . $jamTutup->format('H:i') . '). Coba lagi setelahnya.');
            return self::FAILURE;
        }

        // Peserta yang pada tanggal itu hanya punya 1 sesi
        $idHanyaSatuSesi = SesiPresensi::whereDate('tanggal', $tanggal->toDateString())
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) = 1')
            ->pluck('user_id');

        $jumlah = SesiPresensi::whereDate('tanggal', $tanggal->toDateString())
            ->whereIn('user_id', $idHanyaSatuSesi)
            ->where('status_persetujuan', 'auto')
            ->update(['status_persetujuan' => 'menunggu']);

        $this->info("Selesai: {$jumlah} presensi 1-sesi pada {$tanggal->toDateString()} ditandai menunggu persetujuan HRD.");

        return self::SUCCESS;
    }
}
