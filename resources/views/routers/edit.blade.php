@extends('layouts.app')
@section('title', 'Edit Router')
@section('page-title', 'Edit Router')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Edit Router: {{ $router->name }}</h2>
        <p>Perbarui konfigurasi koneksi router</p>
    </div>
    <div class="d-flex gap-8">
        <a href="{{ route('routers.show', $router) }}" class="btn btn-outline">Detail</a>
        <a href="{{ route('routers.index') }}" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali
        </a>
    </div>
</div>

<div class="card">
    <form action="{{ route('routers.update', $router) }}" method="POST">
        @csrf @method('PUT')
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Nama Router *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $router->name) }}" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">IP Address / Host *</label>
                <input type="text" name="host" class="form-control" value="{{ old('host', $router->host) }}" required>
                @error('host') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" value="{{ old('username', $router->username) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Port API *</label>
                <input type="number" name="port" class="form-control" value="{{ old('port', $router->port) }}" required>
            </div>
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="ros_version">Versi RouterOS *</label>
                <select id="ros_version" name="ros_version" class="form-control" required>
                    <option value="v7" {{ old('ros_version', $router->ros_version) == 'v7' ? 'selected' : '' }}>RouterOS v7</option>
                    <option value="v6" {{ old('ros_version', $router->ros_version) == 'v6' ? 'selected' : '' }}>RouterOS v6</option>
                </select>
                @error('ros_version') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Password <span style="color: #4a5568;">(kosongkan jika tidak ingin mengubah)</span></label>
                <input type="password" name="password" class="form-control" placeholder="Password baru (opsional)">
            </div>
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Lokasi</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $router->location) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Brand</label>
                <input type="text" name="brand" class="form-control" value="{{ old('brand', $router->brand) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" value="{{ old('model', $router->model) }}">
        </div>
        <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $router->description) }}</textarea>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                Simpan Perubahan
            </button>
            <a href="{{ route('routers.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
