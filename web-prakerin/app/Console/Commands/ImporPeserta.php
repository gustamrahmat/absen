<?php

namespace App\Console\Commands;

use App\Services\ImporDataMagang;
use Illuminate\Console\Command;

class ImporPeserta extends Command
{
    protected $signature = 'prakerin:impor-peserta {file : Lokasi file Excel (.xlsx)} {--uji : Hanya cek dan tampilkan laporan, tanpa menyimpan}';

    protected $description = 'Impor daftar peserta magang dari Excel ke data_magang (UUID kartu lama dipertahankan, UUID baru dibuat untuk baris kosong).';

    public function handle(ImporDataMagang $impor): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        try {
            $hasil = $impor->jalankan($file, (bool) $this->option('uji'));
        } catch (\Throwable $e) {
            $this->error('Impor gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info($hasil['hanyaUji'] ? '== MODE UJI: tidak ada yang disimpan ==' : '== Impor selesai ==');
        $this->line("Peserta baru        : {$hasil['baru']}");
        $this->line("  - UUID baru dibuat: {$hasil['uuidBaru']}");
        $this->line("Diperbarui          : {$hasil['diperbarui']}");
        $this->line("Tidak berubah       : {$hasil['tetap']}");
        if ($hasil['departemenBaru']) {
            $this->warn('Departemen BARU dibuat (cek salah ketik): ' . implode(', ', $hasil['departemenBaru']));
        }
        foreach ($hasil['peringatan'] as $p) {
            $this->warn($p);
        }
        foreach ($hasil['galat'] as $g) {
            $this->error($g);
        }

        return $hasil['galat'] ? self::FAILURE : self::SUCCESS;
    }
}
