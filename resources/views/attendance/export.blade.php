<table>
    <tr>
        <td colspan="{{ count($dates) + 1 }}"><strong>{{ \Carbon\Carbon::parse($start_date)->format('M d Y') }} To {{ \Carbon\Carbon::parse($end_date)->format('M d Y') }}</strong></td>
        <td></td>
        <td colspan="9"><strong>Summary</strong></td>
    </tr>
    <tr>
        <td colspan="{{ count($dates) + 1 }}"></td>
        <td></td>
        <td><strong>Total Present Days</strong></td>
        <td><strong>Late Marks</strong></td>
        <td><strong>Leave Without Pay</strong></td>
        <td><strong>Week Off</strong></td>
        <td><strong>Earned Leaves</strong></td>
        <td><strong>Sick Leaves</strong></td>
        <td><strong>Comp Off</strong></td>
        <td><strong>Total Payable Days</strong></td>
        <td><strong>Final Payable Salary</strong></td>
        
    </tr>
    
    @php
        $months = [];
        foreach($dates as $date) {
            $monthKey = \Carbon\Carbon::parse($date)->format('Y-m');
            $months[$monthKey][] = $date;
        }
    @endphp

    @foreach($employees as $employee)
        <!-- Employee Header -->
        <tr>
            <td colspan="{{ count($dates) + 1 }}"><strong>Employee Code:</strong> {{ $employee->employee_id }} </td>
            <td></td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['present'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['lm'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['lop'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['wo'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['el'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['sl'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['co'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['total'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
            <td>
                @if(isset($payableDaysTotals[$employee->id]))
                    <strong>{{ number_format($payableDaysTotals[$employee->id]['final_salary'], 1) }}</strong>
                @else
                    <strong>0</strong>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="{{ count($dates) + 1 }}"><strong>Employee Name:</strong> {{ $employee->full_name }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        
        @foreach($months as $monthKey => $monthDates)
            <tr>
                <td colspan="{{ count($monthDates) + 1 }}" style="background-color: #f3f3f3;"><strong>Month: {{ \Carbon\Carbon::parse($monthKey . '-01')->format('F Y') }}</strong></td>
                @for($i = 0; $i < 10; $i++)
                    <td style="background-color: #f3f3f3;"></td>
                @endfor
            </tr>

            <!-- Status Row -->
            <tr>
                <td><strong>Days</strong></td>
                @foreach($monthDates as $date)
                    <td>{{ \Carbon\Carbon::parse($date)->format('d M (D)') }}</td>
                @endforeach
                @for($i = 0; $i < 10; $i++)
                    <td></td>
                @endfor
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                @foreach($monthDates as $date)
                    <td>
                        {{ $statusCodes[$employee->id][$date] ?? '' }}
                    </td>
                @endforeach
                @for($i = 0; $i < 10; $i++)
                    <td></td>
                @endfor
            </tr>
            
            <!-- In Time Row -->
            <tr>
                <td><strong>InTime</strong></td>
                @foreach($monthDates as $date)
                    <td>
                        @isset($attendanceData[$employee->id][$date]['clock_in'])
                            {{ substr($attendanceData[$employee->id][$date]['clock_in'], 0, 5) }}
                        @endisset
                    </td>
                @endforeach
                @for($i = 0; $i < 10; $i++)
                    <td></td>
                @endfor
            </tr>
            
            <!-- Out Time Row -->
            <tr>
                <td><strong>OutTime</strong></td>
                @foreach($monthDates as $date)
                    <td>
                        @isset($attendanceData[$employee->id][$date]['clock_out'])
                            {{ substr($attendanceData[$employee->id][$date]['clock_out'], 0, 5) }}
                        @endisset
                    </td>
                @endforeach
                @for($i = 0; $i < 10; $i++)
                    <td></td>
                @endfor
            </tr>
            
            <!-- Total Time Row -->
            <tr>
                <td><strong>Total</strong></td>
                @foreach($monthDates as $date)
                    <td>
                        @isset($attendanceData[$employee->id][$date]['total'])
                            {{ $attendanceData[$employee->id][$date]['total'] }}
                        @else
                            00:00
                        @endisset
                    </td>
                @endforeach
                @for($i = 0; $i < 10; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    @endforeach
</table>