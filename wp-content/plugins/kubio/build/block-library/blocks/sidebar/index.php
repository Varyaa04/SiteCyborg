<?php

namespace Kubio\Blocks;

use Kubio\Core\Blocks\TemplatePartBlockBase;
use Kubio\Core\Registry;
use Kubio\Core\StyleManager\DynamicStyles;

class SidebarTemplatePart extends TemplatePartBlockBase {

}

Registry::registerBlock( __DIR__, SidebarTemplatePart::class );
