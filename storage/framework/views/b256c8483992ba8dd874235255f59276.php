<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title><?php echo $__env->yieldContent('title', 'Admin Presensi'); ?> — New Armada Group</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-white font-sans text-slate-800 min-h-screen flex flex-col">
<header class="border-b border-slate-200">
<div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between gap-4">
<a href="<?php echo e(route('admin.presensi.index')); ?>" class="font-extrabold text-sm"><span class="text-red-500">new</span> <span class="text-blue-600">armada group</span></a>
<nav class="hidden md:flex gap-7 text-xs font-semibold">
<a href="<?php echo e(route('admin.presensi.index')); ?>" class="<?php echo e(request()->routeIs('admin.presensi.*') ? 'text-blue-600' : ''); ?>">Presensi</a>
<a href="<?php echo e(route('admin.kartu.cetak')); ?>" class="<?php echo e(request()->routeIs('admin.kartu.*') ? 'text-blue-600' : ''); ?>">Kartu ID</a>
<a href="<?php echo e(route('presensi.index')); ?>">Halaman Peserta</a>
</nav>
<div class="flex items-center gap-3">
<span class="hidden sm:block text-[11px] text-slate-500"><?php echo e(session('admin_nama', 'Administrator')); ?></span>
<form method="POST" action="<?php echo e(route('admin.logout')); ?>"><?php echo csrf_field(); ?>
<button class="text-xs font-semibold border border-slate-300 rounded-md px-4 py-1.5 hover:bg-slate-50">Log Out</button>
</form>
</div>
</div>
</header>
<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-8">
<?php if(session('success')): ?><div class="mb-4 rounded-lg bg-green-50 text-green-700 text-sm px-4 py-2"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('error')): ?><div class="mb-4 rounded-lg bg-red-50 text-red-700 text-sm px-4 py-2"><?php echo e(session('error')); ?></div><?php endif; ?>
<?php echo $__env->yieldContent('content'); ?>
</main>
<footer class="bg-slate-900 text-slate-400 text-xs">
<div class="max-w-7xl mx-auto px-6 h-12 flex items-center justify-between">
<span><b class="text-red-500">new</b> <b class="text-blue-400">armada group</b> | Portal Prakerin</span>
<span>© <?php echo e(date('Y')); ?> PT Mekar Armada Jaya</span>
</div>
</footer>
</body>
</html>
<?php /**PATH D:\Maganghub\webpresensi\resources\views/layouts/admin.blade.php ENDPATH**/ ?>