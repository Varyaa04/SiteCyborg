<?php

namespace Kubio\Blocks;

use Kubio\AssetsDependencyInjector;
use Kubio\Core\Blocks\BlockContainerBase;
use Kubio\Core\Registry;
use Kubio\Core\Utils;

class ImageBlock extends BlockContainerBase {

	const OUTER           = 'outer';
	const IMAGE           = 'image';
	const OVERLAY         = 'overlay';
	const CAPTION         = 'caption';
	const FRAME_IMAGE     = 'frameImage';
	const FRAME_CONTAINER = 'frameContainer';


	public function computed() {
		$show_caption     = $this->getAttribute( 'captionEnabled', false );
		$show_overlay     = $this->getStyle( 'background.overlay.enabled', false, array( 'styledComponent' => 'overlay' ) );
		$show_frame_image = $this->getPropByMedia( 'frame.enabled', false );
		return array(
			'showCaption'    => $show_caption,
			'showOverlay'    => $show_overlay,
			'showFrameImage' => in_array( true, $show_frame_image ),
		);
	}

	public function mapPropsToElements() {
		$frame_hide_classes = Utils::mapHideClassesByMedia(
			$this->getPropByMedia( 'frame.enabled' ),
			true
		);

		$map = array();

		$size_slug = $this->getAttribute( 'sizeSlug' );
		$align     = $this->getAttribute( 'align', 'center' );

		//the wp image class is used to add the src set by WordPress
		$image_classes = array( 'wp-image-' . $this->getAttribute( 'id' ) );
		$default_img   = Utils::getDefaultAssetsURL( 'default-image.png' );

		$src           = $this->getAttribute( 'url' );
		$src           = $src ? $src : $default_img;
		$outer_classes = array( "size-$size_slug", $this->getAlignClasses( $align ) );

		$map[ self::OUTER ]       = array( 'className' => $outer_classes );
		$map[ self::IMAGE ]       = array(
			'alt'       => $this->getAttribute( 'alt', '' ),
			'src'       => $src,
			'className' => $image_classes,
		);
		$map[ self::FRAME_IMAGE ] = array(
			'className' => array_merge(
				$frame_hide_classes
			),
		);
		$map[ self::CAPTION ]     = array( 'innerHTML' => $this->getBlockInnerHtml() );

		$type = $this->getAttribute( 'link.typeOpenLink' );

		if ( $type === 'lightbox' ) {
			AssetsDependencyInjector::injectKubioFrontendStyleDependencies( 'fancybox' );
			AssetsDependencyInjector::injectKubioScriptDependencies( 'fancybox' );

		}

		return $map;
	}

	public function getAlignClasses( $align ) {
		return sprintf( 'align-items-%s', $align ? $align : 'center' );
	}
}
Registry::registerBlock( __DIR__, ImageBlock::class );
