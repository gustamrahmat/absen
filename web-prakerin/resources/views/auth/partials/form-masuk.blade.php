<h2>Masuk ke Akun Anda</h2>
<p class="sub">Masukkan email &amp; kata sandi yang telah terdaftar</p>

<form method="POST" action="{{ route('login.store') }}">
    @csrf
    <div class="fld">
        <label>Alamat Email / NISN / NIM</label>
        <div class="inp"><span>@</span>
            <input name="identifier" value="{{ old('identifier') }}" placeholder="contoh: siswa@smk.sch.id" class="@error('identifier') error @enderror" required>
        </div>
        @error('identifier') <div class="errtxt">{{ $message }}</div> @enderror
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
<p class="switch">Belum memiliki akun peserta? <a href="{{ route('daftar') }}">Daftar Prakerin Sekarang</a></p>
