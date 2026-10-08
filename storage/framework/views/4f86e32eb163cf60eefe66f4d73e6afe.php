<?php $__env->startSection('judul', 'Daftar Akun'); ?>
<?php $__env->startSection('body-class', 'mode-form'); ?>
<?php $__env->startSection('badge', 'Pendaftaran Akun Baru'); ?>

<?php $__env->startSection('konten'); ?>
    <?php echo $__env->make('auth.partials.form-daftar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Maganghub\webpresensi\resources\views/auth/daftar.blade.php ENDPATH**/ ?>