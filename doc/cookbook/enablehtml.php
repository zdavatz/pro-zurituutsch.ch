<?php if (!defined('PmWiki')) exit();

	function EnableHtml($tag) {
		Markup(
			"html-$tag",
			'>{$var}',
			'/&lt;(\/?('.$tag.')(?![a-z!])(([\'"]).*?\4|.*?)*?)&gt;/ie',
			'Keep(PSS(\'<$1>\'))'
		);
	}

?>
