@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="glass-card p-4 border-0">
            <h3 class="text-white fw-bold display-font mb-2">
                <i class="fa-solid fa-shield-halved text-info me-2"></i> Log IP Login Akun
            </h3>
            <p class="text-secondary small mb-4">Rekaman aktivitas login setiap akun beserta IP address yang digunakan.</p>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Waktu Login</th>
                            <th>Nama Akun</th>
                            <th>Email</th>
                            <th>Metode Login</th>
                            <th>IP Address</th>
                            <th>Browser / Device</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loginLogs as $log)
                            <tr>
                                <td class="text-secondary small">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                <td>
                                    @if($log->user)
                                        <span class="fw-semibold text-white">{{ $log->user->name }}</span>
                                        @if(in_array($log->user->role, ['admin', 'super_admin']))
                                            <span class="badge bg-warning-subtle text-warning border border-warning ms-1 px-2" style="font-size:0.7rem;">ADMIN</span>
                                        @endif
                                    @else
                                        <span class="text-muted">— akun dihapus —</span>
                                    @endif
                                </td>
                                <td class="text-secondary small">{{ $log->user->email ?? '—' }}</td>
                                <td>
                                    @php
                                        $methodColor = match($log->method) {
                                            'google'   => 'info',
                                            'otp'      => 'success',
                                            'password' => 'warning',
                                            default    => 'secondary',
                                        };
                                        $methodLabel = match($log->method) {
                                            'google'   => 'Google',
                                            'otp'      => 'OTP Email',
                                            'password' => 'Password',
                                            default    => strtoupper($log->method),
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $methodColor }}-subtle text-{{ $methodColor }} border border-{{ $methodColor }} px-2 py-1">
                                        {{ $methodLabel }}
                                    </span>
                                </td>
                                <td>
                                    <code class="text-success" style="font-size:0.9rem;">{{ $log->ip_address ?? '—' }}</code>
                                </td>
                                <td class="text-secondary" style="font-size:0.78rem;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $log->user_agent }}">
                                    {{ $log->user_agent ? Str::limit($log->user_agent, 60) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary small">
                                    <i class="fa-solid fa-shield-halved fs-2 mb-3 d-block text-muted"></i>
                                    Belum ada log login yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {!! $loginLogs->links('pagination::bootstrap-5') !!}
            </div>
        </div>
    </div>
</div>
@endsection
