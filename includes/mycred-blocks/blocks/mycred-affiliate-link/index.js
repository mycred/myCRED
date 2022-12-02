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
    var TextControl = wp.components.TextControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;

    registerBlockType('mycred-gb-blocks/mycred-affiliate-link', {
        title: __('Affiliate Link', 'mycred'),
        category: 'mycred',
        attributes: {
            pt_type: {
                type: 'string'
            },
            url: {
                type: 'string'
            }
        },
        edit: function (props) {
            var pt_type = props.attributes.pt_type;
            var url = props.attributes.url;
            var options = [];
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            function setPtType(value) {
                props.setAttributes({pt_type: value});
            }
            function setUrl(value) {
                props.setAttributes({url: value});
            }
            return el('div', {}, [
                el('p', {}, __('Affiliate Link Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(SelectControl, {
                            label: __('Point Types', 'mycred'),
                            help: __('The point type you want to show the affiliate link for.', 'mycred'),
                            value: pt_type,
                            onChange: setPtType,
                            options
                        }),
                        el(TextControl, {
                            label: __('URL', 'mycred'),
                            help: __('The URL to attach the current users affiliate ID to. No ID is attached for visitors that are not logged in.', 'mycred'),
                            type: 'url',
                            value: url,
                            onChange: setUrl

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