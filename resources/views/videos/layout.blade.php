<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/app.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/jquery-ui.css') }}" />
</head>

<body>
  <div class="wrapper">
    <div class="flexeeVideos">
      <div class="asideLeft">
        <div class="titleGraphic">
          <img src="{{ asset('images/flexee-videos.svg') }}" alt="">
        </div>
        <div class="videos">
          <ul>
            <li><a href="{{ route('videos.intorduction') }}">Introduction: <small>What the Game Is About</small></a>
            </li>
            <li><a href="{{ route('videos.form-teams') }}">Getting Started: <small>Forming Teams & First
                  Steps</small></a></li>
            <li><a href="{{ route('videos.refine-strategy') }}">Strategy: <small>Winning Approaches & Key
                  Trade-Offs</small></a></li>

            {{-- <li><a href="{{ route('videos.forecasting') }}">Forecasting</a></li> --}}
            <li><a href="{{ route('videos.procurement') }}">Procurement: <small>Smart Sourcing & Cost
                  Control</small></a></li>
            <li><a href="{{ route('videos.manufacturing') }}">Manufacturing: <small>Production Planning &
                  Efficiency</small></a></li>
            <li><a href="{{ route('videos.inventory') }}">Inventory: <small>Balancing Stock with
                  Performance</small></a></li>
            <li><a href="{{ route('videos.distribution') }}">Distribution: <small>Delivering Products to
                  Market</small></a></li>
            <li><a href="{{ route('videos.transportation') }}">Transportation: <small>Logistics & Delivery
                  Decisions</small></a></li>
            <li><a href="{{ route('videos.make-decisions') }}">Decision Making: <small>How Your Choices Shape
                  Results</small></a></li>
            <li><a href="{{ route('videos.analyze-results') }}">Results: <small>Performance Highlights &
                  Insights</small></a></li>
          </ul>
        </div>
        <div class="socialNetwork">
          <ul>
            <li><a href="#"><img src="{{ asset('images/icon/twitter.svg') }}" alt=""></a></li>
            <li><a href="#"><img src="{{ asset('images/icon/instagram.svg') }}" alt=""></a></li>
            <li><a href="#"><img src="{{ asset('images/icon/linkedIn.svg') }}" alt=""></a></li>
          </ul>
        </div>
        <div class="back">
          <a href="/"> <i class="fa fa-long-arrow-left" aria-hidden="true"></i> Back to Main Website</a>
        </div>
      </div>
      <div class="videoContainer">
        <div class="embedYoutubePlayer">
          <div class="youtubePlayer">
            <div id="player"></div>
          </div>
          <div class="unmuteBtn">
            <button id="unmuteBtn"><img src="{{ asset('images/icon/unmute.svg') }}" alt=""></button>
          </div>
          <div class="contactUs">
            <h3>Have Questions or Want to Learn More? <span>Get in Touch with Chuck Nemer, SCM Expert & Advisor</span>
            </h3>
            <div class="contactCard">
              <div class="advisorPhoto">
                <img src="{{ asset('images/chuck-nemer.png') }}" alt="">
              </div>
              <div class="advisorinfo">
                <strong>Chuck Nemer </strong><br>
                CPIM, CSCP, CLTD, CTSC - FLEXEE SCM Simulation<br>
                Sales, Implementation, Training<br>
                chuck@theguruofbiz.com<br>
                +1-651-271-7624
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
<script>
  // Load YouTube IFrame API
  var tag = document.createElement('script');
  tag.src = "https://www.youtube.com/iframe_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

  var player;

  // API callback
  function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
      videoId: "@yield('videoId')",
      playerVars: {
        autoplay: 1,
        controls: 1,
        mute: 1,
        vq: 'hd1080',
        modestbranding: 1,
        rel: 0,
        iv_load_policy: 3
      },
      events: {
        'onReady': onPlayerReady
      }
    });
  }

  function onPlayerReady(event) {
    event.target.playVideo();
  }

  // Unmute button
  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('unmuteBtn').addEventListener('click', function() {
      player.unMute();
      this.style.display = 'none';
    });
  });
</script>

</html>
