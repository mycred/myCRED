/**
 * mb Gutemberg block
 *  Copyright (c) 2001-2018. Matteo Bicocchi (Pupunzi)
 */
(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var RichText = wp.blockEditor.RichText;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;


    registerBlockType('mycred-gb-blocks/mycred-best-user', {
        title: __('Best User', 'mycred'),
        category: 'mycred',
        attributes: {
            ref: {
                type: 'string'
            },
            from: {
                type: 'string'
            },
            until: {
                type: 'string'
            },
            types: {
                type: 'string'
            },
            nothing: {
                type: 'string',
                default: 'No user found'
            },
            order: {
                type: 'string'
            },
            avatar: {
                type: 'integer',
                default: '50'
            },
            content: {
                type: 'string',
                default: '<div class="mycred-best-user">%avatar%<h4>%display_name%</h4></div>'
            }
        },
        edit: function (props) {
            var ref = props.attributes.ref;
            var from = props.attributes.from;
            var until = props.attributes.until;
            var types = props.attributes.types;
            var nothing = props.attributes.nothing;
            var order = props.attributes.order;
            var avatar = props.attributes.avatar;
            var content = props.attributes.content;

            function setRef(value) {
                props.setAttributes({ref: value});
            }
            function setFrom(value) {
                props.setAttributes({from: value});
            }
            function setUntil(value) {
                props.setAttributes({until: value});
            }
            function setTypes(value) {
                props.setAttributes({types: value});
            }
            function setNth(value) {
                props.setAttributes({nothing: value});
            }
            function setOrder(value) {
                props.setAttributes({order: value});
            }
            function setAvatar(value) {
                props.setAttributes({avatar: value});
            }

            function setContent(value) {
                props.setAttributes({content: value});
            }

            return el('div', {}, [
                el('p', {}, __('Best User Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextareaControl, {
                            label: __('Reference(s)', 'mycred'),
                            help: __('Comma separated list of references to add up.', 'mycred'),
                            rows: 2,
                            value: ref,
                            onChange: setRef
                        }),
                        el(TextControl, {
                            label: __('From', 'mycred'),
                            help: __('Option to sum up results from a specific date. Can be used in combination with "Until". Leave empty if not used.', 'mycred'),
                            value: from,
                            onChange: setFrom
                        }),
                        el(TextControl, {
                            label: __('Until', 'mycred'),
                            help: __('Option to sum up results until a specific date. Can be used in combination with "From". Leave empty if not used.', 'mycred'),
                            value: until,
                            onChange: setUntil
                        }),
                        el(TextareaControl, {
                            label: __('Point Type(s)', 'mycred'),
                            help: __('A single point type or a comma separated list of point types to use. Use "mycred_default" for the default point type. Can not be empty.', 'mycred'),
                            rows: 2,
                            value: types,
                            onChange: setTypes
                        }),
                        el(TextControl, {
                            label: __('No Results', 'mycred'),
                            help: __('Information to show if no results were found. No HTML allowed.', 'mycred'),
                            value: nothing,
                            onChange: setNth
                        }),
                        el(SelectControl, {
                            label: __('Order', 'mycred'),
                            help: __('Order of the results.', 'mycred'),
                            value: order,
                            onChange: setOrder,
                            options: [
                                {label: 'Descending', value: 'DESC'},
                                {label: 'Ascending', value: 'ASC'}
                            ]
                        }),
                        el(TextControl, {
                            label: __('Avatar', 'mycred'),
                            help: __('The size of the avatar, if the %avatar% template tag is used in your template', 'mycred'),
                            value: avatar,
                            onChange: setAvatar
                        }),
                        el(TextareaControl, {
                            label: __('Content', 'mycred'),
                            help: __('Content to show visitors viewing this shortcode. Leave empty to show nothing.', 'mycred'),
                            value: content,
                            onChange: setContent,
                            rows:2
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