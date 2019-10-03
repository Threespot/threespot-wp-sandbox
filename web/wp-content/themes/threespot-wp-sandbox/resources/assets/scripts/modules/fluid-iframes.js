//------------------------------------------------------------------------
// Make iframes responsive, preserving their aspect ratio (like FitVid.js)
//------------------------------------------------------------------------
"use strict";

import FluidIframe from "@threespot/fluid-iframe";

const iframes = document.querySelectorAll(".u-richtext > iframe");

iframes.forEach(el => new FluidIframe(el));
