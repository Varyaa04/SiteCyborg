<?php

namespace Kubio\Blocks;

use Kubio\Core\Blocks\BlockBase;
use Kubio\Core\Registry;
use Kubio\Flags;

class CopyrightBlock extends BlockBase {

	const CONTAINER = 'container';
	const OUTER     = 'outer';

	public function mapPropsToElements() {

		$template = $this->getTemplateValue();
		return array(
			self::OUTER => array( 'innerHTML' => $this->kubio_copyright_shortcode( array(), $template ) ),
		);
	}

	function getTemplateValue() {
		$template = $this->getBlockInnerHtml();

		$is_free = !kubio_is_pro();

		if($is_free && Flags::getSetting( 'activatedOnStage2', false )) {
			$template = __('&copy; {year} {site-name}. Created for free using WordPress and <a target="_blank" href="https://kubiobuilder.com" rel="noreferrer">Kubio</a>','kubio');
		}

		return $template;
	}

	function kubio_copyright_shortcode( $atts, $content ) {
		//TODO the href will need changing to the kubio website when will have one
		$default = '&copy; {year} {site-name}. Built using WordPress and <a target="_blank" href="https://kubiobuilder.com" rel="noreferrer">Kubio</a>';
		$msg     = $content ? $content : $default;
		$msg     = str_replace( '{year}', date( 'Y' ), $msg );
		$msg     = str_replace( '{site-name}', get_bloginfo( 'name' ), $msg );
		$msg     = sprintf( '<p>%s</p>', $msg );
		return html_entity_decode( $msg );
	}
}

Registry::registerBlock(
	__DIR__,
	CopyrightBlock::class
);
