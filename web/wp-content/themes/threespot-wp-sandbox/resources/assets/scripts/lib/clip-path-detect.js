// Modernizr Tests for clip-path support by @shshaw
// http://codepen.io/shshaw/details/yyOaqW/
(function(){

  // Here are all the values we will test. If you want to use just one or two, comment out the lines of test you don't need.
  var tests = [
    { name: 'svg', value: 'url(#test)' }, // False positive in IE, supports SVG clip-path, but not on HTML element
    { name: 'inset', value: 'inset(10px 20px 30px 40px)' },
    { name: 'circle', value: 'circle(60px at center)' },
    { name: 'ellipse', value: 'ellipse(50% 50% at 50% 50%)' },
    { name: 'polygon', value: 'polygon(50% 0%, 0% 100%, 100% 100%)' }
  ];

  var t = 0,
      name, value, prop;

  for (; t < tests.length; t++) {
    name = tests[t].name;
    value = tests[t].value;
    Modernizr.addTest('cssclippath' + name, function(){
      // Try using window.CSS.supports
      if ( 'CSS' in window && 'supports' in window.CSS ) {
        for (var i = 0; i < Modernizr._prefixes.length; i++) {
          prop = Modernizr._prefixes[i] + 'clip-path'

          if ( window.CSS.supports(prop,value) ) { return true; }
        }
        return false;
      }
      // Otherwise, use Modernizr.testStyles and examine the property manually
      return Modernizr.testStyles('#modernizr { '+Modernizr._prefixes.join('clip-path:'+value+'; ')+' }',function(elem, rule) {
        var style = getComputedStyle(elem),
            clip = style.clipPath;

        if ( !clip || clip == "none" ) {
          clip = false;

          for (var i = 0; i < Modernizr._domPrefixes.length; i++) {
            test = Modernizr._domPrefixes[i] + 'ClipPath';
            if ( style[test] && style[test] !== "none" ) {
              clip = true;
              break;
            }
          }
        }

        return Modernizr.testProp('clipPath') && clip;
      });
    });

  }

})(Modernizr);
