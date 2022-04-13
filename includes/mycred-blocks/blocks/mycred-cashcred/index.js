(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-cashcred', {
        title: __('Cashcred', 'mycred'),
        category: 'mycred',
        attributes: {
            button: {
                type: 'string',
                default: 'Submit Request'
            },
            gateways: {
                type: 'array'
            },
            types: {
                type: 'array'
            },
            excluded: {
                type: 'string',
                default: 'You have excluded from this point type'
            },
            insufficient: {
                type: 'string',
                default: 'Insufficient Points for Withdrawal.'
            },
            
        },
        edit: function (props) {
            var button = props.attributes.button;
            var gateways = props.attributes.gateways;
            var types = props.attributes.types;
            var excluded = props.attributes.excluded;
            var insufficient = props.attributes.insufficient;
            var options = [];
            var gateway_options = [];

            function setGateways(value) {
                props.setAttributes({gateways: value});
            }

            function setButton(value) {
                props.setAttributes({button: value});
            }

            function setTypes(value) {
                props.setAttributes({types: value});
            }
            function setExclude(value) {
                props.setAttributes({excluded: value});
            }
            function setInsufficient(value) {
                props.setAttributes({insufficient: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            console.log(mycred_cashcred_gateways);

            Object.keys(mycred_cashcred_gateways).forEach(function (key) {
                gateway_options.push({
                    label: mycred_cashcred_gateways[key],
                    value: key
                });
            });

            return el('div', {}, [
                el('p', {}, __('Cashcred Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Button Label', 'mycred'),
                            help: __('The label for the form submit button.', 'mycred'),
                            value: button,
                            onChange: setButton
                        }),
                        el(SelectControl, {
                            multiple: true,
                            label: __('Gateways', 'mycred'),
                            help: __('Select the Gateways to enforce the use of a specific gateways', 'mycred'),
                            value: gateways,
                            onChange: setGateways,
                            options: gateway_options,
                            style: { height:"100px" }
                        }),
                        el(SelectControl, {
                            multiple: true,
                            label: __('Point Types', 'mycred'),
                            help: __('The point type you want to show.', 'mycred'),
                            value: types,
                            onChange: setTypes,
                            options: options,
                            style: { height:"100px" }
                        }),
                        el(TextControl, {
                            label: __('Excluded Message', 'mycred'),
                            help: __('By default, the current user will see this msg "You have excluded from this point type.."', 'mycred'),
                            value: excluded,
                            onChange: setExclude
                        }),
                        el(TextControl, {
                            label: __('Insufficient Message', 'mycred'),
                            help: __('By default, the current user will see this msg "Insufficient Points for Withdrawal."', 'mycred'),
                            value: insufficient,
                            onChange: setInsufficient
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