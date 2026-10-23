{{-- SIMULATIONQUARTERS --}}
<div class="simulationQuarters">
  <h5>Quarter Schedule Summary<small>Manage and Monitor Simulation Quarters</small></h5>
  <div class="quarter head">
    <div class="item">&nbsp;</div>
    <div class="item">&nbsp;</div>
    <div class="item">Start Date</div>
    <div class="item">End Date</div>
  </div>
  @foreach ($quarters as $quarter) 
    <div class="quarter">
      {{-- Status Icon --}}
      <div class="item">
        @if ($simulation->is_published_by_current_faculty !== 'No')
          @php
            $isOngoing = $quarter->inprocess_currently === 'Yes';
            $isCompleted = !$isOngoing && $quarter->end_date <= now()->format('Y-m-d');
          @endphp

          @if ($isOngoing)
            <img src="{{ asset('images/icon/qtr-ongoing.svg') }}" alt="Ongoing">
          @elseif ($isCompleted)
            <img src="{{ asset('images/icon/qtr-completed.svg') }}" alt="Completed">
          @else
            <img src="{{ asset('images/icon/qtr-upcoming.svg') }}" alt="Upcoming">
          @endif
        @endif
      </div>

      {{-- Quarter Info --}}
      <div class="item label">Quarter {{ $quarter->quarter_name }}</div>
      <div class="item">{{ \Carbon\Carbon::parse($quarter->start_date)->format('m/d/Y') }}</div>
      <div class="item">{{ \Carbon\Carbon::parse($quarter->end_date)->format('m/d/Y') }}</div>

      {{-- Close Quarter or Status --}}
      <div class="item autoLeft">
        @if ($simulation->is_published_by_current_faculty !== 'No' && ($isOngoing || $isCompleted))
          @if ($isOngoing)
            <a
              href="{{ route('faculty.courses.quartersClosed', [
                  'course_id' => $simulation->id,
                  'quarter_id' => $quarter->quarter_name,
              ]) }} " onclick="return confirm('Are you sure you want to close this quarter? This action cannot be undone.')">Close
              this Quarter</a>
          @elseif ($quarter->close_manualy === 'Yes' && $quarter->quarter_name > 3)
            <small class="closed">Closed Manually</small>
          @endif
        @endif
      </div>

      {{-- Quarter Link or Calendar Icon --}}
      <div class="item">
        @if ($isOngoing || $isCompleted)
          <a href="{{ route('faculty.courses.quarterInfo', [$simulation->id, $quarter->quarter_name]) }}">
            <i class="fa fa-external-link" aria-hidden="true"></i>
          </a>
        @else
          <i class="fa fa-clock-o alphalight" aria-hidden="true"></i>
        @endif
      </div>
    </div>
    <div class="decisionArea">
      <span>Decision Areas:</span> {{ collect(json_decode($quarter->decisions, true))->pluck('title')->implode(', ') }}
    </div>

    @if ($quarter->inprocess_currently === 'Yes')
      <div class="extendDuration">
        <label for="">Extend Current Quarter Duration</label>
        <p>Need more time to complete activities in the current quarter? Extend the end date and we'll automatically
          adjust the next quarters accordingly.</p>
        <ul>
          <li>- Select how many extra days you'd like to add to the current (active) quarter.</li>
          <li>- All future quarters will shift forward by the same number of days.</li>
          <li>- Once saved, the changes will update the simulation schedule.</li>
        </ul>
        <p>How many days would you like to extend the current quarter?</p>

        <form
          action="{{ route('faculty.courses.extendQtrDuration', [
              'course_id' => $quarter->simulation_id,
              'quarter_id' => $quarter->quarter_name,
          ]) }}"
          method="POST" enctype="multipart/form-data" autocomplete="off">
          @csrf
          <input type="hidden" name="start_time" value="{{ old('start_date', $quarter->start_time) }}">
          <input type="hidden" name="end_time" value="{{ old('start_date', $quarter->end_time) }}">
          <input type="hidden" name="simulation_id" value="{{ $quarter->simulation_id }}">
          <input type="hidden" name="quarter_id" value="{{ $quarter->quarter_name }}">
          <input type="hidden" name="is_schedule" value="Yes">
          <div class="daysSelection">
            @for ($count = 1; $count <= 5; $count++)
              <label for="day{{ $count }}"><input type="radio" id="day{{ $count }}" name="days"
                  value="{{ $count }}" {{ $count == 2 ? 'checked' : '' }}>+{{ $count }}
                {{ $count > 1 ? 'Days' : 'Day' }} </label>
            @endfor
          </div>
          <button type="submit">Save and Adjust Schedule</button>
        </form>
      </div>
    @endif
      
    
  
  @endforeach
</div>
