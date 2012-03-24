<?php

	/**
	 * @package image_url_content_type
	 */

	class Extension_Image_URL_Content_Type extends Extension {
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '*',
					'delegate'	=> 'AppendContentType',
					'callback'	=> 'appendContentType'
				)
			);
		}

		public function appendContentType(&$context) {
			require_once __DIR__ . '/libs/image-url-content-type.php';

			$context['items']->{'image-url'} = new ImageURLContentType();
		}
	}