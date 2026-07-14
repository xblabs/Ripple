<?php

/**
 * Ripple keeps its original hand-written style: tab indentation and a single
 * space inside parentheses (e.g. `dispatch( 'test' )`). This config enforces
 * that house style rather than a mass reformat to PSR-12, so v2 code reads like
 * the existing code and `git blame` stays meaningful.
 */

$finder = PhpCsFixer\Finder::create()
	->in( [ __DIR__ . '/src', __DIR__ . '/tests' ] );

return ( new PhpCsFixer\Config() )
	->setRiskyAllowed( false )
	->setIndent( "\t" )
	->setRules( [
		'array_syntax'              => [ 'syntax' => 'short' ],
		'spaces_inside_parentheses' => [ 'space' => 'single' ],
		'ordered_imports'           => [ 'sort_algorithm' => 'alpha' ],
		'no_unused_imports'         => true,
		'single_quote'              => true,
		'no_trailing_whitespace'    => true,
		'blank_line_after_opening_tag' => true,
	] )
	->setFinder( $finder );
