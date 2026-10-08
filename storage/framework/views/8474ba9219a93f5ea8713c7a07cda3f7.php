<?php $__env->startSection('title', 'Tambah Peserta'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $inp = 'w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400';
    $lbl = 'block text-xs font-semibold text-slate-700 mb-1.5';
?>

<div class="max-w-4xl mx-auto">

    
    <div class="mb-6">

        <a href="<?php echo e(route('admin.presensi.index')); ?>"
           class="inline-flex items-center text-xs text-slate-500 hover:text-blue-600 mb-3">
            ← Kembali
        </a>

        <h1 class="text-2xl font-extrabold text-slate-800">
            Tambah Peserta
        </h1>

        <p class="text-sm text-slate-500 mt-1">
            Tambahkan peserta yang akan menggunakan sistem presensi.
        </p>

    </div>


    
    <form method="POST"
          action="<?php echo e(route('admin.peserta.store')); ?>"
          class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">

        <?php echo csrf_field(); ?>


        
        <?php if($errors->any()): ?>
            <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                <div class="font-semibold mb-1">
                    Data belum dapat disimpan:
                </div>

                <ul class="list-disc pl-5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($e); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>


        
        <div class="mb-6">

            <div class="flex items-center gap-2 mb-4">
                <div class="w-1.5 h-5 bg-blue-600 rounded-full"></div>

                <h2 class="text-sm font-bold text-slate-800">
                    Data Peserta
                </h2>
            </div>


            <div class="grid md:grid-cols-2 gap-4">

                
                <div>
                    <label class="<?php echo e($lbl); ?>">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="nama"
                        value="<?php echo e(old('nama')); ?>"
                        class="<?php echo e($inp); ?>"
                        placeholder="Masukkan nama lengkap"
                        required
                    >
                </div>


                
                <div>
                    <label class="<?php echo e($lbl); ?>">
                        Email <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo e(old('email')); ?>"
                        class="<?php echo e($inp); ?>"
                        placeholder="peserta@email.com"
                        required
                    >
                </div>


                
                <div>
                    <label class="<?php echo e($lbl); ?>">
                        No. WhatsApp
                    </label>

                    <input
                        type="text"
                        name="no_wa"
                        value="<?php echo e(old('no_wa')); ?>"
                        class="<?php echo e($inp); ?>"
                        placeholder="08xxxxxxxxxx"
                    >
                </div>


                
                <div>
                    <label class="<?php echo e($lbl); ?>">
                        Departemen <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="departemen_id"
                        class="<?php echo e($inp); ?>"
                        required
                    >

                        <option value="">
                            Pilih departemen
                        </option>

                        <?php $__currentLoopData = $departemen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                            <option
                                value="<?php echo e($d->id); ?>"
                                <?php if(old('departemen_id') == $d->id): echo 'selected'; endif; ?>
                            >
                                <?php echo e($d->nama); ?>

                            </option>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </select>
                </div>

            </div>

        </div>


        
        <div class="mb-6">

            <div class="flex items-center gap-2 mb-4">
                <div class="w-1.5 h-5 bg-blue-600 rounded-full"></div>

                <h2 class="text-sm font-bold text-slate-800">
                    Periode Presensi
                </h2>
            </div>


            <div class="grid md:grid-cols-2 gap-4">

                
                <div>
                    <label class="<?php echo e($lbl); ?>">
                        Tanggal Mulai <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="tgl_mulai"
                        value="<?php echo e(old('tgl_mulai')); ?>"
                        class="<?php echo e($inp); ?>"
                        required
                    >

                    <p class="text-[11px] text-slate-400 mt-1">
                        Tanggal mulai peserta melakukan presensi.
                    </p>
                </div>


                
                <div>
                    <label class="<?php echo e($lbl); ?>">
                        Tanggal Selesai <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="tgl_selesai"
                        value="<?php echo e(old('tgl_selesai')); ?>"
                        class="<?php echo e($inp); ?>"
                        required
                    >

                    <p class="text-[11px] text-slate-400 mt-1">
                        Tanggal terakhir peserta melakukan presensi.
                    </p>
                </div>

            </div>

        </div>


        
        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-6">

            <div class="flex gap-3">

                <div class="text-blue-600 text-lg">
                    ℹ
                </div>

                <div>

                    <p class="text-xs font-semibold text-blue-800">
                        Informasi
                    </p>

                    <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                        Setelah peserta ditambahkan, akun peserta akan dibuat
                        dan peserta dapat digunakan untuk sistem presensi.
                    </p>

                </div>

            </div>

        </div>


        
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">

            <a
                href="<?php echo e(route('admin.presensi.index')); ?>"
                class="px-5 py-2.5 border border-slate-300 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Batal
            </a>

            <button
                type="submit"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm"
            >
                + Tambah Peserta
            </button>

        </div>

    </form>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Maganghub\webpresensi\resources\views/admin/peserta/create.blade.php ENDPATH**/ ?>