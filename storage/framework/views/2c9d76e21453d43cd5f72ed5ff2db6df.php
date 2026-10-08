<?php $__env->startSection('body-class', 'mode-landing'); ?>
<?php $__env->startSection('badge', 'Portal Pendaftaran'); ?>

<?php $__env->startSection('konten'); ?>
    
    
    <?php echo $__env->make('auth.partials.form-masuk', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Maganghub\webpresensi\resources\views/auth/landing.blade.php ENDPATH**/ ?>