/*
  Add custom fields to default blocks

  - Add “Disable uppercase” and “Text wrapping” fields to heading blocks
  - Add “Hide Bullets” field to list blocks
*/
const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;
const { InspectorControls } = wp.blockEditor || wp.editor;
const { PanelBody, ToggleControl, SelectControl } = wp.components;

// Set which blocks should get these custom fields
const HEADING_BLOCKS = 'core/heading';
const LIST_BLOCK = 'core/list';

// 1. Add new attributes to specific blocks
function addCustomBlockAttributes(settings, name) {
    if (name === HEADING_BLOCKS) {
        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                disableUppercase: { type: 'boolean', default: false },
                textWrap: { type: 'string', default: 'default' }, // default, pretty, balance
            },
        };
    }
    if (name === LIST_BLOCK) {
        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                hideBullets: { type: 'boolean', default: false },
            },
            supports: {
                ...settings.supports,
                align: ['wide', 'full'], // Enable wide/full alignment
            },
        };
    }
    return settings;
}
addFilter('blocks.registerBlockType', 'threespot/custom-block-attrs', addCustomBlockAttributes);

// 2. Add sidebar controls
const withCustomBlockControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        if (props.name === HEADING_BLOCKS) {
            const { attributes, setAttributes } = props;
            const { disableUppercase, textWrap } = attributes;

            return (
                <Fragment>
                    <InspectorControls>
                        <PanelBody title="Text Options" initialOpen={true}>
                            <ToggleControl
                                label="Disable uppercase"
                                checked={!!disableUppercase}
                                onChange={(value) => setAttributes({ disableUppercase: value })}
                            />
                            <SelectControl
                                label="Text Wrapping"
                                value={textWrap}
                                options={[
                                    { label: 'Default', value: 'default' },
                                    { label: 'Pretty', value: 'pretty' },
                                    { label: 'Balance', value: 'balance' },
                                ]}
                                onChange={(value) => setAttributes({ textWrap: value })}
                            />
                        </PanelBody>
                    </InspectorControls>
                    <BlockEdit {...props} />
                </Fragment>
            );
        }
        if (props.name === LIST_BLOCK) {
            const { attributes, setAttributes } = props;
            const { hideBullets } = attributes;

            return (
                <Fragment>
                    <InspectorControls>
                        <PanelBody title="List Options" initialOpen={true}>
                            <ToggleControl
                                label="Hide Bullets"
                                checked={!!hideBullets}
                                onChange={(value) => setAttributes({ hideBullets: value })}
                            />
                        </PanelBody>
                    </InspectorControls>
                    <BlockEdit {...props} />
                </Fragment>
            );
        }
        return <BlockEdit {...props} />;
    };
}, 'withCustomBlockControls');
addFilter('editor.BlockEdit', 'threespot/custom-block-controls', withCustomBlockControls);

// 3. Add classes to the block root in the editor
const withCustomBlockClasses = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        let newClassName = props.className || '';

        if (props.name === HEADING_BLOCKS) {
            const { disableUppercase, textWrap } = props.attributes;
            if (disableUppercase) {
                newClassName += ' no-uppercase';
            }
            if (textWrap && textWrap !== 'default') {
                newClassName += ` text-wrap-${textWrap}`;
            }
        }
        if (props.name === LIST_BLOCK) {
            const { hideBullets } = props.attributes;
            if (hideBullets) {
                newClassName += ' no-bullets';
            }
        }

        props = { ...props, className: newClassName.trim() };
        return <BlockListBlock {...props} />;
    };
}, 'withCustomBlockClasses');
addFilter('editor.BlockListBlock', 'threespot/custom-block-editor-classes', withCustomBlockClasses);

// 4. Apply front-end classes
function applyCustomBlockProps(extraProps, blockType, attributes) {
    let classNames = extraProps.className || '';

    if (blockType.name === HEADING_BLOCKS) {
        if (attributes.disableUppercase) {
            classNames += ' no-uppercase';
        }
        if (attributes.textWrap && attributes.textWrap !== 'default') {
            classNames += ` text-wrap-${attributes.textWrap}`;
        }
    }
    if (blockType.name === LIST_BLOCK) {
        if (attributes.hideBullets) {
            classNames += ' no-bullets';
        }
    }

    extraProps.className = classNames.trim();
    return extraProps;
}
addFilter('blocks.getSaveContent.extraProps', 'threespot/custom-block-props', applyCustomBlockProps);
