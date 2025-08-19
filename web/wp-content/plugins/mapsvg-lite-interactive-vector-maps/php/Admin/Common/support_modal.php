<!-- MODAL -->
<div
  class="modal fade"
  id="customizationModal"
  tabindex="-1"
  role="dialog"
  aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel">Support</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-3" id="supportTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="chat-tab" data-bs-toggle="tab" data-bs-target="#chat" type="button" role="tab" aria-controls="chat" aria-selected="true">Chat</button>
          </li>
          <li class="nav-item" role="presentation" id="access-tokens-tab-item">
            <button class="nav-link" id="access-tokens-tab" data-bs-toggle="tab" data-bs-target="#access-tokens" type="button" role="tab" aria-controls="access-tokens" aria-selected="false">Access tokens</button>
          </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content" id="supportTabsContent">
          <div class="tab-pane fade show active" id="chat" role="tabpanel" aria-labelledby="chat-tab">
            <div id="mapsvg-enable-chat-section">
              <div class="alert alert-info mt-4 mapsvg-premium-feature"><i class="bi bi-star-fill"></i> Premium feature: Log in to save conversation history, access chats on <a href="https://mapsvg.com/dashboard" target="_blank">mapsvg.com/dashboard</a>, and get faster response times. <a class="btn btn-xs mx-1 btn-outline-primary" target="_blank" href="https://mapsvg.com">Upgrade</a></div>
              <?php if (strpos(home_url(), 'demo.mapsvg.com') !== false) : ?>
                <div class="alert alert-info mt-4" role="alert">
                  This feature is not available on the demo site!
                </div>
              <?php endif; ?>

              
            </div>

            <div id="mapsvg-enabled-chat-section" style="display: none;">
              <div class="alert alert-info" role="alert" style="margin-top: 15px;">
                Chat is enabled. You can now chat with support.
              </div>
            </div>
            <form class="form" id="mapsvg-chat-consent-form">
              <h5>Support Chat Consent</h5>
              <p>
                You can use chat in the free version of mapsvg to ask any questions, but you can only use "guest" session, without logging in. 
                Also, our response times may be slower. We prioritize addressing questions from customers who have purchased MapSVG-Pro, but we'll get back to you as soon as we have no pending inquiries from them.
              </p>
              <p>
                By enabling the support chat, you agree to allow us to collect diagnostic information to assist with resolving your issue. This may include logs, network requests, and other data related to your usage of the plugin.
              </p>
              <div class="form-group mb-3">
                <div class="form-check pt-2">
                  <input type="checkbox" class="form-check-input" name="chatConsentAccepted" checked="" id="chatConsentAccepted">
                  <label for="chatConsentAccepted" class="form-check-label" style="transform: translateY(-2px);">I agree to share diagnostic information, including logs and network requests, for support purposes.</label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" id="enableChatButton" data-loading-text="Enabling...">Enable chat</button>
            </form>
          </div>
          <div class="tab-pane fade" id="access-tokens" role="tabpanel" aria-labelledby="access-tokens-tab">
            <div class="alert alert-info mt-4 mapsvg-premium-feature"><i class="bi bi-star-fill"></i> Premium feature <a class="btn btn-xs mx-1 btn-outline-primary" target="_blank" href="https://mapsvg.com">Upgrade</a></div>
            <div id="mapsvg-support-access-section">
              <?php if (strpos(home_url(), 'demo.mapsvg.com') !== false) : ?>
                <div class="alert alert-info mt-4" role="alert">
                  This feature is not available on the demo site!
                </div>
              <?php endif; ?>
              <h5 style="margin-top: 30px;">
                Create an access token for the support team
              </h5>
              <p>
                Please grant us some level of access to your WordPress when submitting a bug report for quick issue resolution (the more access you grant, the faster the issue will be resolved, but at least "Access to logs" is required).
              </p>

              <form class="form" id="mapsvg-create-token-form">
                <div class="form-group mb-3 row">
                  <label class="col-md-3 form-label col-form-label">Access to logs</label>
                  <div class="col-md-9">
                    <div
                      class="form-switch form-switch-md btn-group"> <input type="checkbox" name="accessLogs" class="form-check-input"> </div>
                    <p class="form-text">
                      This will grant access to WordPress logs, database queries, and other diagnostic information.
                    </p>
                  </div>
                </div>
                <div class="form-group mb-3 row">
                  <label class="col-md-3 form-label col-form-label">Access to WP-admin</label>
                  <div class="col-md-9">
                    <div
                      class="form-switch form-switch-md btn-group"> <input type="checkbox" name="accessWp"
                        class="form-check-input"> </div>
                    <p class="form-text">
                      If the previous access level was not enough, we may ask you to provide the full access to WP-admin.
                      This will create a new admin user with username "mapsvg". When you delete all access tokens with "wp" access level, the user "mapsvg" will be deleted too.
                    </p>
                  </div>
                </div>



                <button type="submit" class="btn btn-primary" disabled="disabled">Create</button>



              </form>
            </div>
            <div id="mapsvg-new-token-message" style="display: none; margin-top: 30px;">
              <label for="magic-link" class="form-label">Magic link</label>
              <input id="magic-link" type="text" class="form-control" readonly />
              <div class="alert alert-info" role="alert" style="margin-top: 15px;">
                The magic link has been copied to the clipboard. Make sure to save it as it will be shown just once.
              </div>
            </div>
            <h5 style="margin-top: 30px;">
              Access tokens
            </h5>
            <div id="mapsvg-tokens"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<div id="gleap-unlogged">
  <div class="mapsvg-bb-feedback-button mapsvg-gleap-font gl-block" dir="ltr">
    <div class="mapsvg-bb-feedback-button-icon">
      <svg class="mapsvg-bb-logo-logo mapsvg-bb-logo-logo--default" width="145" height="144" viewBox="0 0 145 144" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M38.9534 15H105.047C113.857 15 121 22.1426 121 30.9534L121 89.5238L121 96.015L121 125.541C121 128.759 117.393 130.66 114.739 128.84L90.1188 111.968H38.9534C30.1426 111.968 23 104.826 23 96.015V30.9534C23 22.1426 30.1426 15 38.9534 15Z" fill="white"></path>
      </svg><svg class="mapsvg-bb-logo-arrowdown" fill="#fff" width="100pt" height="100pt" version="1.1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <path d="m50 77.637c-1.3477 0-2.6953-0.51562-3.7266-1.543l-44.73-44.73c-2.0586-2.0586-2.0586-5.3945 0-7.4531 2.0586-2.0586 5.3945-2.0586 7.4531 0l41.004 41 41.004-41c2.0586-2.0586 5.3945-2.0586 7.4531 0 2.0586 2.0586 2.0586 5.3945 0 7.4531l-44.73 44.727c-1.0312 1.0312-2.3789 1.5469-3.7266 1.5469z"></path>
      </svg>
    </div>
  </div>
</div>