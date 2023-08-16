@php
    $isEdit = isset($meeting) ? true : false;
    $url = $isEdit ? route('meetings.update', $meeting->id) : route('meetings.store');
@endphp
<form action="{{$url}}" method="post" data-form="ajax-form" data-redirect="{{route('meetings.index')}}">
    @if ($isEdit)
    @method('PUT')
    @endif
    @csrf
    <div class="row">
        <div class="form-group col-lg-6">
            <label for="subject">Subject<span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject" id="subject" value="{{$isEdit ? $meeting->subject : ''}}">
        </div>
        <div class="form-group col-lg-6">
            <label for="attendees_emails">Attendees Emails<span class="text-danger">*</span></label>
            <select class="form-control select2" name="attendees_emails[]" tage="true"  multiple>
               @if ($isEdit)
               @foreach ($meeting->attendees as $attendee)
               <option value="{{$attendee->email}}" selected>{{$attendee->email}}</option>
               @endforeach
               @endif
            </select>
            <span class="text-danger">You can add multiple Emails But not more than or less than two</span>
        </div>

        <div class="form-group col-lg-6">
            <label for="start_time">Start Time <span class="text-danger">*</span></label>
            <input type="datetime-local" name="start_time" class="form-control fc-datepicker" placeholder="MM/DD/YYYY" value="{{$isEdit ? $meeting->start_time : ''}}" >
        </div>

        <div class="form-group col-lg-6">
            <label for="end_time">End Time <span class="text-danger">*</span></label>
            <input type="datetime-local" name="end_time" class="form-control fc-datepicker" placeholder="MM/DD/YYYY" value="{{$isEdit ? $meeting->end_time : ''}}" >
        </div>

        <div class="col-lg-12">
            <button style="float: right;" type="submit" class="btn btn-primary" data-button="submit">Submit</button>
        </div>
    </div>
</form>
