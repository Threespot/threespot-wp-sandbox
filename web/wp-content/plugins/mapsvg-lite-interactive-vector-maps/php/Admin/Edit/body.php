<!-- FORM -->
<!--<table id="mapsvg-table-maps" class="table table-striped" style="height:
325px;" width="824">-->
<!--<table id="mapsvg-table-maps" class="table table-striped" width="100%">-->
<div id="mapsvg-panels" class="stretch">
  <div class="mapsvg-panel mapsvg-panel-left stretch" id="mapsvg-container">
    <div class="alert alert-warning alert-dismissible" id="mapsvg-auto-id-warning" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&#xD7;</span>
      </button>
      <strong>Warning!</strong> Some objects in SVG file are missing their IDs. MapSVG generated IDs
      but please note that if you will edit SVG file later and add/remove some objects, it would
      change objects order and ID references would be lost.
    </div>
    <div id="mapsvg-preview-buttons">
      <div
        class="btn-group-xs btn-group-toggle"
        data-toggle="buttons"
        id="mapsvg-preview-containers-toggle"
      >
        <input
          id="containerOption"
          class="btn-check"
          type="checkbox"
          name="mapsvg-preview-containers-toggle"
          value="1"
          checked
          autocomplete="off"
        />
        <label
          for="containerOption"
          class="btn btn-outline-secondary btn-xs"          
        >
          Containers</label
        >
      </div>
      <div
        class="btn-group-xs btn-group btn-group-toggle"
        data-toggle="buttons"
        id="mapsvg-map-mode"
      >
        <input
          id="previewOption"
          type="radio"
          class="btn-check"
          name="mapsvg_map_mode"
          value="preview"
          autocomplete="off"
          checked
        />
        <label
          for="previewOption"
          class="btn btn-outline-secondary btn-xs active"
          data-mode="preview"
        >
          Preview</label
        >

        <input
          id="addRegOption"
          type="radio"
          class="btn-check"
          name="mapsvg_map_mode"
          value="addRegions"
          autocomplete="off"
        />
        <label
          for="addRegOption"
          class="btn btn-outline-secondary btn-xs"
          data-mode="addRegions"
          style="display: none"
        >
          Add regions</label
        >

        <input
          id="editRegOption"
          type="radio"
          class="btn-check"
          name="mapsvg_map_mode"
          value="editRegions"
          autocomplete="off"
        />
        <label for="editRegOption" class="btn btn-outline-secondary btn-xs" data-mode="editRegions">
          Edit regions</label
        >

        <input
          id="editDbOption"
          type="radio"
          class="btn-check"
          name="mapsvg_map_mode"
          value="editData"
          autocomplete="off"
        />
        <label for="editDbOption" class="btn btn-outline-secondary btn-xs" data-mode="editData">
          Edit DB objects</label
        >
      </div>
    </div>
    <div id="mapsvg-sizer">
      <div id="mapsvg" data-autoload="true" data-map-id="<?php echo esc_html($data["map"]->id); ?>"></div>
    </div>
  </div>
  <div class="stretch mapsvg-panel mapsvg-panel-right">
    <ul class="nav nav-tabs" id="mapsvg-tabs-menu">      
      <li class="toggle-tooltip nav-item" title="Settings">
        <a href="#tab_settings" class="nav-link active"><i class="bi-gear"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Colors">
        <a href="#tab_colors" class="nav-link"><i class="bi-palette"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Regions">
        <a href="#tab_regions" class="nav-link"><i class="bi bi-bounding-box-circles"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Database">
        <a href="#tab_database" class="nav-link"><i class="bi-database"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Filters">
        <a href="#tab_filters" class="nav-link"><i class="bi-funnel"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Google Maps">
        <a href="#tab_google_maps" class="nav-link"><i class="bi-google"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Directory">
        <a href="#tab_directory" class="nav-link"><i class="bi-window-sidebar"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Details view">
        <a href="#tab_details" class="nav-link"><i class="bi-person-vcard"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Actions">
        <a href="#tab_actions" class="nav-link"><i class="bi-mouse2"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Gallery">
        <a href="#tab_gallery" class="nav-link"><i class="bi-image"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Toggles">
        <a href="#tab_layers" class="nav-link"><i class="bi-toggles"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Templates">
        <a href="#tab_templates" class="nav-link"><i class="bi-braces-asterisk"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="Javascript">
        <a href="#tab_events" class="nav-link"><i class="bi-code"></i></a>
      </li>
      <li class="toggle-tooltip nav-item" title="CSS">
        <a href="#tab_css" class="nav-link"><i class="bi-stars"></i></a>
      </li>
      <li class="mapsvg-draw-menu-item nav-item">
        <a href="#tab_draw_region" class="nav-link">SVG Object</a>
      </li>
    </ul>
    <div id="mapsvg-mapform-container" class="stretch mapsvg-scrollable-content">
      <div class="tab-content" id="mapsvg-tabs">
        <div class="tab-pane active" id="tab_settings" data-controller="settings"></div>
        <!--                <div class="tab-pane" id="tab_choropleth" data-controller="choropleth"></div>-->
        <div class="tab-pane" id="tab_colors" data-controller="colors"></div>
        <div class="tab-pane" id="tab_regions" data-controller="regions"></div>
        <div class="tab-pane" id="tab_google_maps" data-controller="googlemaps"></div>
        <div class="tab-pane tab-markers" id="tab_markers" data-controller="markers"></div>
        <div class="tab-pane" id="tab_database" data-controller="database">
          <div class="full-flex" id="database-controller"></div>
        </div>
        <!-- <div class="tab-pane" id="tab_floors" data-controller="floors"></div>-->
        <div class="tab-pane" id="tab_layers" data-controller="layers"></div>
        <div class="tab-pane" id="tab_directory" data-controller="directory"></div>
        <div class="tab-pane" id="tab_details" data-controller="details"></div>
        <div class="tab-pane" id="tab_filters" data-controller="filters"></div>
        <div class="tab-pane" id="tab_actions" data-controller="actions"></div>
        <div class="tab-pane" id="tab_templates" data-controller="templates"></div>
        <div class="tab-pane" id="tab_events" data-controller="javascript"></div>
        <div class="tab-pane" id="tab_css" data-controller="css"></div>
        <div
          class="tab-pane mapsvg-draw-controller"
          id="tab_draw_region"
          data-controller="draw-region"
        ></div>
        <div class="tab-pane" id="tab_gallery" data-controller="gallery"></div>
      </div>
    </div>
  </div>
</div>
