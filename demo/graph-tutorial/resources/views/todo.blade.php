<!-- Copyright (c) Microsoft Corporation.
     Licensed under the MIT License. -->

<!-- <CalendarSnippet> -->
@extends('layout')

@section('content')
<h1>Todo</h1>
@isset($lists)
    @foreach($lists as $list)
        <h3>{{$list->getDisplayName()}}</h3>
        <a class="btn btn-light btn-sm mb-3" href={{action('CalendarController@getNewEventForm')}}>New Task</a>

            <table class="table">
              <thead>
                <tr>
                  <th scope="col">Status</th>
                  <th scope="col">Task</th>
                  <th scope="col">Due</th>
                </tr>
              </thead>
              <tbody>
              @foreach($list->getTasks() as $task)
                    <tr>
                        <td>{{$task->getStatus()->value()}}</td>
                      <td>{{$task->getTitle()}}</td>
                      <td>{{\Carbon\Carbon::parse($task->getDueDateTime())->format('n/j/y g:i A')}}</td>
                    </tr>
                    @endforeach
              </tbody>
            </table>
    @endforeach
@endif
@isset($plannerTasks)
    <h3>Planner Tasks</h3>
    <a class="btn btn-light btn-sm mb-3" href={{action('CalendarController@getNewEventForm')}}>New Task</a>

    <table class="table">
        <thead>
        <tr>
            <th scope="col">Status</th>
            <th scope="col">Task</th>
            <th scope="col">Due</th>
        </tr>
        </thead>
        <tbody>
        @foreach($plannerTasks as $task)
            <tr>
                <td>{{$task->getPercentComplete()}}</td>
                <td>{{$task->getTitle()}}</td>
                <td>{{\Carbon\Carbon::parse($task->getDueDateTime())->format('n/j/y g:i A')}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endisset
@endsection
<!-- </CalendarSnippet> -->
