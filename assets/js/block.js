const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.editor;
const { PanelBody, SelectControl, ToggleControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('lunio/tax-calculator', {
    title: __('Lunio Tax Calculator', 'lunio-wp'),
    icon: 'calculator',
    category: 'widgets',
    attributes: {
        type: {
            type: 'string',
            default: 'standard',
        },
        province: {
            type: 'string',
            default: '',
        },
        layout: {
            type: 'string',
            default: 'full',
        },
        showBreakdown: {
            type: 'boolean',
            default: true,
        },
        poweredBy: {
            type: 'boolean',
            default: true,
        },
    },
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const provinces = [
            { label: __('Select Province', 'lunio-wp'), value: '' },
            { label: 'Alberta', value: 'AB' },
            { label: 'British Columbia', value: 'BC' },
            { label: 'Manitoba', value: 'MB' },
            { label: 'New Brunswick', value: 'NB' },
            { label: 'Newfoundland and Labrador', value: 'NL' },
            { label: 'Northwest Territories', value: 'NT' },
            { label: 'Nova Scotia', value: 'NS' },
            { label: 'Nunavut', value: 'NU' },
            { label: 'Ontario', value: 'ON' },
            { label: 'Prince Edward Island', value: 'PE' },
            { label: 'Quebec', value: 'QC' },
            { label: 'Saskatchewan', value: 'SK' },
            { label: 'Yukon', value: 'YT' },
        ];

        return [
            wp.element.createElement('div', {
                className: 'lunio-block-preview',
                style: {
                    padding: '20px',
                    border: '2px dashed #ccc',
                    textAlign: 'center',
                    background: '#f9f9f9'
                }
            }, 
            wp.element.createElement('div', { style: { fontSize: '24px', marginBottom: '10px' } }, '🧮'),
            wp.element.createElement('div', { style: { fontWeight: 'bold' } }, attributes.type === 'reverse' ? 'Reverse Tax Calculator' : 'Tax Calculator'),
            wp.element.createElement('div', { style: { fontSize: '14px', color: '#666' } }, 'Province: ' + (attributes.province || 'None')),
            wp.element.createElement('div', { style: { fontSize: '14px', color: '#666' } }, 'Layout: ' + attributes.layout)
            ),
            wp.element.createElement(InspectorControls, {},
                wp.element.createElement(PanelBody, { title: __('Calculator Settings', 'lunio-wp') },
                    wp.element.createElement(SelectControl, {
                        label: __('Calculator Type', 'lunio-wp'),
                        value: attributes.type,
                        options: [
                            { label: __('Standard', 'lunio-wp'), value: 'standard' },
                            { label: __('Reverse', 'lunio-wp'), value: 'reverse' },
                        ],
                        onChange: (value) => setAttributes({ type: value }),
                    }),
                    wp.element.createElement(SelectControl, {
                        label: __('Default Province', 'lunio-wp'),
                        value: attributes.province,
                        options: provinces,
                        onChange: (value) => setAttributes({ province: value }),
                    }),
                    wp.element.createElement(SelectControl, {
                        label: __('Layout', 'lunio-wp'),
                        value: attributes.layout,
                        options: [
                            { label: __('Full', 'lunio-wp'), value: 'full' },
                            { label: __('Compact', 'lunio-wp'), value: 'compact' },
                        ],
                        onChange: (value) => setAttributes({ layout: value }),
                    }),
                    wp.element.createElement(ToggleControl, {
                        label: __('Show Tax Breakdown', 'lunio-wp'),
                        checked: attributes.showBreakdown,
                        onChange: (value) => setAttributes({ showBreakdown: value }),
                    }),
                    wp.element.createElement(ToggleControl, {
                        label: __('Show Powered by Lunio', 'lunio-wp'),
                        checked: attributes.poweredBy,
                        onChange: (value) => setAttributes({ poweredBy: value }),
                    }),
                    wp.element.createElement('p', { style: { fontSize: '12px', color: '#666' } }, __('Alternatively, use the shortcode directly.', 'lunio-wp'))
                )
            )
        ];
    },
    save: function() {
        return null; // Dynamic block
    },
});