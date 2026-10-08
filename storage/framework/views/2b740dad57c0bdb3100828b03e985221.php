<h2>Masuk ke Akun Anda</h2>
<p class="sub">Masukkan email &amp; kata sandi yang telah terdaftar</p>

<form method="POST" action="<?php echo e(route('login.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="fld">
        <label>Alamat Email / NISN / NIM</label>
        <div class="inp"><span>@</span>
            <input name="identifier" value="<?php echo e(old('identifier')); ?>" placeholder="contoh: siswa@smk.sch.id" class="<?php $__errorArgs = ['identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
        </div>
        <?php $__errorArgs = ['identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="errtxt"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="fld">
        <label>Kata Sandi <a href="#" onclick="alert('Fitur lupa kata sandi menyusul.');return false">Lupa Kata Sandi?</a></label>
        <div class="inp"><span>🔒</span>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
    </div>
    <label class="chk"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label>
    <button class="btn" type="submit">Masuk ke Akun →</button>
</form>

<div class="hr">ATAU</div>
<p class="switch">Belum memiliki akun peserta? <a href="<?php echo e(route('daftar')); ?>">Daftar Prakerin Sekarang</a></p>
<?php /**PATH D:\Maganghub\webpresensi\resources\views/auth/partials/form-masuk.blade.php ENDPATH**/ ?>