<?php

namespace App\Console\Commands;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KelolaHariLibur extends Command
{
    protected $signature = 'prakerin:libur
        {tanggal? : Tanggal libur, format Y-m-d (mis. 2026-12-25)}
        {nama? : Nama hari libur (mis. "Hari Raya Natal")}
        {--jenis=nasional : nasional | cuti_bersama | perusahaan}
        {--pengganti= : Tanggal hari kerja pengganti, format Y-m-d (mis. hari Sabtu pengganti cuti bersama)}
        {--hapus : Hapus hari libur pada tanggal ini}
        {--daftar : Tampilkan semua hari libur yang terdaftar}';

    protected $description = 'Tambah/ubah/hapus hari libur (nasional, cuti bersama, perusahaan) dan hari kerja penggantinya.';

    public function handle(): int
    {
        if ($this->option('daftar')) {
            $baris = HariLibur::orderBy('tanggal')->get()->map(fn ($h) => [
                $h->tanggal->toDateString(),
                $h->tanggal->translatedFormat('l'),
                $h->nama,
                $h->jenis,
                $h->tanggal_pengganti ? $h->tanggal_pengganti->toDateString() . ' (' . $h->tanggal_pengganti->translatedFormat('l') . ')' : '-',
            ])->all();
            $this->table(['Tanggal', 'Hari', 'Nama', 'Jenis', 'Hari kerja pengganti'], $baris);

            return self::SUCCESS;
        }

        $tanggal = $this->bacaTanggal($this->argument('tanggal'));
        if (! $tanggal) {
            $this->error('Tanggal wajib diisi dengan format Y-m-d, contoh: 2026-12-25.');
            return self::FAILURE;
        }

        if ($this->option('hapus')) {
            $jumlah = HariLibur::whereDate('tanggal', $tanggal->toDateString())->delete();
            $this->info($jumlah ? "Hari libur {$tanggal->toDateString()} dihapus." : 'Tidak ada hari libur pada tanggal itu.');
            return self::SUCCESS;
        }

        $nama = trim((string) $this->argument('nama'));
        if ($nama === '') {
            $this->error('Nama hari libur wajib diisi.');
            return self::FAILURE;
        }

        $jenis = (string) $this->option('jenis');
        if (! in_array($jenis, ['nasional', 'cuti_bersama', 'perusahaan'], true)) {
            $this->error('Jenis harus: nasional, cuti_bersama, atau perusahaan.');
            return self::FAILURE;
        }

        $pengganti = null;
        if ($this->option('pengganti')) {
            $pengganti = $this->bacaTanggal($this->option('pengganti'));
            if (! $pengganti) {
                $this->error('Tanggal pengganti harus berformat Y-m-d.');
                return self::FAILURE;
            }
            if ($pengganti->isSameDay($tanggal)) {
                $this->error('Hari pengganti tidak boleh sama dengan hari liburnya.');
                return self::FAILURE;
            }
            if (HariLibur::whereDate('tanggal', $pengganti->toDateString())->exists()) {
                $this->error('Hari pengganti itu sendiri terdaftar sebagai hari libur.');
                return self::FAILURE;
            }
            if (HariLibur::whereDate('tanggal_pengganti', $pengganti->toDateString())->whereDate('tanggal', '<>', $tanggal->toDateString())->exists()) {
                $this->error('Tanggal itu sudah menjadi hari pengganti untuk libur lain.');
                return self::FAILURE;
            }
            if ($pengganti->dayOfWeekIso <= 5) {
                $this->warn('Catatan: hari pengganti jatuh di Senin-Jumat (sudah hari kerja biasa). Biasanya pengganti adalah hari Sabtu.');
            }
        }
        if (HariLibur::whereDate('tanggal_pengganti', $tanggal->toDateString())->exists()) {
            $this->error('Tanggal itu sudah menjadi hari kerja pengganti, tidak bisa sekaligus hari libur.');
            return self::FAILURE;
        }
        if ($tanggal->dayOfWeekIso >= 6) {
            $this->warn('Catatan: tanggal ini jatuh di akhir pekan (sudah tidak wajib masuk).');
        }

        HariLibur::updateOrCreate(
            ['tanggal' => $tanggal->toDateString()],
            ['nama' => $nama, 'jenis' => $jenis, 'tanggal_pengganti' => $pengganti ? $pengganti->toDateString() : null]
        );

        $this->info("Tersimpan: {$tanggal->toDateString()} {$nama} ({$jenis})" . ($pengganti ? ", diganti {$pengganti->toDateString()}" : '') . '.');

        return self::SUCCESS;
    }

    // Mengembalikan null kalau teks bukan tanggal Y-m-d yang valid (termasuk 2026-02-30).
    private function bacaTanggal(?string $teks): ?Carbon
    {
        if (! $teks) {
            return null;
        }
        try {
            $t = Carbon::createFromFormat('!Y-m-d', $teks);
        } catch (\Throwable $e) {
            return null;
        }

        return ($t && $t->format('Y-m-d') === $teks) ? $t : null;
    }
}
