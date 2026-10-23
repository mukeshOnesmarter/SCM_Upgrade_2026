<script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
<script src="{{ asset('js/jquery-ui.js') }}"></script>
<script src="{{ asset('js/nprogress.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script> 
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(el => new bootstrap.Popover(el));
  });
</script> 
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

<script>
  $(document).ready(function() {

    // Auto adjust min-height for page layout
    const headerHeight = $("div.header").outerHeight() || 0;
    const footerHeight = $("div.fotter").outerHeight() || 0;
    const windowHeight = $(window).height();
    const pageBodyHeight = windowHeight - headerHeight - footerHeight;
    $("#adminLayout").css('min-height', pageBodyHeight + 'px');

    // Disable copy/paste for email fields
    $('input[type="email"]').on("cut copy paste", function(e) {
      e.preventDefault();
    });

    // Datepicker setup
    $("input.datepicker").datepicker({
      dateFormat: 'mm-dd-yy',
      minDate: 0
    });

    // Auto fade alerts
    ['#errorMessage', '#successMessage', '#screenAlert'].forEach(function(selector) {
      if ($(selector).length) {
        setTimeout(() => $(selector).fadeOut(), 3000);
      }
    });

    // Open and Close Modals
    $("#openDecisionsModal").on('click', function(e) {
      e.preventDefault();
      $("#decisionsModal").fadeIn();
    });

    $("#closeDecisionsModal").on('click', function(e) {
      e.preventDefault();
      $("#decisionsModal").fadeOut();
    });
  });
</script>
@yield('templateScript')
