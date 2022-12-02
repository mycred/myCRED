(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-load-coupon', {
        title: __('Load Coupon', 'mycred'),
        category: 'mycred',
        attributes: {
            label: {
                type: 'string',
                default: 'Coupon'
            },
            button: {
                type: 'string',
                default: 'Apply Coupon'
            },
            placeholder: {
                type: 'string'
            }
        },
        edit: function (props) {
            var label = props.attributes.label;
            var button = props.attributes.button;
            var placeholder = props.attributes.placeholder;

            function setLabel(value) {
                props.setAttributes({label: value});
            }

            function setButton(value) {
                props.setAttributes({button: value});
            }

            function setPlaceholder(value) {
                props.setAttributes({placeholder: value});
            }

            return el('div', {}, [
                el('p', {}, __('Load Coupon Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Label', 'mycred'),
                            help: __('The coupon label. Can not be empty.', 'mycred'),
                            value: label,
                            onChange: setLabel

                        }),
                        el(TextControl, {
                            label: __('Button Label', 'mycred'),
                            help: __('The form submit buttons label.', 'mycred'),
                            value: button,
                            onChange: setButton

                        }),
                        el(TextControl, {
                            label: __('Placeholder', 'mycred'),
                            help: __('The placeholder label for the coupon field.', 'mycred'),
                            value: placeholder,
                            onChange: setPlaceholder
                        }),
                    )
                )
            ]);
        },
        save: function (props) {
            return null;
        }
    });
})(window.wp);