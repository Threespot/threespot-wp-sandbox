<style>
    #growls.default {
        top: 32px;
        right: -2px;
    }
</style>

<!-- Modal What's new-->
<div class="modal fade" id="whatsNewModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">What's new in v<?php echo esc_html(MAPSVG_VERSION) ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="mapsvg-changelog">
                <?php echo (isset($data['whatsNew']) ? esc_html($data['whatsNew']) : ''); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if (strpos(home_url(), 'demo.mapsvg.com') === false) : ?>
    <div class="alert alert-info alert-dismissible" role="alert"
        id="mapsvg-alert-activate" <?php echo (isset($data['options']['purchase_code']) && $data['options']['purchase_code'] ? "style='display:none'" : ""); ?>>
        <button type="button" class="btn-close close" aria-label="Close"></button>
        <p style="margin-bottom: 10px; font-size: 14px;">Enter your purchase code from CodeCanyon to enable
            automatic updates.</p>
        <form class="form" id="mapsvg-purchase-code-form">

            <div class="row justify-content-start">
                <div class="col-5">
                    <input type="text" class="form-control" name="purchase_code" autocomplete="off"
                        value="<?php echo (isset($data['options']['purchase_code']) ? esc_html($data['options']['purchase_code']) : ''); ?>" />
                </div>
                <div class="col-1">

                    <button class="btn btn-primary" id="mapsvg-btn-activate"
                        data-loading-text="Checking...">
                        Activate
                    </button>

                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (isset($data['mapsvg_error'])) { ?>
    <div class="alert alert-danger">
        <?php echo esc_html($data['mapsvg_error']); ?>
    </div>
<?php } ?>
<?php if (isset($data['mapsvg_notice'])) { ?>
    <div class="alert alert-info">
        <?php echo esc_html($data['mapsvg_error']); ?>
    </div>
<?php } ?>

<div class="">
    <div style="margin-bottom: 15px;">

    </div>

    <div style="margin-bottom: 15px;">

    </div>
    <div style="margin-bottom: 15px;">
    </div>


</div>


<div id="mapsvg-google-map-fullscreen-wrap" style="display: none;">
    <div id="mapsvg-google-map-fullscreen"></div>
    <div id="mapsvg-google-map-fullscreen-controls" class="card card-body bg-light">
        <div style="margin-bottom: 10px;">
            <input type="text" class="form-control typeahead" onclick="this.focus();this.select()"
                id="mapsvg-gm-address-search" placeholder="Enter address..." />
            <!--            <span class="input-group-btn">-->
            <!--              <button class="btn btn-default" type="button"><i class="bi bi-search"></i></button>-->
            <!--            </span>-->
        </div><!-- /input-group -->
        <div class="input-group">
            <a class="btn btn-outline-secondary" style="margin-right:10px" id="mapsvg-gm-download">Download SVG</a>
            <button class="btn btn-outline-secondary" id="mapsvg-gm-close">Close</button>
        </div>
    </div>
</div>


<!--<div class="row" style="margin-bottom: 20px;">-->
<!--    <div class="col-sm-3" style="text-align: center;">-->
<div class="bg-light  mb-3" id="mapsvg-admin-row" style="overflow: hidden; padding-bottom: 9px;">
    <div class="mb">
        <form method="POST" style="margin: 0 auto;"><a href="" id="hidden-link" style="display: none;"></a>
            <select class="form-control select-map-list span2"
                id="mapsvg-svg-file-select" style="width: 250px;">
                <option value="">New SVG map</option>
                <?php if (isset($data['svgFiles'])) foreach ($data['svgFiles'] as $file) { ?>
                    <option data-relative-url="<?php echo esc_attr($file->relativeUrl) ?>">
                        <?php echo esc_html($file->pathShort) ?></option>
                <?php } ?>
            </select>
        </form>
    </div>
    <div class="mb">
        <button id="new-google-map" class="btn btn-outline-secondary">New Google Map</button>
    </div>
    <div class="mb">
        <form style="display: block; margin: 0 auto;" enctype="multipart/form-data"
            method="POST" id="image_file_uploader_form">
            <div class="btn btn-outline-secondary btn-file" data-loading-text="Uploading...">New Image Map
                <input type="file" name="svg_file" id="image_file_uploader">
            </div>
            <input type="hidden" name="upload_image">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('upload_image')) ?>">
        </form>
    </div>
    <div class="mb">
        <form style="display: block; margin: 0 auto;" enctype="multipart/form-data"
            method="POST" id="svg_file_uploader_form">
            <div class="btn btn-outline-secondary btn-file" data-loading-text="Uploading...">Upload SVG
                <input type="file" name="svg_file" id="svg_file_uploader" accept="image/*">
            </div>
            <input type="hidden" name="upload_svg">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('upload_map')) ?>">
        </form>
    </div>
    <div class="mb">
        <form style="display: block; margin: 0 auto;" enctype="multipart/form-data"
            method="POST">
            <div class="btn btn-outline-secondary btn-file" id="download_gmap">Download SVG with Google map</div>
        </form>
    </div>

    <div class="mb">
        <div class="btn btn-outline-secondary btn-file" id="mapsvg-btn-settings-modal" data-toggle="modal" data-target="#settingsModal">
            <i class="bi bi-gear"></i> Settings
        </div>
        <!-- Modal -->
        <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">Settings</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    </div>
                </div>
            </div>
        </div>
    </div>



</div>

<style>
    .mb {
        float: left;
        margin-right: 10px;
        margin-bottom: 10px;
    }
</style>


<?php if (isset($data['maps']) && count($data['maps']) > 0) { ?>
    <!-- MENU -->
    <table class="table table-striped" id="mapsvg-table-maps">
        <thead>
            <th style="width: 30%">Map title</th>
            <th style="width: 20%">Shortcode</th>
            <th>Actions</th>
        </thead>
        <tbody id="mapsvg-table">
            <?php foreach ($data['maps'] as $m) { ?>

                <?php $needsUpgrade = version_compare($m->version, '3.0.0', '<'); ?>

                <tr data-id="<?php echo esc_attr($m->id) ?>" data-title="<?php echo esc_attr($m->title) ?>">
                    <td class="mapsvg-map-title">
                        <?php if ($needsUpgrade) { ?>
                            <div><?php echo esc_html($m->title) ?></div>
                        <?php } else { ?>
                            <a href="?page=mapsvg-config&map_id=<?php echo esc_attr($m->id) ?>"><?php echo esc_html($m->title) ?></a>
                        <?php } ?>

                    </td>

                    <td class="mapsvg-shortcode">[mapsvg id="<?php echo esc_html($m->id) ?>"]
                        <button data-shortcode='[mapsvg id="<?php echo esc_attr($m->id) ?>"]' class="toggle-tooltip mapsvg-copy-shortcode btn btn-xs btn-outline-secondary" title="Copy to clipboard"><i class="bi bi-copy"></i></button>
                    </td>

                    <td class="mapsvg-action-buttons">
                        <a href="#" class="btn btn-sm btn-outline-secondary mapsvg-copy" data-nonce="<?php echo esc_attr(wp_create_nonce('ajax_mapsvg_copy-' . $m->id)) ?>">Duplicate</a>
                        <a href="#" class="btn btn-sm btn-outline-secondary mapsvg-delete" data-loading-text="Deleting..." data-nonce="<?php echo esc_attr(wp_create_nonce('ajax_mapsvg_delete-' . $m->id)) ?>">Delete</a>
                        <span class="hidden text-danger mapsvg-span-deleted">Deleted</span>
                        <a href="#" class="btn btn-sm btn-outline-secondary mapsvg-undo hidden" data-loading-text="Restoring..." data-nonce="<?php echo esc_attr(wp_create_nonce('ajax_mapsvg_undo-' . $m->id)) ?>">Undo</a>
                        <?php if ($needsUpgrade) { ?>
                            <a href="#" class="btn btn-sm btn-danger mapsvg-upgrade-v2" data-nonce="<?php echo esc_attr(wp_create_nonce('ajax_mapsvg_upgrade-' . $m->id)) ?>" data-loading-text="Upgrading...">Upgrade</a>
                            <div class="alert alert-info" style="margin-top: 15px;">The map version (v2.4.1) is incompatible

                                with the current version of MapSVG.

                                The map still works on the front-end but you can't edit it.

                                Please click "Upgrade" to create an upgraded copy of the map.

                                Some settings can't be converted automatically.

                                You will need to setup templates and directory manually, and update your JS event handlers
                                code.
                            </div>

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>



            <?php ?>
        </tbody>
    </table>
    <?php if ($data["pagination"]["page"] === 1 && !$data["pagination"]["hasMore"]) { ?>
    <?php } else { ?>
        <nav aria-label="...">
            <ul class="pagination pagination-sm">
                <li class="page-item <?php if ($data["pagination"]["page"] === 1) echo "disabled"; ?>">
                    <a class="page-link" href="<?php echo esc_attr($data["pagination"]["prevLink"]) ?>">Previous</a>
                </li>
                <li class="page-item">
                    <a class="page-link disabled" href="#"><?php echo esc_html($data["pagination"]["page"]); ?></a>
                </li>
                <li class="page-item">
                    <a class="page-link <?php if (!$data["pagination"]["hasMore"]) echo "disabled" ?>" href="<?php echo esc_attr($data["pagination"]["nextLink"]) ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php } ?>
<?php } ?>