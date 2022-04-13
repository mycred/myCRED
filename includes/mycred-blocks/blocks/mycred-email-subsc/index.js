(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.editor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;

    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-email-subsc', {
        title: __('Email Subscriptions', 'mycred'),
        category: 'mycred',
        attributes: {
            success: {
                type: 'string',
                default: 'Settings Updated'
            }
        },
        edit: function (props) {
            var success = props.attributes.success;

            function setSuccess(value) {
                props.setAttributes({success: value});
            }

            return el('div', {}, [
                el('p', {}, __('Email Subscriptions Shortcode', 'mycred')
                        ),
                el(InspectorControls, null,
                        el(TextControl, {
                            label: __('Success', 'mycred'),
                            help: __('Message to show when settings have been changed.', 'mycred'),
                            value: success,
                            onChange: setSuccess
                        })
                        )
            ]);
        },
        save: function (props) {
            return null;
        }
    });
})(window.wp);