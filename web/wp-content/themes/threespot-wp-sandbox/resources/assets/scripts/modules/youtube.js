//------------------------------------------------------------------------
// YouTube video player tracking and dynamic loading
//
// TODO: Only create the player once it’s scrolled into view
//
// How to track player events in GTM
// https://www.simoahava.com/analytics/the-youtube-video-trigger-in-google-tag-manager/
//
// Requires YouTube Data API key to add proper “title” for accessiblity
// https://developers.google.com/youtube/v3/getting-started
//
// Video players can be built dynamically using data attributes:
// <div data-youtube="FuYW3GdPhsc">
//   <a href="https://www.youtube.com/watch?v=FuYW3GdPhsc">Watch video on YouTube</a>
// </div>
//------------------------------------------------------------------------
/*global YT*/
"use strict";
import loadJS from "fg-loadjs";
const publicKey = window.APP && window.APP.YOUTUBE_PUBLIC_KEY;

class YoutubePlayer {
  constructor(config) {
    if (!publicKey) {
      console.warn("No YouTube API public key provided, can’t get video title");
      return false;
    }

    this.el = config.el;

    // Generate unique ID for each video
    // https://gist.github.com/gordonbrander/2230317
    this.uniqueID = "YT_" + Math.random().toString(36).substr(2, 4);

    // Check if iframe embed
    if (this.el.nodeName.toLowerCase() === "iframe") {
      var iframeSrc = this.el.getAttribute("src");

      // Add enablejsapi=1 to src to enable GTM tracking
      if (
        iframeSrc.indexOf("enablejsapi") === -1 ||
        iframeSrc.indexOf("wmode") === -1
      ) {
        var separator = iframeSrc.indexOf("?") > 0 ? "&" : "?";
        iframeSrc += separator + "enablejsapi=1&wmode=opaque";
        this.el.setAttribute("src", iframeSrc);
      }
    } else {
      // Get playerVars from optional data attributes if present
      this.collectVars();

      // Get YouTube video ID
      this.videoID = this.el.getAttribute("data-youtube");

      if (!this.videoID) {
        console.warn("Missing YouTube ID");
        return false;
      }

      // Replace fallback content with placeholder element that iframe will replace
      this.el.innerHTML = `<div id=${this.uniqueID}>`;

      // Check for video title
      this.videoTitle = this.el.getAttribute("data-youtube-title") || "";

      // Lookup title if not provided
      if (!this.videoTitle.length) {
        this.videoTitle = this.getVideoTitle();
      }
    }

    // Insert the video player
    this.buildPlayer();
  }

  getVideoTitle() {
    this.getVideoData(function(data) {
      if ("items" in data && data.items.length) {
        var video = data.items[0];
        return video.snippet.title.trim();
        // self.description = video.snippet.description || '';
      } else {
        console.warn("Can’t get YouTube data for video ID" + self.videoID);
      }
    });
  }

  // Plain JS AJAX request
  // https://plainjs.com/javascript/ajax/making-cors-ajax-get-requests-54/
  getCORS(url, success, error) {
      var xhr = new XMLHttpRequest();
      if (!('withCredentials' in xhr)) xhr = new XDomainRequest(); // fix IE8/9
      xhr.open('GET', url);
      xhr.onload = success;
      xhr.onerror = error;
      xhr.send();
      return xhr;
  }

  // Get video metadata using YouTube gdata API
  // https://www.googleapis.com/youtube/v3/videos?id=jZ4cur1kgog
  getVideoData(callback) {
    var self = this;
    var url = `https://www.googleapis.com/youtube/v3/videos?id=${self.videoID}&key=${publicKey}&part=snippet,contentDetails`;

    this.getCORS(url, function(request) {
      var response = request.currentTarget.response || request.target.responseText;
      callback(JSON.parse(response));
    }, function() {
      self.videoTitle = "Title not found";
    });
  }

  // Collect player vars from data attributes (if present)
  collectVars() {
    this.playerVars = {};
    this.playerVars.autohide = this.el.getAttribute("data-youtube-autohide") || 1;
    this.playerVars.autoplay = this.el.getAttribute("data-youtube-autoplay") || 0;
    // controls=2 does not work when enablejsapi=1
    this.playerVars.controls = this.el.getAttribute("data-youtube-controls") || 1;
    this.playerVars.iv_load_policy = this.el.getAttribute("data-youtube-iv_load_policy") || 3;
    this.playerVars.showinfo = this.el.getAttribute("data-youtube-showinfo") || 0;
    this.playerVars.rel = this.el.getAttribute("data-youtube-rel") || 0;
    this.playerVars.wmode = "opaque";
    // console.log('this.playerVars', this.playerVars);
  }

  buildPlayer() {
    var self = this;
    // Replace the placeholder element with the iframe
    this.player = new YT.Player(this.uniqueID, {
      videoId: self.videoID,
      playerVars: self.playerVars,
      events: {
        onReady: function() {
          // console.log('YT ready', self.uniqueID);
        }
      }
    });
  }
}

// Find videos
const videos = document.querySelectorAll("[data-youtube], iframe[src*='youtube.com'], iframe[src*='youtube-nocookie.com']");

// Load JS API and create video players if present
if (publicKey && videos.length) {
  // Build video players
  var buildPlayers = function() {
    videos.forEach(function(item) {
      new YoutubePlayer({ el: item });
    });
  };

  // Check if YouTube script has already run
  // https://developers.google.com/youtube/iframe_api_reference
  if (typeof window.onYouTubeIframeAPIReady == "undefined") {
    // Set custom “onYouTubeIframeAPIReady” callback that will fire once the script has downloaded
    window.onYouTubeIframeAPIReady = function() {
      buildPlayers();
    };

    // Get YouTube JS API script
    loadJS("https://www.youtube.com/iframe_api", function() {
      // YouTube script automatically calls onYouTubeIframeAPIReady()
    });
  } else {
    // If YouTube script has previously run, we can build players immediately
    buildPlayers();
  }
}
