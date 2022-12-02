(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-chart-top-instance', {
        title: __('Chart Top Instance', 'mycred'),
        category: 'mycred',
        attributes: {
            type : {
                type: 'string',
                default: 'bar'
            },
            ctype : {
                type: 'string',
            },
            number : {
                type: 'string',
                default: '10'
            },
            order : {
                type: 'string',
                default: 'DESC'
            },
            title : {
                type: 'string',
            },
            animate : {
                type: 'string',
                default: '1'
            },
            bezier : {
                type: 'string',
                default: '1'
            },
            labels : {
                type: 'string',
                default: '1'
            },
            legend : {
                type: 'string',
                default: '1'
            },
            width : {
                type: 'string'
            },
            height : {
                type: 'string'
            }
        },
        edit: function (props) {
            var type = props.attributes.type;
            var ctype = props.attributes.ctype;
            var number = props.attributes.number;
            var order = props.attributes.order;
            var title = props.attributes.title;
            var animate = props.attributes.animate;
            var bezier = props.attributes.bezier;
            var labels = props.attributes.labels;
            var legend = props.attributes.legend;
            var width = props.attributes.width;
            var height = props.attributes.height;

            function setTypes(value) {
                props.setAttributes({type: value});
            }

            function setPtypes(value) {
                props.setAttributes({ctype: value});
            }

            function setNumber(value) {
                props.setAttributes({number: value});
            }

            function setOrder(value) {
                props.setAttributes({order: value});
            }

            function setTitle(value) {
                props.setAttributes({title: value});
            }

            function setAnimate(value) {
                props.setAttributes({animate: value});
            }

            function setBezier(value) {
                props.setAttributes({bezier: value});
            }

            function setLabels(value) {
                props.setAttributes({labels: value});
            }

            function setLegend(value) {
                props.setAttributes({legend: value});
            }

            function setWidth(value) {
                props.setAttributes({width: value});
            }

             function setHeight(value) {
                props.setAttributes({height: value});
            }

            return el('div', {}, [
                el('p', {}, __('Chart Top Instance Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Type', 'mycred'),
                            help: __('The chart type to render. Supports: pie, doughnut, line, bar, radar and polarArea', 'mycred'),
                            value: type,
                            onChange: setTypes

                        }),
                        el(TextControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type we want to show data for. Should only be used when you need to show data for a custom point type.', 'mycred'),
                            value: ctype,
                            onChange: setPtypes

                        }),
                        el(TextControl, {
                            label: __('Number', 'mycred'),
                            help: __('The number of balances to show.', 'mycred'),
                            value: number,
                            onChange: setNumber

                        }),
                        el(TextControl, {
                            label: __('Order', 'mycred'),
                            help: __('The order of which the periods should be sorted.', 'mycred'),
                            value: order,
                            onChange: setOrder

                        }),
                        el(TextControl, {
                            label: __('Title', 'mycred'),
                            help: __('To set a title for the chart.', 'mycred'),
                            value: title,
                            onChange: setTitle

                        }),
                        el(TextControl, {
                            label: __('Animate', 'mycred'),
                            help: __('If the chart should be animated (1) or not (0).', 'mycred'),
                            value: animate,
                            onChange: setAnimate

                        }),
                        el(TextControl, {
                            label: __('Bezier', 'mycred'),
                            help: __('If line charts should use bezier curves (1) or not (0)', 'mycred'),
                            value: bezier,
                            onChange: setBezier

                        }),
                        el(TextControl, {
                            label: __('Labels', 'mycred'),
                            help: __('If labels should be shown in the chart (1) or not (0). Not all chart types use labels, it is mainly for bar and line charts where we have both an x and y axis.', 'mycred'),
                            value: labels,
                            onChange: setLabels
                        }),
                        el(TextControl, {
                            label: __('Legend', 'mycred'),
                            help: __('If a legend should be shown in the chart (1) or not (0). Not all charts show legends.', 'mycred'),
                            value: legend,
                            onChange: setLegend
                        }),
                        el(TextControl, {
                            label: __('Width', 'mycred'),
                            help: __('The chart canvas width. By default the chart will render full with of the container where the shortcode is used. Can be either a pixel value (without px) or a percentage value (with %).', 'mycred'),
                            value: width,
                            onChange: setWidth
                        }),
                        el(TextControl, {
                            label: __('Height', 'mycred'),
                            help: __('The chart canvas height. Can be either a pixel value (without px) or a percentage value (with %).', 'mycred'),
                            value: height,
                            onChange: setHeight
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