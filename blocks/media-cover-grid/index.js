import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    CheckboxControl,
    RangeControl,
    SelectControl,
    ToggleControl
} from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

const MEDIA_TYPE_OPTIONS = [
    { value: 'book', label: __('Bücher', 'child') },
    { value: 'movie', label: __('Filme', 'child') },
    { value: 'tv', label: __('Serien', 'child') },
    { value: 'game', label: __('Videospiele', 'child') },
    { value: 'music', label: __('Musik', 'child') }
];

const PREVIEW_ITEMS = [
    {
        type: 'book',
        typeLabel: __('Buch', 'child'),
        title: __('Beispielbuch', 'child'),
        meta: __('Autor:in', 'child')
    },
    {
        type: 'movie',
        typeLabel: __('Film', 'child'),
        title: __('Beispielfilm', 'child'),
        meta: '2025'
    },
    {
        type: 'tv',
        typeLabel: __('Serie', 'child'),
        title: __('Beispielserie', 'child'),
        meta: '2024'
    },
    {
        type: 'game',
        typeLabel: __('Videospiel', 'child'),
        title: __('Beispielspiel', 'child'),
        meta: __('PC, Switch', 'child')
    },
    {
        type: 'music',
        typeLabel: __('Musik', 'child'),
        title: __('Beispielsong', 'child'),
        meta: __('Künstler:in', 'child')
    }
];

const updateMediaTypes = (mediaTypes, value, checked) => {
    const normalizedTypes = Array.isArray(mediaTypes) ? mediaTypes : [];

    if (checked) {
        return [...new Set([...normalizedTypes, value])];
    }

    return normalizedTypes.filter((type) => type !== value);
};

function Edit({ attributes, setAttributes }) {
    const {
        mediaTypes = metadata.attributes.mediaTypes.default,
        maxItems = metadata.attributes.maxItems.default,
        linkTo = metadata.attributes.linkTo.default,
        sortOrder = metadata.attributes.sortOrder.default,
        showTitle = true,
        showMeta = true,
        showType = true,
        allowDuplicates = false
    } = attributes;

    const blockProps = useBlockProps({ className: 'child-media-cover-grid-block' });
    const enabledTypes = Array.isArray(mediaTypes) ? mediaTypes : [];
    const previewItems = PREVIEW_ITEMS.filter((item) => enabledTypes.includes(item.type));

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Medien filtern', 'child')} initialOpen={true}>
                    {MEDIA_TYPE_OPTIONS.map((option) => (
                        <CheckboxControl
                            key={option.value}
                            label={option.label}
                            checked={enabledTypes.includes(option.value)}
                            onChange={(checked) =>
                                setAttributes({
                                    mediaTypes: updateMediaTypes(enabledTypes, option.value, checked)
                                })
                            }
                        />
                    ))}
                    <RangeControl
                        label={__('Maximale Anzahl', 'child')}
                        value={maxItems}
                        onChange={(value) => setAttributes({ maxItems: value })}
                        min={1}
                        max={120}
                    />
                </PanelBody>

                <PanelBody title={__('Darstellung', 'child')} initialOpen={true}>
                    <SelectControl
                        label={__('Sortierung', 'child')}
                        value={sortOrder}
                        options={[
                            { label: __('Neueste Beiträge zuerst', 'child'), value: 'newest' },
                            { label: __('Älteste Beiträge zuerst', 'child'), value: 'oldest' },
                            { label: __('Titel A–Z', 'child'), value: 'title' }
                        ]}
                        onChange={(value) => setAttributes({ sortOrder: value })}
                    />
                    <SelectControl
                        label={__('Links öffnen', 'child')}
                        value={linkTo}
                        options={[
                            { label: __('Zum Beitrag', 'child'), value: 'post' },
                            { label: __('Zum externen Medien-Link', 'child'), value: 'external' },
                            { label: __('Kein Link', 'child'), value: 'none' }
                        ]}
                        onChange={(value) => setAttributes({ linkTo: value })}
                    />
                    <ToggleControl
                        label={__('Titel anzeigen', 'child')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />
                    <ToggleControl
                        label={__('Meta anzeigen', 'child')}
                        checked={showMeta}
                        onChange={(value) => setAttributes({ showMeta: value })}
                    />
                    <ToggleControl
                        label={__('Typ anzeigen', 'child')}
                        checked={showType}
                        onChange={(value) => setAttributes({ showType: value })}
                    />
                    <ToggleControl
                        label={__('Doppelte Erwähnungen einzeln anzeigen', 'child')}
                        help={__('Ausgeschaltet: dasselbe Medium erscheint nur einmal, auch wenn es in mehreren Beiträgen erwähnt wird.', 'child')}
                        checked={allowDuplicates}
                        onChange={(value) => setAttributes({ allowDuplicates: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div className="child-media-cover-grid__editor-note">
                {__('Dieses Grid wird auf der Website automatisch aus veröffentlichten Beiträgen befüllt.', 'child')}
            </div>
            <div className="child-media-cover-grid">
                {previewItems.length ? (
                    previewItems.map((item) => (
                        <div key={item.type} className="child-media-cover-grid__item">
                            <div className={`child-media-cover-grid__cover child-media-cover-grid__cover--${item.type}`} aria-hidden="true">
                                <span>{item.typeLabel.charAt(0)}</span>
                            </div>
                            {(showType || showTitle || showMeta) && (
                                <div className="child-media-cover-grid__content">
                                    {showType && <span className="child-media-cover-grid__type">{item.typeLabel}</span>}
                                    {showTitle && <h3 className="child-media-cover-grid__title">{item.title}</h3>}
                                    {showMeta && <p className="child-media-cover-grid__meta">{item.meta}</p>}
                                </div>
                            )}
                        </div>
                    ))
                ) : (
                    <p className="child-media-cover-grid__empty">
                        {__('Wähle mindestens einen Medientyp aus.', 'child')}
                    </p>
                )}
            </div>
        </div>
    );
}

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null
});
