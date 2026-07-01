/**
 * Event Archive Pagination Handler
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Store current page for each tab
    var tabPages = {};

    // Initialize tab pages from URL
    var urlParams = new URLSearchParams(window.location.search);
    var currentPage = urlParams.get("paged") || "1";
    var eventStatus = urlParams.get("event_status");

    // Handle tab switching
    $('.list-tab-event .nav-tabs a[data-toggle="tab"]').on(
      "show.bs.tab",
      function (e) {
        var currentTab = $(e.relatedTarget).attr("href");
        var targetTab = $(e.target).attr("href");
        var $targetPane = $(targetTab);
        var status = $targetPane.data("status");

        // Store current page for the tab we're leaving
        if (currentTab) {
          var currentStatus = $(currentTab).data("status");
          if (currentStatus) {
            tabPages[currentStatus] = getCurrentPageForTab(currentTab);
          }
        }

        // Get stored page for target tab or default to 1
        var targetPage = tabPages[status] || "1";

        // Update URL with current tab status and page
        if (history.pushState && status) {
          var newUrl = updateQueryStringParameter(
            window.location.href,
            "event_status",
            status
          );
          newUrl = updateQueryStringParameter(newUrl, "paged", targetPage);
          window.history.pushState({ path: newUrl }, "", newUrl);
        }
      }
    );

    // Handle pagination clicks
    $(document).on("click", ".pagination-event a", function (e) {
      e.preventDefault();

      var href = $(this).attr("href");
      var page = getPageFromUrl(href);
      var $activeTab = $(".list-tab-event .nav-tabs li.active a");
      var activeTabHref = $activeTab.attr("href");
      var $activePane = $(activeTabHref);
      var status = $activePane.data("status");

      // Validate page number
      if (!page || page < 1) {
        page = "1";
      }

      // Store the page for current tab
      if (status) {
        tabPages[status] = page;
      }

      // Build new URL ensuring event_status is preserved
      var newUrl = window.location.pathname;
      var params = new URLSearchParams();

      if (status) {
        params.set("event_status", status);
      }
      params.set("paged", page);

      newUrl += "?" + params.toString();

      // Navigate to new URL
      window.location.href = newUrl;
    });

    // Helper function to get current page for a tab
    function getCurrentPageForTab(tabSelector) {
      var $tab = $(tabSelector);
      var $pagination = $tab.find(".pagination-event .current");
      if ($pagination.length) {
        return $pagination.text();
      }
      return "1";
    }

    // Helper function to update query string
    function updateQueryStringParameter(uri, key, value) {
      var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
      var separator = uri.indexOf("?") !== -1 ? "&" : "?";

      if (uri.match(re)) {
        return uri.replace(re, "$1" + key + "=" + value + "$2");
      } else {
        return uri + separator + key + "=" + value;
      }
    }

    // Helper function to extract page number from URL
    function getPageFromUrl(url) {
      // Try query parameter first
      var matches = url.match(/[?&]paged=(\d+)/);
      if (matches) {
        return matches[1];
      }

      // Try permalink structure /page/X/
      matches = url.match(/\/page\/(\d+)\//);
      if (matches) {
        return matches[1];
      }

      return "1";
    }

    // Load correct tab on page load based on URL parameter
    if (eventStatus) {
      var $targetTab = $(
        '.list-tab-event .nav-tabs a[href="#tab-' + eventStatus + '"]'
      );
      if ($targetTab.length) {
        // Store the current page for this tab
        tabPages[eventStatus] = currentPage;
        $targetTab.tab("show");
      }
    }

    // Initialize current page for active tab
    var $activeTab = $(".list-tab-event .nav-tabs li.active a");
    if ($activeTab.length) {
      var activeTabHref = $activeTab.attr("href");
      var $activePane = $(activeTabHref);
      var activeStatus = $activePane.data("status");
      if (activeStatus) {
        tabPages[activeStatus] = currentPage;
      }
    }
  });
})(jQuery);
