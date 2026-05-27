@extends('layouts.app')

@section('title', 'Заявки поддержки')

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">
    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;">📬 Заявки поддержки</h2>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div style="display:flex;gap:8px;margin-bottom:16px;">
        @foreach(['all' => 'Все', 'new' => '🟢 Новые', 'in_progress' => '🟡 В работе', 'closed' => '⚫ Закрытые'] as $key => $label)
            <a href="{{ route('admin.support.index', ['status' => $key]) }}"
               style="padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;
                      {{ $status === $key ? 'background:var(--accent);color:white;' : 'background:var(--bg-input);color:var(--text-secondary);border:1px solid var(--border);' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="form-section" style="padding:0;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--surface-glass);">
                    <th style="padding:12px;text-align:left;font-size:12px;">#</th>
                    <th style="padding:12px;text-align:left;font-size:12px;">Email</th>
                    <th style="padding:12px;text-align:left;font-size:12px;">Сообщение</th>
                    <th style="padding:12px;text-align:center;font-size:12px;">Статус</th>
                    <th style="padding:12px;text-align:center;font-size:12px;">Дата</th>
                    <th style="padding:12px;text-align:right;font-size:12px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $t)
                <tr style="border-top:1px solid var(--border);{{ $t->status === 'new' ? 'background:#fefff0;' : '' }}">
                    <td style="padding:12px;font-weight:600;">{{ $t->id }}</td>
                    <td style="padding:12px;font-size:13px;">{{ $t->email }}</td>
                    <td style="padding:12px;font-size:13px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($t->message, 80) }}</td>
                    <td style="padding:12px;text-align:center;">
                        @if($t->status === 'new')
                            <span style="color:#15803d;font-size:12px;font-weight:600;">🟢 Новая</span>
                        @elseif($t->status === 'in_progress')
                            <span style="color:#ca8a04;font-size:12px;font-weight:600;">🟡 В работе</span>
                        @else
                            <span style="color:#6b7280;font-size:12px;">⚫ Закрыта</span>
                        @endif
                    </td>
                    <td style="padding:12px;text-align:center;font-size:12px;color:var(--text-tertiary);">{{ $t->created_at->format('d.m.Y H:i') }}</td>
                    <td style="padding:12px;text-align:right;">
                        <a href="{{ route('admin.support.show', $t->id) }}" style="font-size:13px;color:var(--accent);">Открыть</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:30px;text-align:center;color:var(--text-tertiary);">Заявок нет</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $tickets->appends(['status' => $status])->links() }}</div>
</div>
@endsection