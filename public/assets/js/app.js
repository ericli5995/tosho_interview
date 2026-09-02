/* Public site behaviour. jQuery is available (see requirement); used here for the
   mobile navigation toggle and smooth in-page scrolling. */
(function ($) {
    "use strict";

    $(function () {
        var $toggle = $(".nav-toggle");
        var $nav = $("#site-nav");

        $toggle.on("click", function () {
            var open = $nav.toggleClass("is-open").hasClass("is-open");
            $toggle.attr("aria-expanded", open ? "true" : "false");
        });

        $(document).on("click", 'a[href^="#"]', function (event) {
            var target = $(this.getAttribute("href"));
            if (target.length) {
                event.preventDefault();
                $("html, body").animate({ scrollTop: target.offset().top - 80 }, 250);
            }
        });
    });
})(window.jQuery);
