@extends('admin.layout')
@section('title', 'Demand : Historical Data And Statistical')
@section('template')

  @if (Session::has('message'))
    <p class="message" id="successMessage">{{ Session::get('message') }}</p>
  @endif

  @if (Session::has('error'))
    <p class="message" id="errorMessage">{{ Session::get('error') }}</p>
  @endif

  @if (Session::has('setupInitialDemand'))
    <p class="message" id="errorMessage">{{ Session::get('setupInitialDemand') }}</p>
  @endif

  <div class="wraptemplateBody">
    <div class="templateBody">
      <div class="simulationTemplate" id="simulationTemplate">
        {{-- COURSE HEADER --}}
        <div class="commonHeader">
          <h3>{{ Str::upper($course->title) }}</h3>
          <p><strong>Session Code:</strong> {{ $course->simulation_code }} | <strong>Organization:</strong>
            {{ $course->organization }} | <strong>Start Date:</strong> {{ $course->start_date }} | <strong>End
              Date:</strong> {{ $course->end_date }} </p>
        </div>
        {{-- PAGETITLE --}}
        <div class="pageTitle">
          <h4>Industry Initial Demand</h4>
          <p>Analyze the initial market demand across industries to set the foundation for your simulation. Define
            expected demand patterns for products and regions, ensuring a realistic and strategic starting point for
            participants.</p>
        </div>
      </div>
      <div class="templateCrud">
        <div class="dataForm">
          <form action="{{ route('admin.courses.generateInitialDemand', $course->id) }}" id="createRecord" method="POST"
            enctype="multipart/form-data" autocomplete="off">
            @csrf
            <input type="hidden" name="simulation_id" value="{{ $course->id }}">
            <div class="demandGeneration">
              @foreach ($initialDemands as $initialDemand)
                <div class="regionInitialDemand">
                  <div class="regionTitle">
                    <h4>Region ({{ $initialDemand->id }}) {{ $initialDemand->custome_name }}</h4>
                  </div>
                  @if ($initialDemand->products)
                    <div class="productWrap">
                      @foreach ($initialDemand->products as $product)
                        <div class="product">
                          <div class="productTitle">
                            <h5>Product ({{ $product->id }}) {{ $product->name }}</h5>
                          </div>
                          @if ($product->demand_channels)
                            <div class="demandChannels">
                              @foreach ($product->demand_channels as $channel)
                                <div class="channel">
                                  <div class="item title">Demand ({{ $channel->title }})</div>
                                  @if ($channel->id == 1)
                                    <div class="item">{{ number_format($channel->demand->demand_channel_01) }}</div>
                                  @else
                                    <div class="item">{{ number_format($channel->demand->demand_channel_02) }}</div>
                                  @endif
                                </div>
                              @endforeach
                            </div>
                          @endif
                        </div>
                      @endforeach
                    </div>
                  @endif

                </div>
              @endforeach
            </div>
            <h6>Growth Parameters</h6>
            <div class="dataRow">
              <div class="inputField">
                <label for="title">GDP (%) *</label>
                <input type="text" name="gdp_percentage" maxlength="2" value="{{ old('gdp_percentage') ?? '5' }}">
                <small>The GDP percentage should be a numeric value ranging from 2 to 10.</small>
                @error('gdp_percentage')
                  <small class="error_text">{{ $message }}</small>
                @enderror
              </div>
              <div class="inputField">
                <label for="title">GDP DRIFT (%) *</label>
                <input type="text" name="drift" maxlength="2" value="{{ old('drift') ?? '0.5' }}">
              </div>
            </div>

            <div class="dataRow action_button">
              <button>Generate Forcast Demand</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
