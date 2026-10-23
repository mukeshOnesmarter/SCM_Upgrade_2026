@extends('student.layout')
@section('title', 'Your Invitation History')
@section('template')
  <div class="invitationContainer">
    <div class="pageTitle">
      <h4>Your Invitation History</h4>
      <p>This page allows you to view all the invitations sent to you for various simulations.</p>
      <p>Check your participation status, along with the dates of each invitation, to keep track of your upcoming
        opportunities.</p>
    </div>
    @if (count($allInvitations) > 0)
      @foreach ($allInvitations as $invitations)
        @if ($invitations->is_faculty_published != 'No')
          <div class="invitation">
            <div class="item">
              @if (is_null($invitations->is_student_join))
                <img src="{{ URL::asset('images/icon/invitations.svg') }}" alt="">
              @else
                <img src="{{ URL::asset('images/icon/invitations-hover.svg') }}" alt="">
              @endif
            </div>
            <div class="item detail">
              <h6>{{ $invitations->simulation->title }}</h6>
              <p><strong>Organization:</strong> {{ $invitations->simulation->organization }} | <strong>Faculty:</strong>
                {{ ucfirst($invitations->faculty->first_name . ' ' . $invitations->faculty->last_name) }} |
                <strong>Simulation Code:</strong> {{ $invitations->simulation->share_code }}
              </p>
              <p><strong><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Date of Invitation:</strong>
                {{ $invitations->invited_at }} | <strong>Simulation Start Date</strong> :
                {{ \Carbon\Carbon::parse($invitations->simulation->start_date)->format('M d, Y') }}</p>
            </div>

            @if ($invitations->simulation->start_date > now()->format('Y-m-d'))
              <div class="item status">
                <span class="disabled"><img src="{{ asset('images/icon/clock.svg') }}" alt=""> Simulation Has Not Started Yet</span> 
              </div>
            @else
              @if ($invitations->is_student_join !== null)
                <div class="item status">
                  <span><img src="{{ URL::asset('images/icon/check-green.svg') }}" alt=""> Simulation Successfully Joined</span>
                  {{-- {{ $invitations->is_student_join }}  --}}
                </div>
              @else
                <div class="item status">
                  <form method="POST" action="{{ route('student.review-Simulation') }}" autocomplete="off"
                    class="formContainer">
                    @csrf
                    <input type="hidden" name="simulation_code" value="{{ $invitations->simulation->share_code }}">
                    <button> Join the Simulation</button>
                  </form>
                </div>
              @endif
            @endif
          </div>
        @endif
      @endforeach
    @else
      <p>You are not invited to participate in any simulation.</p>
    @endif
  </div>
@endsection
