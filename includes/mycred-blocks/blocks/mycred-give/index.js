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
    var TextareaControl = wp.components.TextareaControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;

    registerBlockType('mycred-gb-blocks/mycred-give', {
        title: __('Give'),
        category: 'mycred',
        attributes: {
            amount: {
                type: 'string'
            },
            user_id: {
                type: 'string'
            },
            log: {
                type: 'string'
            },
            ref: {
                type: 'string',
                default: 'give'
            },
            limit: {
                type: 'string',
                default: 0
            },
            type: {
                type: 'string'
            },
            content: {
                type: 'string',
                default: ''
            }
        },
        edit: function (props) {
            var amount = props.attributes.amount;
            var user_id = props.attributes.user_id;
            var log = props.attributes.log;
            var ref = props.attributes.ref;
            var limit = props.attributes.limit;
            var pt_type = props.attributes.type;
            var content = props.attributes.content;

            var options = [];
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            function setRate(value) {
                props.setAttributes({amount: value});
            }
            function setUserId(value) {
                props.setAttributes({user_id: value});
            }
            function setLog(value) {
                props.setAttributes({log: value});
            }
            function setReference(value) {
                props.setAttributes({ref: value});
            }
            function setLimit(value) {
                props.setAttributes({limit: value});
            }
            function setPtType(value) {
                props.setAttributes({type: value});
            }
            function setContent(value) {
                props.setAttributes({content: value});
            }
            return el('div', {}, [
                el('p', {}, __( 'Give Shortcode', 'mycred' ) ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Amount', 'mycred'),
                            help: __('Required amount to give the user when this shortcode fires', 'mycred'),
                            value: amount,
                            onChange: setRate
                        }),
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __('Option to award a specific user. Use "current" to use five points to the user that views the shortcode. Can not be empty.', 'mycred'),
                            value: user_id,
                            onChange: setUserId
                        }),
                        el(TextControl, {
                            label: __('Log Entry', 'mycred'),
                            help: __('The log entry template. Can not be empty. Does not support HTML elements.', 'mycred'),
                            value: log,
                            onChange: setLog
                        }),
                        el(TextControl, {
                            label: __('Reference', 'mycred'),
                            help: __('A reference to log this transaction under. Can not be empty and must be a lowercase string. Instead of empty spaces please use underscores.', 'mycred'),
                            value: ref,
                            onChange: setReference
                        }),
                        el(TextControl, {
                            label: __('Limit', 'mycred'),
                            help: __('Optional limit the number of times a user can gain points from this shortcode. Use zero for no limit.', 'mycred'),
                            value: limit,
                            onChange: setLimit
                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to give.', 'mycred'),
                            value: pt_type,
                            onChange: setPtType,
                            options
                        }),
                        el(TextareaControl, {
                            label: __('Content', 'mycred'),
                            help: __('Content to show visitors viewing this shortcode. Leave empty to show nothing.', 'mycred'),
                            value: content,
                            onChange: setContent
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