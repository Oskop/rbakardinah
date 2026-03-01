<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('RBA Submissions') }} - {{ $header->year }} ({{ $header->period->name }})
            </h2>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.headers.pagu.index', $header) }}"
                    class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                    Set Pagu Global
                </a>
                <a href="{{ route('admin.headers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to
                    List</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Unit Submissions Status</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unit Name</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Update</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($header->submissions as $submission)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $submission->unit->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $submission->status_submission === 'Draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                                    {{ $submission->status_submission === 'Pending Supervisor' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $submission->status_submission === 'Validated' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $submission->status_submission === 'Pagu Issued' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                ">
                                            {{ $submission->status_submission }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $submission->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>