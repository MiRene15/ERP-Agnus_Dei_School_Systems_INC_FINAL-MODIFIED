@forelse($fees as $gradeLevel => $gradeFees)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500">({{ $gradeFees->first()->school_year }})</span></h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Term</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Tuition Fee</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Misc Fee</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Total</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($terms as $term)
                @php $fee = $gradeFees->firstWhere('term', $term); @endphp
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2 font-medium text-gray-900">{{ $term }}</td>
                    @if($fee)
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($fee->tuition_fee, 2) }}</td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($fee->misc_fee, 2) }}</td>
                    <td class="py-3 px-2 font-medium text-gray-900">₱ {{ number_format($fee->tuition_fee + $fee->misc_fee, 2) }}</td>
                    <td class="py-3 px-2">
                        <div class="flex gap-1">
                            <a href="{{ route('directress.fees.edit', $fee) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('directress.fees.destroy', $fee) }}" onsubmit="return confirm('Delete this fee schedule?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                    @else
                    <td class="py-3 px-2 text-gray-400" colspan="4">Not set</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 font-semibold">
                    <td class="py-3 px-2 text-gray-800">Annual Total</td>
                    <td class="py-3 px-2 text-gray-800">₱ {{ number_format($gradeFees->sum('tuition_fee'), 2) }}</td>
                    <td class="py-3 px-2 text-gray-800">₱ {{ number_format($gradeFees->sum('misc_fee'), 2) }}</td>
                    <td class="py-3 px-2 text-gray-900">₱ {{ number_format($gradeFees->sum('tuition_fee') + $gradeFees->sum('misc_fee'), 2) }}</td>
                    <td class="py-3 px-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@empty
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <p class="text-sm text-gray-500 text-center py-4">No fee schedules created yet. <a href="{{ route('directress.fees.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Add one</a>.</p>
</div>
@endforelse
