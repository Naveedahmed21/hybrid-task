<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Google\Client;
use App\Models\Meeting;
use App\Models\Attendee;
use Google\Service\Calendar;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Google_Service_Calendar_Event;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setClientId(config('google_credential.GOOGLE_CLIENT_ID'));
        $client->setRedirectUri(config('google_credential.GOOGLE_REDIRECT_URI'));
        $client->addScope(Calendar::CALENDAR_EVENTS);
        return redirect($client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new Client();
        $client->setClientId(config('google_credential.GOOGLE_CLIENT_ID'));
        $client->setClientSecret(config('google_credential.GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(config('google_credential.GOOGLE_REDIRECT_URI'));
        $accessToken = $client->fetchAccessTokenWithAuthCode($request->code);
        $client->setAccessToken($accessToken);

        Cache::put('google_access_token', $accessToken, now()->addSeconds($accessToken['expires_in']));

        return redirect(route('dashboard'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('backend.meetings.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.meetings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required',
            'start_time' => 'required| date',
            'end_time' => 'required | date',
            'attendees_emails.*' => 'required |email',
        ],[
            'attendees_emails.*.email' => 'Email Must be a valid email address'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()->first(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            DB::beginTransaction();
            if(count($request->attendees_emails) < 2 || count($request->attendees_emails) > 2){
                return response()->json([
                    'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => "You can add only two attendees emails",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            $meeting = Meeting::create([
            'subject' => $request->subject,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'organizer_id' => auth()->user()->id
            ]);

            foreach($request->attendees_emails as $email){
                Attendee::create([
                    'meeting_id' => $meeting->id,
                    'email' => $email,
                ]);
            }
            DB::commit();
            $accessToken = Cache::get('google_access_token');

            if ($accessToken) {

                $client = new Client();
                $client->setAccessToken($accessToken);

                $service = new Calendar($client);
                $event = new Google_Service_Calendar_Event([
                    'summary' => $meeting->subject,
                    'start' => [
                        'dateTime' => Carbon::parse($meeting->start_time)->toRfc3339String(),
                        'timeZone' => 'UTC',
                    ],
                    'end' => [
                        'dateTime' => Carbon::parse($meeting->end_time)->toRfc3339String(),
                        'timeZone' => 'UTC',
                    ],
                    'attendees' => [
                        ['email' => $meeting->attendees[0]->email],
                        ['email' => $meeting->attendees[0]->email],
                    ],
                ]);

                $calendarId = 'primary';
                $event = $service->events->insert($calendarId, $event);
                $meeting->google_event_id = $event->id;
                $meeting->save();
            }
            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => "Meeting Created Sucessfuuly"
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $meeting = Meeting::with('attendees')->findOrFail($id);
        return view('backend.meetings.edit', compact('meeting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'attendees_emails.*' => 'required|email',
        ], [
            'attendees_emails.*.email' => 'Email must be a valid email address'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()->first(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();
            $meeting = Meeting::findOrFail($id);

            $meeting->update([
                'subject' => $request->subject,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'organizer_id' => auth()->user()->id
            ]);
            $meeting->attendees()->delete();
            foreach ($request->attendees_emails as $email) {
                Attendee::create([
                    'meeting_id' => $meeting->id,
                    'email' => $email,
                ]);
            }

            $accessToken = Cache::get('google_access_token');
            if ($accessToken && $meeting->google_event_id) {
                $client = new Client();
                $client->setAccessToken($accessToken);

                $service = new Calendar($client);
                $event = new Google_Service_Calendar_Event([
                    'summary' => $meeting->subject,
                    'start' => [
                        'dateTime' => Carbon::parse($meeting->start_time)->toRfc3339String(),
                        'timeZone' => 'UTC',
                    ],
                    'end' => [
                        'dateTime' => Carbon::parse($meeting->end_time)->toRfc3339String(),
                        'timeZone' => 'UTC',
                    ],
                    'attendees' => collect($meeting->attendees)->map(function ($attendee) {
                        return ['email' => $attendee->email];
                    })->toArray(),
                ]);

                $calendarId = 'primary';
                $googleEventId = $meeting->google_event_id;
                $service->events->update($calendarId, $googleEventId, $event);
            }

            DB::commit();

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => "Meeting Updated Successfully"
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $meeting = Meeting::findOrFail($id);

            $meeting->attendees()->delete();
            $accessToken = Cache::get('google_access_token');
            if ($accessToken && $meeting->google_event_id ) {
                $client = new Client();
                $client->setAccessToken($accessToken);

                $service = new Calendar($client);
                $calendarId = 'primary';
                $googleEventId = $meeting->google_event_id;
                $service->events->delete($calendarId, $googleEventId);
            }
            $meeting->delete();
            DB::commit();

            return response()->json([
                'status' => JsonResponse::HTTP_OK,
                'message' => "Meeting Deleted Successfully"
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function dataTable()
    {
        $meetings = Meeting::with(['attendees', 'organiser'])->get();
        return DataTables::of($meetings)
            ->addColumn('actions', function ($record) {
                $actions = '';
                $actions = '<div class="btn-list">';
                $actions .= '<a href="' . route('meetings.edit', $record->id) . '"  data-title="Edit Meeting" class="btn btn-sm btn-primary">
                                        <span class="fe fe-edit"> </span>
                                    </a>';
                $actions .= '<button type="button" class="btn btn-sm btn-danger delete" data-url="' . route('meetings.destroy', $record->id) . '" data-method="get" data-datatable="#meetings_datatable">
                                        <span class="fe fe-trash-2"> </span>
                                    </button>';

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('subject', function ($record) {
                return $record->subject;
            })
            ->addColumn('date_time', function ($record) {
                return $record->date_time;
            })
            ->addColumn('organizer', function ($record) {
                return $record->organiser->name;
            })
            ->rawColumns(['actions', 'subject', 'date_time', 'organizer'])
            ->addIndexColumn()->make(true);
    }
}
