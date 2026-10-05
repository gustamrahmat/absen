<h2>Buat Akun Baru</h2>
<p class="sub">Lengkapi 3 data di bawah untuk membuat akun</p>

<form method="POST" action="{{ route('daftar.store') }}">
    @csrf
    <div class="fld">
        <label>Nama Lengkap</label>
        <div class="inp"><span>👤</span>
            <input name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap Anda" class="@error('name') error @enderror" required>
        </div>
        @error('name') <div class="errtxt">{{ $message }}</div> @enderror
    </div>
    <div class="fld">
        <label>NIM / NISN</label>
        <div class="inp"><span>#</span>
            <input name="nomor_induk" value="{{ old('nomor_induk') }}" placeholder="Masukkan NIM / NISN Anda" inputmode="numeric" autocomplete="username" maxlength="50" class="@error('nomor_induk') error @enderror" required>
        </div>
        @error('nomor_induk') <div class="errtxt">{{ $message }}</div> @enderror
    </div>
    <div class="fld">
        <label>Kata Sandi</label>
        <div class="inp"><span>🔒</span>
            <input type="password" name="password" placeholder="Minimal 8 karakter" minlength="8" class="@error('password') error @enderror" required>
        </div>
        @error('password') <div class="errtxt">{{ $message }}</div> @enderror
    </div>
    <button class="btn" type="submit">Daftar Akun →</button>
</form>

<div class="hr">ATAU</div>
<p class="switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk Sekarang</a></p>
