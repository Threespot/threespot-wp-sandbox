<?php if (isset($_GET['mapsvg_shortcode_inline'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    echo do_shortcode(sanitize_text_field(wp_unslash($_GET['mapsvg_shortcode_inline']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    die();
} ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">

    <?php if (! get_theme_support('title-tag')) : ?>
        <title><?php wp_title(); ?></title>
    <?php endif; ?>

    <?php wp_head(); ?>

    <style>
        #wpadminbar {
            display: none;
        }

        html {
            margin-top: 0 !important;
        }

        body {
            position: relative;
            background-color: transparent !important;
        }

        article .entry-header {
            margin: 0 !important;
        }

        article .entry-content {
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: 100% !important;
            padding: 0 !important;
        }

        article .entry-content>*:first-child {
            margin-top: 0 !important;
        }

        article .entry-content>* {
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: 100% !important;
        }

        article .entry-header>* {
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: 100% !important;
        }

        .edit-link {
            display: none !important;
        }
    </style>

</head>

<body <?php body_class('blank-slate mapsvg-embedded-post'); ?>>

    <?php while (have_posts()) : ?>

        <?php the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php $title = the_title('<h1 class="entry-title">', '</h1>', false); ?>
            <?php if (strlen($title) > 29 && !isset($_GET["mapsvg_shortcode"])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            ?>
                <header class="entry-header page-header">
                    <?php echo esc_html($title); ?>
                </header>
            <?php } ?>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>

        </article><!-- #post-<?php the_ID(); ?> -->

    <?php endwhile; ?>

    <?php wp_footer(); ?>


    <script>
        (function(window) {
            /**
             * Resize sensor.
             * @private
             * @param element
             * @param callback
             * @constructor
             */
            window.ResizeSensor = function(element, callback) {

                var _this = this;

                _this.element = element;
                _this.callback = callback;

                var style = getComputedStyle(element);
                var zIndex = parseInt(style.zIndex);
                if (isNaN(zIndex)) {
                    zIndex = 0;
                };
                zIndex--;

                _this.expand = document.createElement('div');
                _this.expand.style.position = "absolute";
                _this.expand.style.left = "0px";
                _this.expand.style.top = "0px";
                _this.expand.style.right = "0px";
                _this.expand.style.bottom = "0px";
                _this.expand.style.overflow = "hidden";
                _this.expand.style.zIndex = zIndex;
                _this.expand.style.visibility = "hidden";

                var expandChild = document.createElement('div');
                expandChild.style.position = "absolute";
                expandChild.style.left = "0px";
                expandChild.style.top = "0px";
                expandChild.style.width = "10000000px";
                expandChild.style.height = "10000000px";
                _this.expand.appendChild(expandChild);

                _this.shrink = document.createElement('div');
                _this.shrink.style.position = "absolute";
                _this.shrink.style.left = "0px";
                _this.shrink.style.top = "0px";
                _this.shrink.style.right = "0px";
                _this.shrink.style.bottom = "0px";
                _this.shrink.style.overflow = "hidden";
                _this.shrink.style.zIndex = zIndex;
                _this.shrink.style.visibility = "hidden";

                var shrinkChild = document.createElement('div');
                shrinkChild.style.position = "absolute";
                shrinkChild.style.left = "0px";
                shrinkChild.style.top = "0px";
                shrinkChild.style.width = "200%";
                shrinkChild.style.height = "200%";
                _this.shrink.appendChild(shrinkChild);

                _this.element.appendChild(_this.expand);
                _this.element.appendChild(_this.shrink);

                var size = element.getBoundingClientRect();

                _this.currentWidth = size.width;
                _this.currentHeight = size.height;

                _this.setScroll();

                _this.expand.addEventListener('scroll', function() {
                    _this.onScroll()
                });
                _this.shrink.addEventListener('scroll', function() {
                    _this.onScroll()
                });
            };
            window.ResizeSensor.prototype.onScroll = function() {
                var _this = this;
                var size = _this.element.getBoundingClientRect();

                var newWidth = size.width;
                var newHeight = size.height;

                if (newWidth != _this.currentWidth || newHeight != _this.currentHeight) {
                    _this.currentWidth = newWidth;
                    _this.currentHeight = newHeight;
                    _this.callback();
                }

                this.setScroll();
            };
            window.ResizeSensor.prototype.setScroll = function() {
                this.expand.scrollLeft = 10000000;
                this.expand.scrollTop = 10000000;
                this.shrink.scrollLeft = 10000000;
                this.shrink.scrollTop = 10000000;
            };
            window.ResizeSensor.prototype.destroy = function() {
                this.expand.remove();
                this.shrink.remove();
            };

        })(window);

        var setSize = function() {
            var w = document.documentElement.offsetWidth;
            var h = document.documentElement.offsetHeight;
            window.parent.postMessage({
                width: w,
                height: h
            }, '*');
        };

        window.addEventListener("load", function() {
            var resizeSensor = new window.ResizeSensor(document.body, function() {
                setSize();
            });

            setSize();
        })
    </script>

    <style>
        html {
            margin-top: 0 !important;
        }
    </style>

</body>

</html>