<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AI call logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[100vw] overflow-x-auto mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Recordings and transcripts from Twilio (OpenAI primary, Sarvam fallback).') }}
                </p>
                <table id="calls-table" class="display nowrap" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Call SID</th>
                        <th>Phone</th>
                        <th>Recording URL</th>
                        <th>User text</th>
                        <th>AI response</th>
                        <th>Response audio</th>
                        <th>Error</th>
                        <th>Created</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        $('#calls-table').DataTable({
            ajax: {
                url: '{{ route('admin.calls.data') }}',
                dataSrc: 'data',
            },
            columns: [
                { data: 'id' },
                { data: 'call_sid', defaultContent: '—' },
                { data: 'phone_number', defaultContent: '—' },
                {
                    data: 'recording_url',
                    render: function (data) {
                        if (!data) return '—';
                        const short = data.length > 48 ? data.slice(0, 45) + '…' : data;
                        return '<a href="' + data.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">' + short + '</a>';
                    }
                },
                {
                    data: 'user_text',
                    render: function (data) {
                        if (!data) return '—';
                        return $('<div>').text(data).html();
                    }
                },
                {
                    data: 'ai_response',
                    render: function (data) {
                        if (!data) return '—';
                        return $('<div>').text(data).html();
                    }
                },
                {
                    data: 'response_audio_url',
                    render: function (data) {
                        if (!data) return '—';
                        return '<a href="' + data.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">Play</a>';
                    }
                },
                {
                    data: 'error_message',
                    render: function (data) {
                        if (!data) return '—';
                        return $('<div>').text(data).html();
                    }
                },
                {
                    data: 'created_at',
                    render: function (data) {
                        if (!data) return '—';
                        try {
                            const d = new Date(data);
                            return isNaN(d) ? data : d.toLocaleString();
                        } catch (e) {
                            return data;
                        }
                    }
                }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            scrollX: true
        });
    </script>
@endpush
