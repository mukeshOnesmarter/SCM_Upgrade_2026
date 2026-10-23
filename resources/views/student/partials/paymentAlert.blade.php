@isset($commonHeader)
  @if ($commonHeader->payment_status === 'unpaid')
    <div class="paymentAlert">Payment Due – Secure your seat by completing your payment. Click
      <strong><a href="{{ route('student.simulation.payment', ['simulation_id' => request('simulation_id')]) }}">
          Settle Now</a></strong>
    </div>
  @endif
@endisset
