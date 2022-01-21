<?php
// Copyright (c) Microsoft Corporation.
// Licensed under the MIT License.

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use App\TokenStore\TokenCache;
use App\TimeZones\TimeZones;

class TodoController extends Controller
{
  public function todo()
  {
    $viewData = $this->loadViewData();

    $graph = $this->getGraph();

    // Get user's timezone
    $timezone = TimeZones::getTzFromWindows($viewData['userTimeZone']);

    // Get start and end of week
    $startOfWeek = new \DateTimeImmutable('sunday -1 week', $timezone);
    $endOfWeek = new \DateTimeImmutable('sunday', $timezone);

    $viewData['dateRange'] = $startOfWeek->format('M j, Y').' - '.$endOfWeek->format('M j, Y');

    $queryParams = array(
      'startDateTime' => $startOfWeek->format(\DateTimeInterface::ISO8601),
      'endDateTime' => $endOfWeek->format(\DateTimeInterface::ISO8601),
      // Only request the properties used by the app
      '$select' => 'subject,organizer,start,end',
      // Sort them by start time
      '$orderby' => 'start/dateTime',
      // Limit results to 25
      '$top' => 25
    );

    // Append query parameters to the '/me/calendarView' url
    $getEventsUrl = '/me/calendarView?'.http_build_query($queryParams);

    $getTaskListsUrl = '/me/todo/lists';


    $lists = $graph->createRequest('GET', $getTaskListsUrl)
      ->setReturnType(Model\TodoTaskList::class)
      ->execute();

    foreach ($lists as $list) {
        $list->setTasks($graph->createRequest('get',"/me/todo/lists/{$list->getID()}/tasks")
                            ->setReturnType(Model\TodoTask::class)
                            ->execute());
    }

    $plannerTasks = $graph->createRequest('GET', '/me/planner/tasks')
                            ->setReturnType(Model\PlannerTask::class)
                            ->execute();

    return view('todo', [
        'lists' => $lists,
        'plannerTasks' => $plannerTasks,
    ]);
  }

  // <getNewEventFormSnippet>
  public function getNewEventForm()
  {
    $viewData = $this->loadViewData();

    return view('newevent', $viewData);
  }
  // </getNewEventFormSnippet>

  // <createNewEventSnippet>
  public function createNewEvent(Request $request)
  {
    // Validate required fields
    $request->validate([
      'eventSubject' => 'nullable|string',
      'eventAttendees' => 'nullable|string',
      'eventStart' => 'required|date',
      'eventEnd' => 'required|date',
      'eventBody' => 'nullable|string'
    ]);

    $viewData = $this->loadViewData();

    $graph = $this->getGraph();

    // Attendees from form are a semi-colon delimited list of
    // email addresses
    $attendeeAddresses = explode(';', $request->eventAttendees);

    // The Attendee object in Graph is complex, so build the structure
    $attendees = [];
    foreach($attendeeAddresses as $attendeeAddress)
    {
      array_push($attendees, [
        // Add the email address in the emailAddress property
        'emailAddress' => [
          'address' => $attendeeAddress
        ],
        // Set the attendee type to required
        'type' => 'required'
      ]);
    }

    // Build the event
    $newEvent = [
      'subject' => $request->eventSubject,
      'attendees' => $attendees,
      'start' => [
        'dateTime' => $request->eventStart,
        'timeZone' => $viewData['userTimeZone']
      ],
      'end' => [
        'dateTime' => $request->eventEnd,
        'timeZone' => $viewData['userTimeZone']
      ],
      'body' => [
        'content' => $request->eventBody,
        'contentType' => 'text'
      ]
    ];

    // POST /me/events
    $response = $graph->createRequest('POST', '/me/events')
      ->attachBody($newEvent)
      ->setReturnType(Model\Event::class)
      ->execute();

    return redirect('/todo');
  }
  // </createNewEventSnippet>

  private function getGraph(): Graph
  {
    // Get the access token from the cache
    $tokenCache = new TokenCache();
    $accessToken = $tokenCache->getAccessToken();

    // Create a Graph client
    $graph = new Graph();
    $graph->setAccessToken($accessToken);
    return $graph;
  }
}
