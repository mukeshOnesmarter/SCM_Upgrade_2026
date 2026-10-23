<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>FLEXEE: Supply Chain Management</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/app.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/jquery-ui.css') }}" />
</head>

<body>
  <div class="wrapper">
    <div class="landingPage">
      <div class="heroBanner">
        <img src="{{ URL::asset('images/logo-1x.svg') }}" alt="">
        <h1>Production Server</h1>
        {{-- <h1>xperience the <strong>End-to-End Supply Chain</strong><span>Enhanced with AI Insights</span></h1> --}}
        <p>Compete with the built-in AI Player and learn through the AI Faculty Advisor - Real-world decisions. Real-time analytics. Real learning.</p>
        <ul> 
          <li><a href="{{ route('admin.login') }}" target="_blank">Admin Login</a></li>
          <li><a href="{{ route('faculty.login') }}" target="_blank">Faculty Login</a></li>
          <li><a href="{{ route('student.login') }}" target="_blank">Student Login</a></li>
        </ul>
      </div>


      <div class="wrapSection">
        <div class="flexeeFeatures innerContainer">
          <h3>Why FLEXEE?
            <small>Because learners don’t just study supply chains - they run them, end-to-end, with
              AI-powered insights.</small>
          </h3>
          <div class="featureContainer">
            <div class="featureItem">
              <a href="{{ route('videos.forecasting') }}" target="_blank">
                <div class="graphic"><img src="{{ asset('images/icon-forecasting.svg') }}" alt=""></div>
                <label for="title">Forecasting</label>
              </a>
            </div>
            <div class="featureItem">
              <a href="{{ route('videos.procurement') }}" target="_blank">
                <div class="graphic"><img src="{{ asset('images/icon-procurement.svg') }}" alt=""></div>
                <label for="title">Procurement</label>
              </a>
            </div>
            <div class="featureItem">
              <a href="{{ route('videos.manufacturing') }}" target="_blank">
                <div class="graphic"><img src="{{ asset('images/icon-manufacturing.svg') }}" alt=""></div>
                <label for="title">Manufacturing</label>
              </a>
            </div>
            <div class="featureItem">
              <a href="{{ route('videos.distribution') }}" target="_blank">
                <div class="graphic"><img src="{{ asset('images/icon-distribution.svg') }}" alt=""></div>
                <label for="title">Distribution</label>
              </a>
            </div>
            <div class="featureItem">
              <a href="{{ route('videos.transportation') }}" target="_blank">
                <div class="graphic"><img src="{{ asset('images/icon-transportation.svg') }}" alt=""></div>
                <label for="title">Transportation</label>
              </a>
            </div>
            <div class="featureItem">
              <a href="{{ route('videos.inventory') }}" target="_blank">
                <div class="graphic"><img src="{{ asset('images/icon-inventory.svg') }}" alt=""></div>
                <label for="title">Inventory</label>
              </a>
            </div>
          </div>
          <p>Simulate procurement, production, inventory, logistics, marketing, and financial decisions across multiple
            quarters <span>designed for academic and strategic learning environments.</span> </p>
        </div>
      </div>

       

       

      <div class="wrapSection bgGray">
        <div class="innerContainer graphicContainer">
          <img src="{{ asset('images/graphic-02.png') }}" alt="">
        </div>
      </div>

      <div class="wrapSection">
        <div class="flexeeFlow innerContainer">
          <div class="widgetContainer">
            <div class="decisionsContainer">
              <div class="sectionTitle">
                <h3 class="text-black">What Makes It Unique?</h3>
              </div>
              <p>AI-Enhanced Analysis <span>Transform raw numbers into meaningful dashboards.</span></p>
              <p>Boardroom Scorecard <span>Track financial, operational, customer, and external KPIs.</span></p>
              <p>Learning Support <span>AI nudges learners with after-action insights.</span></p>
              <p>Postponed Production <span>Convert “shells” at DCs into finished goods to match demand.</span></p>
              <p>Cross-Docking & RFID <span>simulate retailer demands and logistics cost trade-offs.</span></p>
              <p>Emergency Levers <span>Production & procurement premiums that affect service.</span></p>
              <p>Faculty Dashboards <span>Monitor teams, export reports, with AI-driven summaries.</span></p>
            </div>
            <div class="processContainer">
              <div class="sectionTitle">
                <h3>How It Works</h3>
                <span>How the Simulation Works?</span>
              </div>
              <div class="videoContainer">
                <div class="video">
                  <a href="{{ route('videos.form-teams') }}" target="_blank">
                    <img src="{{ asset('images/PlayCircle.svg') }}" alt="">
                    <p><span>Form Teams -</span> Begin by creating student groups for simulation play</p>
                  </a>
                </div>
                <div class="video">
                  <a href="{{ route('videos.make-decisions') }}" target="_blank">
                    <img src="{{ asset('images/PlayCircle.svg') }}" alt="">
                    <p><span>Make Decisions -</span> Take control each quarter with impactful business choices</p>
                  </a>
                </div>
                <div class="video">
                  <a href="{{ route('videos.analyze-results') }}" target="_blank">
                    <img src="{{ asset('images/PlayCircle.svg') }}" alt="">
                    <p><span>Analyze Results -</span> Track the impact of your decisions across finance & operations</p>
                  </a>
                </div>
                <div class="video">
                  <a href="{{ route('videos.refine-strategy') }}" target="_blank">
                    <img src="{{ asset('images/PlayCircle.svg') }}" alt="">
                    <p><span>Refine Strategy -</span> Use data-driven feedback to improve your next move</p>
                  </a>
                </div>
              </div>
            </div>
          </div>
          <div class="smallDevice">
            <img src="{{ asset('images/smallDevice.svg') }}" alt="">
          </div>
        </div>
      </div>

      <div class="wrapSection">
        <div class="contactUs innerContainer">
          <a id="ContactUs"></a>
          <h3>Have Questions or Want to Learn More? <span>Get in Touch with Chuck Nemer, <strong>SCM Expert & Advisor</strong></span>
          </h3>
          <div class="contactCard">
            <div class="advisorPhoto">
              <img src="{{ asset('images/chuck-nemer.png') }}" alt="">
            </div>
            <div class="advisorinfo">
              <strong>Chuck Nemer </strong><br>
              CPIM, CSCP, CLTD, CTSC - FLEXEE SCM<br>
              Sales, Implementation, Training<br>
              chuck@theguruofbiz.com<br>
              +1-651-271-7624
            </div>
          </div>


        </div>
      </div>

      <div class="wrapSection">
        <div class="fotter innerContainer">
          <ul>
            <li><a href="https://x.com/FlexeeLLC" target="_blank"><img src="{{ asset('images/icon/twitter.svg') }}"
                  alt=""></a></li>
            <li><a href="https://www.youtube.com/@FlexeeLLC" target="_blank"><img
                  src="{{ asset('images/icon/youTube.svg') }}" alt=""></a></li>
            <li><a href="#" target="_blank"><img src="{{ asset('images/icon/linkedIn.svg') }}"
                  alt=""></a></li>
          </ul>
          <p>© {{ now()->format('Y') }} FLEXEE SCM Simulation Platform. <span>All rights reserved.</span></p> 
          @if (config('app.commit'))
            <p class="build">Build {{ substr((string) config('app.commit'), 0, 7) }}</p>
          @endif
        </div>
      </div>

    </div>
  </div>
</body>

</html>
