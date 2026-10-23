@extends('faculty.layout')
@section('title', 'Invitees')
@section('template')
  <div class="coursesTemplate">
    <div class="sectionContainer">
      <div class="sectionTitle">
        <h4>Invited Students for Simulation</h4>
        <p>Browse the list of students who have been invited to participate in the simulation. Monitor their response
          status and manage invitations efficiently.</p>
      </div>

      @if (count($invitees) > 0)
        <div class="invitees">
          @foreach ($invitees as $item)
            <div class="listingRow">
              <div class="item graphic">
                <img src="{{ URL::asset('images/icon/invitees.svg') }}" alt="">
              </div>
              <div class="item title">
                <label for="title"> {{ strtolower($item->email) }}</label>
                <small>Invited At: {{ \Carbon\Carbon::parse($item->invited_at)->format('m/d/Y') }} |
                  {{ $item->simulation->title }} </small>
              </div>
              <div class="item status">
                @if (is_null($item->accepted_at))
                  <small>Not accepted yet.</small>
                @else
                  <img src="{{ URL::asset('images/icon/check-green.svg') }}" alt="">
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @else
        <p>No invitations have been sent to any students yet.</p>
      @endif
    </div>
  </div>
@endsection
