<script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
<script src="{{ asset('js/jquery-ui.js') }}"></script>
<script src="{{ asset('js/nprogress.min.js') }}"></script> 
<script>
  document.addEventListener('DOMContentLoaded', function() {

    // Show NProgress on link clicks, skip new tabs and anchors
    document.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function(e) {
        const href = link.getAttribute('href');
        if (link.target === '_blank' || !href || href.startsWith('#') || href.startsWith('javascript')) {
          return;
        }
        NProgress.start();
      });
    });

    // Show NProgress & process overlay on form submissions
    document.querySelectorAll('form').forEach(function(form) {
      form.addEventListener('submit', function() {
        if (document.getElementById('process')) {
          $('#process').fadeIn();
        }
        NProgress.start();
      });
    });

    // Hide NProgress and page loader on full page load
    window.addEventListener('load', function() {
      NProgress.done();
      if (document.getElementById('pageLoader')) {
        $('#pageLoader').fadeOut('fast');
      }
    });
  });
</script> 
<script type="text/javascript" defer>
  $(document).ready(function() {
    //SET MIN HEIGHT FOR PAGE BODY
    var headerHeight = $("div.header").outerHeight();
    var fotterHeight = $("div.fotter").outerHeight();
    var windowHeight = $(window).height();
    var pagebodyHeight = windowHeight - headerHeight - fotterHeight;
    $("#adminLayout").css('min-height', pagebodyHeight + 'px');

    // Auto fade alerts
    ['#errorMessage', '#successMessage', '#screenAlert'].forEach(function(selector) {
      if ($(selector).length) {
        setTimeout(() => $(selector).fadeOut(), 3000);
      }
    }); 

    // DATEPICKER
    $("input.datepicker").datepicker({
      dateFormat: 'mm-dd-yy',
      minDate: 0
    });  

  });
</script>
@yield('templateScript')
