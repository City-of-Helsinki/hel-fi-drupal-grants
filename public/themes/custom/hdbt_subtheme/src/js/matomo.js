// eslint-disable-next-line func-names
(function ($, Drupal) {
  function loadMatomoAnalytics() {
    if (typeof Drupal.eu_cookie_compliance === "undefined") {
      return;
    }

    // Load Matomo only if statistics cookies are allowed.
    if (Drupal.eu_cookie_compliance.hasAgreed("statistics")) {
      // Matomo Tag Manager
      // eslint-disable-next-line no-multi-assign
      const _paq = (window._paq = window._paq || []);
      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
      _paq.push(["trackPageView"]);
      _paq.push(["enableLinkTracking"]);
      (function () {
        const u = "//webanalytics.digiaiiris.com/js/";
        _paq.push(["setTrackerUrl", `${u}tracker.php`]);
        _paq.push(["setSiteId", "1219"]);
        const d = document;
        const g = d.createElement("script");
        const s = d.getElementsByTagName("script")[0];
        g.async = true;
        g.src = `${u}piwik.min.js`;
        s.parentNode.insertBefore(g, s);
      })();
    }
  }

  // Load when cookie settings are changed.
  $(document).on("eu_cookie_compliance.changeStatus", loadMatomoAnalytics());

  // Load on page load.
  $(document).ready(loadMatomoAnalytics);
})(jQuery, Drupal);
