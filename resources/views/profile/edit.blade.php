@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'Profile')
@section('page-subtitle', 'Kelola informasi profil dan foto kamu.')

@section('content')

@if(session('status') === 'profile-updated')
    <div class="alert alert--success" style="margin-bottom:16px">Profil berhasil diperbarui.</div>
@endif

@if(session('status') === 'photo-updated')
    <div class="alert alert--success" style="margin-bottom:16px">Foto profil berhasil diperbarui.</div>
@endif

<div style="display:flex; flex-direction:column; gap:20px; max-width:600px">

    {{-- ===== FOTO PROFIL ===== --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Foto Profil</div>
        </div>
        <div class="card-body" style="display:flex; align-items:center; gap:20px">

            {{-- Avatar --}}
            @if(auth()->user()->photo)
                <img src="{{ Storage::url(auth()->user()->photo) }}"
                     style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid var(--border)">
            @else
                <div class="avatar avatar--blue" style="width:80px; height:80px; font-size:28px">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            @endif

            {{-- Form Upload --}}
            <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <div style="display:flex; flex-direction:column; gap:8px">
                    <input type="file" name="photo" accept="image/*"
                           class="form-input" style="font-size:13px">
                    @error('photo')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                    <span style="font-size:11px; color:var(--text-muted)">JPG, JPEG, PNG. Maks 2MB.</span>
                    <button type="submit" class="btn btn--primary btn--sm">Upload Foto</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== INFO PROFIL ===== --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Informasi Profil</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}" style="display:flex; flex-direction:column; gap:14px">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-input"
                           value="{{ old('name', auth()->user()->name) }}" required>
                    @error('name')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input"
                           value="{{ old('email', auth()->user()->email) }}" required>
                    @error('email')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="phone" class="form-input"
                           value="{{ old('phone', auth()->user()->phone) }}">
                </div>

                <div>
                    <button type="submit" class="btn btn--primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== GANTI PASSWORD ===== --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Ganti Password</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('password.update') }}" style="display:flex; flex-direction:column; gap:14px">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="current_password" class="form-input">
                    @error('current_password')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-input">
                    @error('password')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-input">
                </div>

                <div>
                    <button type="submit" class="btn btn--primary">Ganti Password</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection