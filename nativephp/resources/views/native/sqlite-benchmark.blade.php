<native:column class="w-full h-full p-4 gap-3 bg-white safe-area">
    <native:text class="text-2xl font-bold text-zinc-900">SQLite benchmark</native:text>
    <native:text class="text-base text-zinc-600">NativePHP SuperNative: native UI, persistent PHP runtime, PDO SQLite</native:text>
    <native:column class="w-full gap-2">
        <native:button label="Run SuperNative benchmark" @tap="start" :disabled="$status === 'running'" />
        <native:button label="Open WebView benchmark" @tap="openWebView" :disabled="$status === 'running'" />
        @if ($status === 'complete')
            <native:button label="Export report to logcat" @tap="exportReport" />
        @endif
    </native:column>
    <native:text class="text-base font-semibold text-zinc-900" @if ($status === 'running') native:poll="1ms" @endif>
        @if ($status === 'running') Running {{ $caseIndex }}/17…
        @elseif ($status === 'complete') Complete: SQLite {{ $metadata['sqlite_version'] ?? '' }}, journal_mode={{ $metadata['journal_mode'] ?? '' }}, synchronous={{ $metadata['synchronous'] ?? '' }}, integrity_check={{ $metadata['integrity_check'] ?? '' }}
        @elseif ($status === 'error') Error: {{ $error }}
        @else Ready
        @endif
    </native:text>
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-1">
            @foreach ($results as $result)
                <native:row class="w-full justify-between">
                    <native:text class="text-sm text-zinc-900">{{ $result['name'] }}</native:text>
                    <native:text class="text-sm {{ $result['status'] === 'ok' ? 'text-zinc-600' : 'text-red-600' }}">
                        @if ($result['status'] === 'ok') {{ number_format($result['median_ms'], 3) }} ms (p95 {{ number_format($result['p95_ms'], 3) }}) @else {{ $result['error'] }} @endif
                    </native:text>
                </native:row>
                <native:divider />
            @endforeach
        </native:column>
    </native:scroll-view>
</native:column>
