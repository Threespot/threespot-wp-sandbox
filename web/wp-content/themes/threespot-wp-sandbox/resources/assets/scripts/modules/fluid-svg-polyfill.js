import fluidSVGPolyfill from "@threespot/fluid-svg-polyfill";

// Find SVGs with “is-fluid” class
const svgs = document.querySelectorAll('.is-fluid');

svgs.forEach( el => fluidSVGPolyfill(el) );
