(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-my-balance-converted', {
        title: __('My Balance Converted', 'mycred'),
        category: 'mycred',
        attributes: {
            ctype: {
                type: 'string',
                default: 'mycred_default'
            },
            rate: {
                type: 'number',
                default: 1
            },
            prefix: {
                type: 'string',
                default: ''
            },
            suffix: {
                type: 'string',
                default: ''
            }
        },
        edit: function (props) {
            var ctype = props.attributes.ctype;
            var rate = props.attributes.rate;
            var prefix = props.attributes.prefix;
            var suffix = props.attributes.suffix;

            var options = [];
            function setCType(value) {
                props.setAttributes({ctype: value});
            }
            function setRate(value) {
                props.setAttributes({rate: value});
            }
            function setPrefix(value) {
                props.setAttributes({prefix: value});
            }

            function setSuffix(value) {
                props.setAttributes({suffix: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            return el('div', {}, [
                el('p', {}, __('My Balance Converted Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to show a Conversion for.', 'mycred'),
                            value: ctype,
                            onChange: setCType,
                            options
                        }),
                        el(TextControl, {
                            label: __('Conversion Rate', 'mycred'),
                            help: __('', 'mycred'),
                            value: rate,
                            onChange: setRate
                        }),
                        el(TextControl, {
                            label: __('Prefix', 'mycred'),
                            help: __('', 'mycred'),
                            value: prefix,
                            onChange: setPrefix
                        }),
                        el(TextControl, {
                            label: __('Suffix', 'mycred'),
                            help: __('', 'mycred'),
                            value: suffix,
                            onChange: setSuffix
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