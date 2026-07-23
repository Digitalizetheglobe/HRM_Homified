<table>
    <thead>
        <tr>
            <th colspan="2">{{ __('Employee Name') }}</th>
            <th colspan="3">{{ $employee->full_name }}</th>
        </tr>
        <tr>
            <th colspan="2">{{ __('Date Range') }}</th>
            <th colspan="3">{{ $start_date }} {{ __('to') }} {{ $end_date }}</th>
        </tr>
        <tr>
            <th></th>
        </tr>
        <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Clock In') }}</th>
            <th>{{ __('Clock Out') }}</th>
            <th>{{ __('Late/Early') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->date }}</td>
                <td>{{ $attendance->status }}</td>
                <td>{{ $attendance->clock_in != '00:00:00' ? $attendance->clock_in : '-' }}</td>
                <td>{{ $attendance->clock_out != '00:00:00' ? $attendance->clock_out : '-' }}</td>
                <td>
                    @if($attendance->late != '00:00:00')
                        {{ __('Late: ') }}{{ $attendance->late }}
                    @endif
                    @if($attendance->early_leaving != '00:00:00')
                        {{ __('Early: ') }}{{ $attendance->early_leaving }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
