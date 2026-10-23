<div class="commonHeader" id="commonHeader">
  <div class="quarterInformation">
    <h3>Firm {{ str_pad($commonHeader->student_team_number, 2, '0', STR_PAD_LEFT)  }} : {{ Str::upper($commonHeader->title) }}</h3>
    @if ($commonHeader->simulationStatus != 'completed')
      <p><strong>Quarter {{ str_pad($commonHeader->quarter_inprocess->quarter_name, 2, '0', STR_PAD_LEFT) }}</strong>
        In-Process
        | <strong>Deadline</strong>
        {{ \Carbon\Carbon::parse($commonHeader->quarter_inprocess->end_date)->format('M d, Y') }} |
        @if ($commonHeader->quarter_inprocess->days_remaining == 0)
          Ending Today
        @else
          <strong>{{ str_pad($commonHeader->quarter_inprocess->days_remaining, 2, '0', STR_PAD_LEFT) }} </strong>
          {{ Str::plural('Day', $commonHeader->quarter_inprocess->days_remaining) }} Remaining
        @endif
      </p>
    @endif
    <div class="quarters">
      @if ($commonHeader->simulationQuarters)
        <ul>
          @foreach ($commonHeader->simulationQuarters as $quarter)
            <li>
              <a href="{{ route(Route::currentRouteName(), [$commonHeader->id, $quarter->quarter_name]) }}"
                class="{{ $quarter->quarter_name < $commonHeader->quarter_inprocess->quarter_name ? 'completed' : '' }}
              {{ $quarter->quarter_name == $commonHeader->quarter_selected ? 'active' : '' }} ">
                <span> {{ $quarter->quarter_name }} </span>
                @if ($quarter->quarter_name == $commonHeader->quarter_selected)
                  <strong> Quarter Selected</strong>
                @endif
              </a>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</div>
