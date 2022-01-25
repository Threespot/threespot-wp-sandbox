/**
 * LearnDash Block ld-quiz-complete
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
    InnerBlocks,
    InspectorControls,
 } = wp.editor;

const {
    ServerSideRender,
    PanelBody,
    TextControl,
    ToggleControl
} = wp.components;

registerBlockType(
    'learndash/ld-quiz-complete',
    {
        title: sprintf(_x('LearnDash %s Complete', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz')),
        description: sprintf(_x('This block shows the content if the user is has completed the %s.', 'placeholders: quiz', 'learndash'), ldlms_get_custom_label('quiz')),
        icon: 'star-filled',
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
        },
        edit: props => {
            const { attributes: { course_id, quiz_id, user_id }, className, setAttributes } = props;

            const inspectorControls = (
                <InspectorControls>
                    <PanelBody
                        title={__('Settings', 'learndash')}
                    >
                        <TextControl
                            label={sprintf(_x('%s ID', 'Quiz ID', 'learndash'), ldlms_get_custom_label('quiz'))}
                            help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: quiz, quiz', 'learndash'), ldlms_get_custom_label('quiz'), ldlms_get_custom_label('quiz'))}
                            value={quiz_id || ''}
                            onChange={quiz_id => setAttributes({ quiz_id })}
                        />
                        <TextControl
                            label={sprintf(_x('%s ID', 'Course ID', 'learndash'), ldlms_get_custom_label('course') )}
                            help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: course, course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course' ) ) }
                            value={course_id || ''}
                            onChange={course_id => setAttributes({ course_id })}
                        />
                        
                        <TextControl
                            label={__('User ID', 'learndash')}
                            help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
                            value={user_id || ''}
                            onChange={user_id => setAttributes({ user_id })}
                        />
                    </PanelBody>
                </InspectorControls>
            );

            let ld_block_error_message = '';
            let preview_quiz_id = ldlms_get_integer_value(quiz_id);
            if (preview_quiz_id === 0) {
                if ( 'sfwd-quiz' === ldlms_get_post_edit_meta('post_type') ) {
                    preview_quiz_id = ldlms_get_post_edit_meta('post_id');
                    preview_quiz_id = ldlms_get_integer_value(preview_quiz_id);
                }
                if (preview_quiz_id == 0) {
                    ld_block_error_message = sprintf(_x('%1$s ID is required when not used within a %2$s.', 'placeholders: Quiz, Quiz', 'learndash'), ldlms_get_custom_label('quiz'), ldlms_get_custom_label('quiz'));
                }
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

            return [
                inspectorControls,
                outputBlock
            ];
        },
        save: props => {
            return (
                <InnerBlocks.Content />
            );
        }
	},
);
