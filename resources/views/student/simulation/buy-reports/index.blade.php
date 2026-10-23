 @extends('student.simulation.layout')
 @section('title', 'Research Studies')
 @section('template')
   <div class="buyReports" id="buyReports">
     @include('student.simulation.buy-reports.partials.commonHeader')
      <div class="pageTitle">
      <h4>Purchased Research Study Reports</h4>
      <p>This section displays all the research study reports your firm has purchased during the simulation. You can
      download each report from the list below for detailed insights and analysis.</p>
    </div>
    <div class="purchasedReports"> 
      @if ($getReports->isNotEmpty()) 
        <table class="purchasedtable">
          <thead>
            <tr>
              <th width="70" class="text-center">S.No</th>
              <th colspan="2">Report Title</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($getReports as $report)
              <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $report->consultingFee->title }}</td>
                <td width="100" align="center"><a target="_blank"
                    href="{{ route('student.simulation.download.consulting-reports', [
                        'simulation_id' => request('simulation_id'),
                        'quarter_id' => request('quarter_id'),
                        'slug' => $report->consultingFee->slug,
                    ]) }}">Download</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p class="norecords">No research study reports are available for download at this time.</p>
      @endif
     </div>
   </div>
 @endsection
