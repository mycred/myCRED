/**
 * mb Gutemberg block
 *  Copyright (c) 2001-2018. Matteo Bicocchi (Pupunzi)
 */
//
(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;

    registerBlockType('mycred-gb-blocks/mycred-affiliate-id', {
        title: __('Affiliate ID', 'mycred'),
        category: 'mycred',
        attributes: {
            type: {
                type: 'string'
            }
        },
        edit: function (props) {
            var pt_type = props.attributes.type;
            var content = props.attributes.content;
            var options = [];
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            function setPtType(value) {
                props.setAttributes({type: value});
            }

            return el('div', {}, [
                el('p', {}, __('Affiliate ID Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(SelectControl, {
                            label: __('Point Types', 'mycred'),
                            help: __('The point type you want to show the affiliate link for.', 'mycred'),
                            value: pt_type,
                            onChange: setPtType,
                            options
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