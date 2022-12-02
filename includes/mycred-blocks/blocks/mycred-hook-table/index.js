(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-hook-table', {
        title: __('Hook Table', 'mycred'),
        category: 'mycred',
        attributes: {
            type: {
                type: 'string'
            },
            show_gains: {
                type: 'bool',
                default: true
            },
            user: {
                type: 'string',
                default: '-user-'
            },
            post: {
                type: 'string',
                default: '-post-'
            },
            comment: {
                type: 'string',
                default: '-comment-'
            },
            amount: {
                type: 'string'
            },
            nothing: {
                type: 'string'
            }
        },
        edit: function (props) {
            var type = props.attributes.type;
            var show_gains = props.attributes.show_gains;
            var user = props.attributes.user;
            var post = props.attributes.post;
            var comment = props.attributes.comment;
            var amount = props.attributes.amount;
            var nothing = props.attributes.nothing;

            var options = [];
            function setType(value) {
                props.setAttributes({type: value});
            }
            function setShowGains(value) {
                props.setAttributes({show_gains: value});
            }
            function setUser(value) {
                props.setAttributes({user: value});
            }
            function setPost(value) {
                props.setAttributes({post: value});
            }
            function setComment(value) {
                props.setAttributes({comment: value});
            }
            function setAmount(value) {
                props.setAttributes({amount: value});
            }
            function setNth(value) {
                props.setAttributes({nothing: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            return el('div', {}, [
                el('p', {}, __('Hook Table Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type to show hooks for.', 'mycred'),
                            value: type,
                            onChange: setType,
                            options
                        }),
                        el(ToggleControl, {
                            label: __('Show Gains', 'mycred'),
                            checked: show_gains,
                            onChange: setShowGains
                        }),
                        el(TextControl, {
                            label: __('User', 'mycred'),
                            help: __('Text to replace all user related template tags with.', 'mycred'),
                            value: user,
                            onChange: setUser

                        }),
                        el(TextControl, {
                            label: __('Post Related Template Tags', 'mycred'),
                            help: __('Text to replace all post related template tags with.', 'mycred'),
                            value: post,
                            onChange: setPost

                        }),
                        el(TextControl, {
                            label: __('Comment Related Template Tags', 'mycred'),
                            help: __('Text to replace all comment related template tags with.', 'mycred'),
                            value: comment,
                            onChange: setComment

                        }),
                        el(TextControl, {
                            label: __('Amount Related Template Tags', 'mycred'),
                            help: __('Text to replace all amount related template tags with.', 'mycred'),
                            value: amount,
                            onChange: setAmount

                        }),
                        el(TextControl, {
                            label: __('No Hooks', 'mycred'),
                            help: __('Text to show when there are no active hooks for the selected point type.', 'mycred'),
                            value: nothing,
                            onChange: setNth
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