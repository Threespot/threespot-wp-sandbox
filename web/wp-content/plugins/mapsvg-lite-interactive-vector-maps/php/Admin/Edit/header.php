<div class="mapsvg-dashboard fixed" id="mapsvg-admin">

    <div id="mapsvg-nav-header">
        <h2>
            <a href="<?php echo esc_html(admin_url('admin.php?page=mapsvg-config')) ?>" style="text-decoration: none; outline: none; box-shadow: none;">
                <img src="<?php echo esc_html((MAPSVG_PLUGIN_RELATIVE_URL)); ?>/img/logo-icon.png" style="height: 20px; margin-right: 5px; transform: translateY(-1px);" />
            </a>
            <div style="transform: translateY(1px); display: inline-block;">
                <span id="map-page-title"></span>
                <small id="mapsvg-shortcode"
                    style="float: none; width: auto; display: inline-block; transform: translateY(-2px);">
                    <span class="map-page-shortcode"></span>
                    <button data-shortcode=''
                        class="mapsvg-copy-shortcode btn btn-xs btn-link toggle-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="Copy to clipboard"
                        style="transform: translate(-3px,-1px);">
                        <i class="bi bi-copy"></i></button>
                </small>
            </div>
        </h2>


        <div class="float-right" id="mapsvg-top-buttons">
            <button id="mapsvg-save" class="btn btn-sm btn-primary" data-loading-text="Saving...">Save
                <span class="mapsvg-hotkey-mac">⌘ S</span><span class="mapsvg-hotkey-others">Ctrl+S</span>
            </button>
            <button id="mapsvg-save-svg" class="btn btn-sm btn-primary" style="display: none;"
                data-loading-text="Saving...">Save SVG file <span class="mapsvg-hotkey-mac">⌘ S</span>
                <span
                    class="mapsvg-hotkey-others">Ctrl+S</span>
            </button>
            <div class="btn-group btn-group-toggle  " data-toggle="buttons" id="mapsvg-map-mode-2">
                <input id="mapSettingsOption" class="btn-check" type="radio" name="mapsvg_map_mode_2" value="preview" autocomplete="off" checked>
                <label for="mapSettingsOption" class="btn btn-outline-secondary btn-sm" data-mode="preview">
                    Map settings</label>

                <input id="editSvgOption" class="btn-check" type="radio" name="mapsvg_map_mode_2" value="draw" autocomplete="off">
                <label for="editSvgOption" class="btn btn-outline-secondary btn-sm" data-mode="draw">
                    Edit SVG file</label>
            </div>
            <div class="btn-group btn-group-toggle" data-toggle="buttons" id="mapsvg-view-buttons">

                <input id="icon1Option" class="btn-check" type="checkbox" class="form-check-input hidden" autocomplete="off" checked
                    name="left">
                <label for="icon1Option" class="btn btn-sm btn-outline-secondary active toggle-tooltip" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Toggle left panel" id="mapsvg-panels-view-left">
                    <i class="bi bi-layout-sidebar-inset"></i>

                </label>

                <input id="icon2Option" class="btn-check" type="checkbox" autocomplete="off"
                    checked name="right">
                <label for="icon2Option" class="btn btn-sm btn-outline-secondary active toggle-tooltip" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Toggle right panel" id="mapsvg-panels-view-right">
                    <i class="bi bi-layout-sidebar-inset-reverse"></i>

                </label>
            </div>


            <div class="toggle-tooltip" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Support">
                <button
                    id="btnCustomizationModal"
                    class="btn btn-sm btn-outline-secondary"
                    data-toggle="modal"
                    data-target="#customizationModal">
                    <i class="bi-question-circle"></i> Support</button>
            </div>
        </div>
    </div>