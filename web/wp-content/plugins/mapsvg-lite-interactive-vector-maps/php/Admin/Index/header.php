<div class="mapsvg-dashboard" id="mapsvg-admin">
    <div class="row">
        <div class="col-sm-6">
            <h1 style="margin-bottom: 20px;">                
                <span style="text-decoration: none; outline: none; box-shadow: none;">
                    <img src="<?php echo esc_attr(MAPSVG_PLUGIN_RELATIVE_URL); ?>/img/logo.svg" style="height: 25px;" />
                </span>
            </h1>
        </div>
        <div class="col-sm-6" style="text-align: right; line-height: 72px;">
            <div class="float-end" style="padding-left: 20px;">
                <button id="btnCustomizationModal" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#customizationModal"><i class="bi-question-circle"></i> Support</button>
            </div>

            <div class="float-end">
                

                <a href="https://mapsvg.com/docs" target="_blank">Docs</a>

                | <a href="https://mapsvg.com/changelog" target="_blank">Changelog</a> <span class="badge badge-version">v<?php echo esc_html(MAPSVG_VERSION . ($data['gitBranch'] ? ' <i class="bi bi-git"></i> ' . $data['gitBranch'] : '')); ?></span>
                <?php if (MAPSVG_DEBUG) { ?>
                    | <a href="#" id="mapsvg-btn-phpinfo" data-loading-text="Getting info...">PHPInfo</a>
                <?php } ?>

            </div>




        </div>
    </div>