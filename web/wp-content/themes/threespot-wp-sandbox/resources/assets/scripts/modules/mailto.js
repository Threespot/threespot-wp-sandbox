"use strict";

import MailToLink from "@threespot/mailto";

document.querySelectorAll("[data-email]").forEach(function(el) {
  new MailToLink(el);
});
