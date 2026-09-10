import { __, _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';

const LANGUAGE_OPTIONS = [
  { label: 'JavaScript', value: 'javascript' },
  { label: 'Python', value: 'python' },
  { label: 'PHP', value: 'php' },
  { label: 'HTML', value: 'html' },
  { label: 'CSS', value: 'css' },
  { label: 'Java', value: 'java' },
  { label: 'C++', value: 'cpp' },
  { label: 'C#', value: 'csharp' },
  { label: 'Ruby', value: 'ruby' },
  { label: 'Go', value: 'go' },
  { label: 'Rust', value: 'rust' },
  { label: 'SQL', value: 'sql' },
  { label: 'XML', value: 'xml' },
  { label: 'JSON', value: 'json' },
  { label: 'Markdown', value: 'markdown' },
  { label: 'Plain Text', value: 'text' },
];

function Edit( { attributes, setAttributes } ) {
  const blockProps = useBlockProps();
  const { code, language, showLineNumbers } = attributes;

  return (
    <div { ...blockProps }>
      <InspectorControls>
        <PanelBody
          title={ __( 'Code Settings', 'child' ) }
          initialOpen={ true }
        >
          <SelectControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={ __( 'Language', 'child' ) }
            value={ language }
            options={ LANGUAGE_OPTIONS }
            onChange={ ( value ) => setAttributes( { language: value } ) }
          />
          <ToggleControl
            label={ __( 'Show Line Numbers', 'child' ) }
            checked={ showLineNumbers }
            onChange={ ( value ) => setAttributes( { showLineNumbers: value } ) }
          />
        </PanelBody>
      </InspectorControls>

      <div className="code-highlight-editor">
        <div className="code-highlight-language">{ language }</div>
        <textarea
          className="code-highlight-textarea"
          value={ code }
          onChange={ ( e ) => setAttributes( { code: e.target.value } ) }
          placeholder={ __( 'Enter your code here...', 'child' ) }
        />
      </div>
    </div>
  );
}

registerBlockType( metadata.name, {
  edit: Edit,
  save: () => null,
} );
