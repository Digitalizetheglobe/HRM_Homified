@extends('email.common')

@section('content')
<div style="font-family:Open Sans, Helvetica, Arial, sans-serif;font-size:13px;line-height:22px;text-align:left;color:#797e82;">
    <h2 style="text-align:center; color: #6676EF; line-height:32px; margin-bottom: 20px;">Follow-Up Reminder</h2>
    
    <p style="margin: 10px 0; text-align: left;">Dear Employee,</p>
    
    <p style="margin: 10px 0; text-align: left;">This is a reminder that you have a follow-up scheduled for today with the following client:</p>
    
    <div style="background-color: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Client Name:</strong> {{ isset($timeSheet) && $timeSheet->full_name ? $timeSheet->full_name : 'N/A' }}</p>
        
        @if(isset($timeSheet) && $timeSheet->mobile_no)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Client Phone Number:</strong> {{ $timeSheet->mobile_no }}</p>
        @endif
        
        @if(isset($timeSheet) && $timeSheet->email_id)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Email:</strong> {{ $timeSheet->email_id }}</p>
        @endif
        
        @if(isset($timeSheet) && $timeSheet->project && $timeSheet->project->project_name)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Project:</strong> {{ $timeSheet->project->project_name }}</p>
        @endif
        
        @if(isset($timeSheet) && $timeSheet->primary_reason)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Primary Reason:</strong> {{ $timeSheet->primary_reason }}</p>
        @endif
        
        @if(isset($timeSheet) && $timeSheet->square_feet_range)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Area Requirement:</strong> {{ $timeSheet->square_feet_range }}</p>
        @endif
        
        @if(isset($timeSheet) && $timeSheet->price_range)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Price Range:</strong> {{ $timeSheet->price_range }}</p>
        @endif
        
        @if(isset($timeSheet) && $timeSheet->follow_up_date)
        <p style="margin: 10px 0; text-align: left; color: #333333;"><strong>Follow-Up Date:</strong> 
            @php
                try {
                    echo \Carbon\Carbon::parse($timeSheet->follow_up_date)->format('F d, Y');
                } catch (\Exception $e) {
                    echo $timeSheet->follow_up_date;
                }
            @endphp
        </p>
        @endif
    </div>
    
    @if(isset($timeSheet) && !empty($timeSheet->executive_remark))
    <div style="margin: 20px 0;">
        <h3 style="color: #6676EF; margin-bottom: 10px; text-align: left; font-size: 16px;">Executive Remark:</h3>
        <div style="background-color: #ffffff; padding: 15px; border-left: 4px solid #6676EF; margin: 10px 0;">
            <p style="margin: 0; color: #555555; text-align: left; line-height: 1.6;">
                {{ $timeSheet->executive_remark }}
            </p>
        </div>
    </div>
    @endif
    
    @php
        $feedbacks = [];
        if (isset($timeSheet) && !empty($timeSheet->feedback_information)) {
            if (is_array($timeSheet->feedback_information)) {
                $feedbacks = $timeSheet->feedback_information;
            } else {
                $decoded = json_decode($timeSheet->feedback_information, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $feedbacks = $decoded;
                }
            }
        }
    @endphp
    
    @if(!empty($feedbacks) && is_array($feedbacks) && count($feedbacks) > 0)
    <div style="margin: 20px 0;">
        <h3 style="color: #6676EF; margin-bottom: 15px; text-align: left; font-size: 16px;">Feedback Details:</h3>
        @foreach($feedbacks as $index => $feedback)
            @if(is_array($feedback) && isset($feedback['description']))
            <div style="background-color: #ffffff; padding: 15px; border-left: 4px solid #6676EF; margin-bottom: 15px;">
                <h4 style="color: #6676EF; margin-top: 0; margin-bottom: 10px; text-align: left; font-size: 14px; font-weight: bold;">Customer Feedback {{ $index + 1 }}</h4>
                <p style="margin: 0; color: #555555; text-align: left; line-height: 1.6;">
                    {{ $feedback['description'] }}
                </p>
                @if(isset($feedback['followup_date']) && !empty($feedback['followup_date']))
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #888888; text-align: left;">
                    <strong>Follow-up Date:</strong> 
                    @php
                        try {
                            echo \Carbon\Carbon::parse($feedback['followup_date'])->format('F d, Y');
                        } catch (\Exception $e) {
                            echo $feedback['followup_date'];
                        }
                    @endphp
                </p>
                @endif
                @if(isset($feedback['added_by']) && !empty($feedback['added_by']))
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #888888; text-align: left;">
                    <strong>Added by:</strong> {{ $feedback['added_by'] }}
                    @if(isset($feedback['created_at']) && !empty($feedback['created_at']))
                    @php
                        try {
                            echo ' on ' . \Carbon\Carbon::parse($feedback['created_at'])->format('M d, Y h:i A');
                        } catch (\Exception $e) {
                            echo ' on ' . $feedback['created_at'];
                        }
                    @endphp
                    @endif
                </p>
                @endif
            </div>
            @endif
        @endforeach
    </div>
    @endif
    
    <p style="margin: 20px 0; text-align: left;">
        Please ensure you follow up with this client as scheduled.
    </p>
    
    <p style="margin: 20px 0; text-align: left; color: #666666;">
        Best regards,<br>
        {{ config('app.name') }} System
    </p>
</div>
@endsection

