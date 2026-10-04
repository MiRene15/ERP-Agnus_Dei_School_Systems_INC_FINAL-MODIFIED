@foreach(['uptime' => 'Uptime', 'abuse' => 'Abuse / Spam Alerts', 'slow' => 'Slow Lists', 'logins' => 'Logins + New Accounts Spikes'] as $key => $title)
    @php
        $card = $cards[$key] ?? ['status' => 'green', 'label' => 'All calm', 'count' => 0, 'details' => []];
        $status = $card['status'] ?? 'green';
        $isAcked = in_array($key, $acknowledged ?? [], true);
        $dot = $isAcked ? 'bg-green-500' : ($status === 'red' ? 'bg-red-500' : ($status === 'yellow' ? 'bg-yellow-400' : 'bg-green-500'));
        $ring = $status === 'red' ? 'border-red-200 dark:border-[rgba(248,113,113,0.35)]' : ($status === 'yellow' ? 'border-yellow-200 dark:border-[rgba(252,211,77,0.35)]' : 'border-gray-100 dark:border-[#2A2F58]');
        $details = array_slice($card['details'] ?? [], 0, 3);
    @endphp
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border {{ $ring }} p-5 hover:shadow-md transition">
        <a href="{{ route('admin.system-health.show', $key) }}" class="flex items-center gap-2 group">
            <span class="inline-block w-2.5 h-2.5 rounded-full {{ $dot }}"></span>
            @if($key === 'abuse')
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            @elseif($key === 'slow')
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($key === 'uptime')
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] text-sm group-hover:underline">{{ $title }}</h3>
        </a>
        <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">{{ number_format((int) ($card['count'] ?? 0)) }}</p>
        <p class="mt-1 text-xs text-gray-600 dark:text-[#C1C4DC]">{{ $card['label'] ?? 'All calm' }}</p>
        @if(!empty($details))
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-[#2A2F58] space-y-1 text-xs text-gray-600 dark:text-[#C1C4DC]">
                @foreach($details as $row)
                    <p>{{ $row['area'] ?? '' }} — {{ $row['when'] ?? '' }} — {{ $row['times'] ?? 0 }}x</p>
                @endforeach
            </div>
        @endif
        <div class="mt-3 flex items-center gap-2">
            <a href="{{ route('admin.system-health.show', $key) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-[#23274C] dark:text-[#9FB4FF] transition">View details</a>
            @if($isAcked)
                <span class="text-xs text-green-600 dark:text-[#4ADE80] font-semibold">Acknowledged ✓{{ isset($ackInfo[$key]) ? ' by ' . ($ackInfo[$key]['by'] ?? '') . ' at ' . ($ackInfo[$key]['at'] ?? '') : '' }}</span>
            @elseif($status !== 'green')
                <form method="POST" action="{{ route('admin.system-health.acknowledge') }}">
                    @csrf
                    <input type="hidden" name="alert_type" value="{{ $key }}">
                    <input type="hidden" name="route" value="">
                    <input type="hidden" name="counts" value="{{ (int) ($card['count'] ?? 0) }}">
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition" style="background: var(--navy);">Acknowledge</button>
                </form>
            @else
                <span class="text-xs text-green-600 dark:text-[#4ADE80] font-medium">All calm</span>
            @endif
        </div>
    </div>
@endforeach
