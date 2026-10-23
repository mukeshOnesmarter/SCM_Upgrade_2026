<i class="fa fa-question-circle fa-sm text-primary" tabindex="0" role="button" data-bs-toggle="popover" data-bs-trigger="focus"
  data-bs-html="true" data-bs-placement="top" 
  title="{{ $title }}"
  data-bs-content='
     <div class="m-0 p-0">
       <div class="m-0 p-0">{{ $slot }}</div>
       <a class="text-primary small m-0 p-0" href="{{ $route }}" target="_blank">See Chapter {{ $chapter }}</a>
     </div>'>
  </i>
