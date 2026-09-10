<?php
/**
 * Render callback for Code Highlight Block
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */

if ( empty( $attributes['code'] ) ) {
	return;
}

$code = isset( $attributes['code'] ) ? $attributes['code'] : '';
$language = isset( $attributes['language'] ) ? sanitize_text_field( $attributes['language'] ) : 'javascript';
$show_line_numbers = isset( $attributes['showLineNumbers'] ) ? (bool) $attributes['showLineNumbers'] : false;

// Escape the code for display
$escaped_code = esc_html( $code );

// Split code into lines for line numbers
$lines = explode( "\n", $escaped_code );
$line_count = count( $lines );

?>
<div class="wp-block-child-code-highlight">
	<div class="code-highlight-container">
		<div class="code-highlight-header">
			<span class="code-highlight-language">{{ $language }}</span>
		</div>
		<div class="code-highlight-content">
			<?php if ( $show_line_numbers ) : ?>
				<div class="code-highlight-with-numbers">
					<div class="code-highlight-numbers">
						<?php
						for ( $i = 1; $i <= $line_count; $i++ ) {
							echo esc_html( $i ) . "\n";
						}
						?>
					</div>
					<div class="code-highlight-code-wrapper">
						<code class="language-<?php echo esc_attr( $language ); ?>"><?php echo $escaped_code; ?></code>
					</div>
				</div>
			<?php else : ?>
				<code class="language-<?php echo esc_attr( $language ); ?>"><?php echo $escaped_code; ?></code>
			<?php endif; ?>
		</div>
	</div>
</div>
<script src="https://davatron5000.github.io/microlighter/microlighter.js"></script>
