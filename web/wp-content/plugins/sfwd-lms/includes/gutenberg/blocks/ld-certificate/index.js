/**
 * LearnDash Block ld-certificate
 *
 * @since 3.2
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
    ldlms_get_post_edit_meta,
    ldlms_get_custom_label,
    ldlms_get_integer_value
} from '../ldlms.js';

/**
 * Internal block libraries
 */
const { __, _x, sprintf } = wp.i18n;
const {
	registerBlockType,
 } = wp.blocks;

 const {
    InspectorControls,
 } = wp.editor;

const {
    ServerSideRender,
    PanelBody,
    TextControl,
    ToggleControl
} = wp.components;

registerBlockType(
    'learndash/ld-certificate',
    {
        title: __( 'LearnDash Certificate', 'learndash' ),
        description: _x('This shortcode shows a Certificate download link.', 'learndash'),
        icon: 'welcome-learn-more',
        category: 'learndash-blocks',
        supports: {
            customClassName: false,
        },
        attributes: {
            course_id: {
                type: 'string',
                default: '',
            },
            quiz_id: {
                type: 'string',
                default: '',
            },
            user_id: {
                type: 'string',
                default: '',
            },
            label: {
                type: 'string',
                default: '',
            },
            class_html: {
                type: 'string',
                default: '',
            },
            context: {
                type: 'string',
                default: '',
            },
            callback: {
                type: 'string',
                default: '',
            },
            preview_show: {
                type: 'boolean',
                default: 1
            },
            preview_course_id: {
                type: 'string',
                default: '',
            },
            preview_quiz_id: {
                type: 'string',
                default: '',
            },
            preview_user_id: {
                type: 'string',
                default: '',
            },
            example_show: {
                type: 'boolean',
                default: 0
            },
        },
        edit: props => {
            const { attributes: { course_id, quiz_id, user_id, label, class_html, context, callback, preview_show, preview_course_id, preview_quiz_id, preview_user_id, example_show }, className, setAttributes } = props;

            const inspectorControls = (
                <InspectorControls>
                    <PanelBody
                        title={__('Settings', 'learndash')}
                    >
                        <TextControl
                            label={sprintf(_x('%s ID', 'Course ID', 'learndash'), ldlms_get_custom_label('course') )}
                            help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: course, course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course' ) ) }
                            value={course_id || ''}
                            onChange={course_id => setAttributes({ course_id })}
                        />
                        <TextControl
                            label={sprintf(_x('%s ID', 'Quiz ID', 'learndash'), ldlms_get_custom_label('quiz'))}
                            help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: quiz, quiz', 'learndash'), ldlms_get_custom_label('quiz'), ldlms_get_custom_label('quiz'))}
                            value={quiz_id || ''}
                            onChange={quiz_id => setAttributes({ quiz_id })}
                        />
                        <TextControl
                            label={__('User ID', 'learndash')}
                            help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
                            value={user_id || ''}
                            onChange={user_id => setAttributes({ user_id })}
                        />
                        <TextControl
                            label={__('Label', 'learndash')}
                            help={__('Label for link shown to user', 'learndash')}
                            value={label || ''}
                            onChange={label => setAttributes({ label })}
                        />
                        <TextControl
                            label={__('Class', 'learndash')}
                            help={__('HTML class for link element', 'learndash')}
                            value={class_html || ''}
                            onChange={class_html => setAttributes({ class_html })}
                        />
                        <TextControl
                            label={__('Context', 'learndash')}
                            help={__('User defined value to be passed into shortcode handler', 'learndash')}
                            value={context || ''}
                            onChange={context => setAttributes({ context })}
                        />
                        <TextControl
                            label={__('Callback', 'learndash')}
                            help={__('Custom callback function to be used instead of default output', 'learndash')}
                            value={callback || ''}
                            onChange={callback => setAttributes({ callback })}
                        />
                        </PanelBody>
                    <PanelBody
                        title={__('Preview', 'learndash')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Show Preview', 'learndash')}
                            checked={!!preview_show}
                            onChange={preview_show => setAttributes({ preview_show })}
                        />
                        <TextControl
                            label={sprintf(_x('%s ID', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
                            help={sprintf(_x('Enter a %s ID to test preview', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
                            value={preview_course_id || ''}
                            type={'number'}
                            onChange={preview_course_id => setAttributes({ preview_course_id })}
                        />
                        <TextControl
                            label={sprintf(_x('%s ID', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz'))}
                            help={sprintf(_x('Enter a %s ID to test preview', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz'))}
                            value={preview_quiz_id || ''}
                            type={'number'}
                            onChange={preview_quiz_id => setAttributes({ preview_quiz_id })}
                        />
                        <TextControl
                            label={__('User ID', 'learndash')}
                            help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
                            value={preview_user_id || ''}
                            onChange={preview_user_id => setAttributes({ preview_user_id })}
                        />
                    </PanelBody>
                </InspectorControls>
            );

            /*
            let ld_block_error_message = '';
            let show_course_id = ldlms_get_integer_value(course_id);
            let show_quiz_id = ldlms_get_integer_value(quiz_id);

            let post_type = ldlms_get_post_edit_meta('post_type');    

            if (show_course_id === 0) {
                if (post_type === 'sfwd-courses') {
                    show_course_id = ldlms_get_post_edit_meta('post_id');
                } else if ((post_type === 'sfwd-lessons') || (post_type === 'sfwd-topic') || (post_type === 'sfwd-quiz')) {
                    show_course_id = ldlms_get_post_edit_meta('course_id');
                }
                show_course_id = ldlms_get_integer_value(show_course_id);
            }
            if (show_quiz_id === 0) {
                if (ldlms_get_post_edit_meta('post_type') === 'sfwd-quiz') {
                    show_quiz_id = ldlms_get_post_edit_meta('post_id');
                    show_quiz_id = ldlms_get_integer_value(show_quiz_id);
                }
            }

            if ((show_course_id == 0) && (show_quiz_id == 0)) {
                ld_block_error_message = sprintf(_x('%1$s ID or %2$s ID is required when not used within a %2$s.', 'placeholders: Course, Quz, Course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('quiz'), ldlms_get_custom_label('course'));
            }

            if (ld_block_error_message.length) {
                ld_block_error_message = (<span className="learndash-block-error-message">{ld_block_error_message}</span>);
            }

            const outputBlock = (
                <div className={className}>
                    <div className="learndash-block-inner">
                        {ld_block_error_message}
                        <InnerBlocks />
                    </div>
                </div>
            );
            */
            
            function do_serverside_render(attributes) {
                if (attributes.preview_show == true) {
                    return <ServerSideRender
                        block="learndash/ld-certificate"
                        attributes={attributes}
                    />
                } else {
                    return __('[ld_certificate] shortcode output shown here', 'learndash');
                }
            }

            return [
                inspectorControls,
                do_serverside_render(props.attributes)
            ];
        },
	},
);
