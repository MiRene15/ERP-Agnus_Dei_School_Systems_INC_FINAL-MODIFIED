@php
    $rows = $rows ?? $detail['rows'] ?? [];
    $acks = $detail['acknowledgements'] ?? [];
@endphp
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
    <div class="p-4 border-b border-gray-100 dark:border-[#2A2F58] flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ $detail['title'] ?? '' }} — {{ $detail['label'] ?? '' }}</p>
            <p class="text-xs text-gray-500 dark:text-[#8A90B0]">Counts only — what anyone typed is never shown.</p>
            @if($detail['acknowledged'] ?? false)
                <p class="mt-1 text-xs font-semibold text-green-600 dark:text-[#4ADE80]">Acknowledged ✓ — no new activity.</p>
            @elseif($detail['bulkNeedsAck'] ?? false)
                <p class="mt-1 text-xs text-gray-500 dark:text-[#8A90B0]">New activity needs review.</p>
            @endif
        </div>
        @if($detail['bulkNeedsAck'] ?? false)
            <form method="POST" action="{{ route('admin.system-health.acknowledge') }}">
                @csrf
                <input type="hidden" name="alert_type" value="{{ $detail['type'] ?? '' }}">
                <input type="hidden" name="route" value="">
                <input type="hidden" name="counts" value="{{ (int) ($detail['count'] ?? 0) }}">
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Acknowledge all new</button>
            </form>
        @elseif($detail['acknowledged'] ?? false)
            <span class="text-xs font-semibold text-green-600 dark:text-[#4ADE80]">Acknowledged ✓</span>
        @endif
    </div>
    @if(empty($rows))
        <p class="p-6 text-sm text-gray-500 dark:text-[#C1C4DC] text-center">All calm — no alerts in the last 24 hours.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-[#8A90B0] border-b border-gray-100 dark:border-[#2A2F58]">
                        <th class="px-4 py-2">Area / List</th>
                        <th class="px-4 py-2">When</th>
                        <th class="px-4 py-2">Times</th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="border-b border-gray-50 dark:border-[#23274C]">
                            <td class="px-4 py-2 text-gray-800 dark:text-[#E8EAF6]">{{ $row['area'] ?? '' }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-[#C1C4DC]">{{ $row['when'] ?? '' }}</td>
                            <td class="px-4 py-2 font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ $row['times'] ?? 0 }}x</td>
                            <td class="px-4 py-2">
                                @if($row['needsAck'] ?? true)
                                    <form method="POST" action="{{ route('admin.system-health.acknowledge') }}">
                                        @csrf
                                        <input type="hidden" name="alert_type" value="{{ $detail['type'] ?? '' }}">
                                        <input type="hidden" name="route" value="{{ $row['area'] ?? '' }}">
                                        <input type="hidden" name="counts" value="{{ (int) ($row['times'] ?? 0) }}">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition" style="background: var(--navy);">Acknowledge</button>
                                    </form>
                                @else
                                    <span class="text-xs font-semibold text-green-600 dark:text-[#4ADE80]">Acknowledged ✓{{ ($row['ackBy'] ?? '') !== '' ? ' by ' . $row['ackBy'] : '' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="p-4">{{ $rows->links() }}</div>
        @endif
    @endif
    @if(!empty($acks))
        <div class="p-4 border-t border-gray-100 dark:border-[#2A2F58] text-xs text-gray-600 dark:text-[#C1C4DC] space-y-1">
            <p class="font-semibold uppercase tracking-wide">Acknowledged (who / when)</p>
            @foreach($acks as $ack)
                <p>{{ $ack['by'] ?? 'System' }} — {{ $ack['at'] ?? '' }}{{ ($ack['route'] ?? '') !== '' ? ' — ' . $ack['route'] : '' }}</p>
            @endforeach
        </div>
    @endif
</div>
