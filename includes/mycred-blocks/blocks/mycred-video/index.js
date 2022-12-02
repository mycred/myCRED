(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-video', {
        title: __('Video', 'mycred'),
        category: 'mycred',
        attributes: {
            video_id: {
                type: 'string'
            },
            width: {
                type: 'string',
                default: '560'
            },
            height: {
                type: 'string',
                default: '315'
            },
            ctype: {
                type: 'string'
            },
            amount: {
                type: 'string'
            },
            logic: {
                type: 'string'
            },
            interval: {
                type: 'string'
            }
        },
        edit: function (props) {
            console.log(props.attributes)
            var video_id = props.attributes.video_id;
            var width = props.attributes.width;
            var height = props.attributes.height;
            var ctype = props.attributes.ctype;
            var amount = props.attributes.amount;
            var logic = props.attributes.logic;
            var interval = props.attributes.interval;
            var options = [];
            function setVideoId(value) {
                props.setAttributes({video_id: value});
            }
            function setWidth(value) {
                props.setAttributes({width: value});
            }

            function setHeight(value) {
                props.setAttributes({height: value});
            }
            function setAmount(value) {
                props.setAttributes({amount: value});
            }
            function setLogic(value) {
                props.setAttributes({logic: value});
            }
            function setInterval(value) {
                props.setAttributes({interval: value});
            }

            function setPtType(value) {
                props.setAttributes({ctype: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            return el('div', {}, [
                el('p', {}, __('Video Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Video ID', 'mycred'),
                            help: __('Required video ID to show. No URls or embed codes! Just the video ID', 'mycred'),
                            value: video_id,
                            onChange: setVideoId
                        }),
                        el(TextControl, {
                            label: __('Width', 'mycred'),
                            help: __('Width of the Iframe', 'mycred'),
                            value: width,
                            onChange: setWidth
                        }),
                        el(TextControl, {
                            label: __('Height', 'mycred'),
                            help: __('Height of the Iframe.', 'mycred'),
                            value: height,
                            onChange: setHeight
                        }),
                        el(TextControl, {
                            label: __('Amount', 'mycred'),
                            help: __('The amount of points to give users for watching this video. Leave empty to use your default settings.', 'mycred'),
                            value: amount,
                            onChange: setAmount
                        }),
                        el(SelectControl, {
                            label: __('Logic', 'mycred'),
                            help: __('The award logic to use', 'mycred'),
                            value: logic,
                            onChange: setLogic,
                            options: [
                                {label: 'Play - Award points as soon as video starts playing', value: 'play'},
                                {label: 'Full - Award points only if user watches the entire video', value: 'full'},
                                {label: 'Interval - Award points every x seconds watched', value: 'interval'}
                            ]
                        }),
                        el(TextControl, {
                            label: __('Interval', 'mycred'),
                            help: __('Number of seconds that a user must watch to get points. Only use this if you have set "Logic" to "Interval".', 'mycred'),
                            value: interval,
                            onChange: setInterval
                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to show', 'mycred'),
                            value: ctype,
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