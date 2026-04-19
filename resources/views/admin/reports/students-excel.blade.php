@extends('layouts.app')

@section('title', 'Export students (Excel)')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Export student list</h1>
        <p class="text-gray-600 mt-2">Download a formatted Excel workbook (.xlsx).</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.reports.students-excel') }}" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Export scope *</label>
                <select name="scope" id="export-scope" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    <option value="all">All students</option>
                    <option value="department">By department</option>
                    <option value="year">By year level</option>
                </select>
            </div>
            <div id="dept-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">— Select —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="year-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Year level *</label>
                @if($yearLevels->isEmpty())
                    <input type="text" name="year_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="e.g. 1st Year">
                    <p class="text-xs text-gray-500 mt-1">No saved year levels yet — type the value to match student records.</p>
                @else
                    <select name="year_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        @foreach($yearLevels as $yl)
                            <option value="{{ $yl }}">{{ $yl }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg">
                Download Excel
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var scope = document.getElementById('export-scope');
    var dept = document.getElementById('dept-wrap');
    var year = document.getElementById('year-wrap');
    function sync() {
        var v = scope.value;
        dept.classList.toggle('hidden', v !== 'department');
        year.classList.toggle('hidden', v !== 'year');
    }
    scope.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
@endsection
