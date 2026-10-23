<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/student.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/font-awesome.min.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('css/jquery-ui.css') }}" />
</head>

<body>
  <div class="wrapper">
    <div class="adminTemplate">
      <div class="asideLeft">
        <div class="logo">
          <a href="#"> <img src="{{ URL::asset('images/logo-student-admin.svg') }}" alt=""> </a>
        </div>
        <div class="flexeeManual">
          <h4>FLEXEE MANUAL</h4>
          <ul> 
            <li><a class="{{ request()->routeIs('student.manual.chapter01') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter01') }}">Introduction <small>Read Chapter 01</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter02') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter02') }}">Product Description <small>Read Chapter 02</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter03') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter03') }}">Procurement Decisions <small>Read Chapter 03</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter04') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter04') }}">Manufacturing Decisions <small>Read Chapter 04</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter05') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter05') }}">Distribution Decisions <small>Read Chapter 05</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter06') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter06') }}">Transportation Decisions <small>Read Chapter 06</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter07') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter07') }}">Service Decision <small>Read Chapter 07</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter08') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter08') }}">Demand Mgt. Decisions <small>Read Chapter 08</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter09') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter09') }}">Forecasting Decisions <small>Read Chapter 09</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter10') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter10') }}">Enterprise Integration <small>Read Chapter 10</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter11') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter11') }}">Financial Reports <small>Read Chapter 11</small></a></li> 
            <li><a class="{{ request()->routeIs('student.manual.chapter12') ? 'active' : '' }}"
                href="{{ route('student.manual.chapter12') }}">Performance Evaluation <small>Read Chapter 12</small></a></li> 
          </ul>
        </div>
      </div>
      <div class="asideRight">
        @include('student.partials.header')
        <div class="adminLayout" id="adminLayout" style="padding: 1rem">
          @yield('template')
        </div>
        @include('student.partials.footer')
      </div>
    </div>
  </div>
</body>

</html>
@include('student.partials.sessionMessages')
@include('student.partials.process')
@include('student.partials.script')
