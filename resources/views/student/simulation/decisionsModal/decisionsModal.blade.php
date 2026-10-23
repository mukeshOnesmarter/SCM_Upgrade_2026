 <div class="decisionsModalContainer">
   <div class="decisionsContentContainer">
     <div class="headerContainer">
       <div class="item">
         <h6>{{ $title }} <span>{{ $subTitle }}</span></h6>
         <p>{{ $tagline }}</p>
       </div>
       <div class="item close">
         <a href="#" id="closeDecisionsModal">
           <img src="{{ asset('images/icon/cancel.svg') }}" alt="">
         </a>
       </div>
     </div>
     <div class="contentContainer">
       @stack('modalcontentContainer')
     </div>
     <div class="footerContainer">
       <a href="{{ route('student.manual.index') }}" target="_blank">Read FLEXEE Manual</a>
       <img src="{{ asset('images/icon/document.svg') }}" alt="">
     </div>
   </div>
 </div>
