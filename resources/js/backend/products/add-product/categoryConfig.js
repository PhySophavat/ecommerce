export const emptyVariantValues = () => ({
    size: '',
    color: '',
    material: '',
    volume: '',
    skinType: '',
    storage: '',
    ram: '',
    weight: '',
});

export const categoryConfig = {
    fashion: {
        label: 'Fashion',
        fields: [
            {
                key: 'size',
                label: 'Size',
                placeholder: 'Select size',
                options: ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            },
            {
                key: 'color',
                label: 'Color',
                placeholder: 'Select color',
                options: ['Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Pink', 'Purple', 'Brown', 'Gray'],
            },
            {
                key: 'material',
                label: 'Material',
                placeholder: 'Select material',
                options: ['Cotton', 'Polyester', 'Denim', 'Leather', 'Wool', 'Silk', 'Linen'],
            },
        ],
    },
    beauty: {
        label: 'Beauty',
        fields: [
            {
                key: 'volume',
                label: 'Volume',
                placeholder: 'Select volume',
                options: ['30ml', '50ml', '100ml', '150ml', '200ml', '250ml', '500ml'],
            },
            {
                key: 'skinType',
                label: 'Skin Type',
                placeholder: 'Select skin type',
                options: ['Oily', 'Dry', 'Normal', 'Combination', 'Sensitive', 'All Skin Types'],
            },
        ],
    },
    electronic: {
        label: 'Electronic',
        fields: [
            {
                key: 'storage',
                label: 'Storage',
                placeholder: 'Select storage',
                options: ['64GB', '128GB', '256GB', '512GB', '1TB', '2TB'],
            },
            {
                key: 'ram',
                label: 'RAM',
                placeholder: 'Select RAM',
                options: ['4GB', '8GB', '12GB', '16GB', '32GB', '64GB'],
            },
        ],
    },
    sport: {
        label: 'Sport',
        fields: [
            {
                key: 'size',
                label: 'Size',
                placeholder: 'Select size',
                options: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Free Size'],
            },
            {
                key: 'weight',
                label: 'Weight',
                placeholder: 'Select weight',
                options: ['0.5kg', '1kg', '2kg', '5kg', '10kg', '15kg', '20kg'],
            },
        ],
    },
    home: {
        label: 'Home',
        fields: [
            {
                key: 'size',
                label: 'Size',
                placeholder: 'Select size',
                options: ['Small', 'Medium', 'Large', 'Extra Large'],
            },
            {
                key: 'material',
                label: 'Material',
                placeholder: 'Select material',
                options: ['Wood', 'Metal', 'Plastic', 'Glass', 'Ceramic', 'Cotton', 'Polyester'],
            },
        ],
    },
};

export function categoryFieldsForSlug(slug) {
    return categoryConfig[slug]?.fields ?? [];
}

export function variantValuesFromAttributes(attributes = []) {
    const values = emptyVariantValues();
    const attributeMap = {
        Size: 'size',
        Color: 'color',
        Material: 'material',
        Volume: 'volume',
        'Skin Type': 'skinType',
        Storage: 'storage',
        RAM: 'ram',
        Weight: 'weight',
    };

    attributes.forEach((attribute) => {
        const key = attributeMap[attribute?.name];

        if (key) {
            values[key] = attribute?.value ?? '';
        }
    });

    return values;
}

export function variantAttributesFromValues(slug, values = {}) {
    return categoryFieldsForSlug(slug)
        .map((field) => ({
            name: field.label,
            value: String(values[field.key] ?? '').trim(),
        }))
        .filter((attribute) => attribute.value !== '');
}
