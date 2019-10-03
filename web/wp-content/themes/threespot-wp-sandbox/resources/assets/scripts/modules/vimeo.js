// Vimeo video players
// https://github.com/vimeo/player.js
//
// This module will dynamically load the JS API, build the players, and attach event listeners for GTM.
//
// <div data-vimeo="65171201"><a href="https://vimeo.com/141050050">Watch video on Vimeo</a></div>
//------------------------------------------------------------------------
"use strict";
import once from "lodash/once";
import loadJS from "fg-loadjs";

class VimeoPlayer {
  constructor(config) {
    var self = this;
    this.el = config.el;
    this.id = this.el.getAttribute("data-vimeo");

    // Create video player
    // Docs https://github.com/vimeo/player.js#embed-options
    this.player = new window.Vimeo.Player(this.el, {
      byline: false,
      color: "009cde",
      id: this.id,
      portrait: false,
      title: false
    });

    // Get the video title
    this.player
      .getVideoTitle()
      .then(function(title) {
        self.videoTitle = title;
        // Replace generic title attribute value with video title for better accessiblity.
        // Add a period so screen readers pause, making it easier to understand.
        self.el.setAttribute('title', "Video player: " + title + ".");
      })
      .catch(function(error) {
        console.warn(error);
        self.videoTitle = "Title not found";
      });

    // Play event listener
    this.player.on("play", function() {
      self.gtmVideoAction("play");
    });

    // Finished event listener
    this.player.on("ended", function() {
      self.gtmVideoAction("finished");
    });

    // These functions should only run once
    // http://underscorejs.org/#once
    this.track10sec = once(this.track10sec);
    this.track50percent = once(this.track50percent);

    // Track progress (fires every ~250ms)
    this.player.on("timeupdate", function(data) {
      // console.log(data.seconds + 's - ' + (data.percent * 100) + '% played');

      // 10s elapsed
      if (data.seconds >= 10) {
        self.track10sec();
      }

      // 50% watched
      if (data.percent >= 0.5) {
        self.track50percent();
      }
    });
  }

  track10sec() {
    this.gtmVideoAction("watched 10s");
  }

  track50percent() {
    this.gtmVideoAction("watched 50%");
  }

  // Helper to push data to GTM dataLayer
  gtmVideoAction(action) {
    // console.log({
    //   'event': 'customVideoEvent',
    //   'videoTitle': this.videoTitle,
    //   'videoAction': action
    // });

    // Use try/catch just in case GTM hasn’t loaded yet
    try {
      window.dataLayer.push({
        event: "customVideoEvent",
        videoAction: action,
        videoTitle: this.videoTitle
      });
    } catch (e) {
      console.warn("GTM: Unable to push Vimeo data to dataLayer");
    }
  }
}

// Find videos
var videos = document.querySelectorAll('[data-vimeo], iframe[src*="vimeo.com"]');

// Load JS API and create video players if present
if (videos.length) {
  // Build video players
  var buildPlayers = function() {
    videos.forEach(function(item) {
      var video = new VimeoPlayer({ el: item });
    });
  };

  // Load JS API if not already loaded
  // https://github.com/vimeo/player.js
  if (typeof window.Vimeo == "undefined") {
    // Get the script then build the players
    loadJS("https://player.vimeo.com/api/player.js", function() {
      buildPlayers();
    });
  } else {
    buildPlayers();
  }
}
