"use strict";

import ObjectFitImage from "@threespot/object-fit-image";

var imageWrappers = document.querySelectorAll(".bg-image");

imageWrappers.forEach(el => new ObjectFitImage(el, {
  visuallyHiddenClass: "u-screenreader"
}));
