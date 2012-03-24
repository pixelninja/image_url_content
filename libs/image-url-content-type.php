<?php

	/**
	 * @package content_field
	 */
	class ImageURLContentType implements ContentType {
		public function appendSettingsHeaders(HTMLPage $page) {

		}

		public function appendSettingsInterface(XMLElement $wrapper, $field_name, StdClass $settings = null, MessageStack $errors) {
			$legend = new XMLElement('legend');
			$legend->setValue(__('Image URL'));
			$wrapper->appendChild($legend);

			// Default size
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$values = array(
				array('auto', false, __('Auto Resize')),
				array('small', false, __('Small Box')),
				array('medium', false, __('Medium Box')),
				array('large', false, __('Large Box')),
				array('huge', false, __('Huge Box'))
			);

			foreach ($values as &$value) {
				$value[1] = $value[0] == $settings->{'text-size'};
			}

			$label = Widget::Label(__('Default Size'));
			$label->appendChild(Widget::Select(
				"{$field_name}[text-size]", $values
			));

			$group->appendChild($label);

			// Text formatter:
			$field = new Field();
			$group->appendChild($field->buildFormatterSelect(
				isset($settings->{'text-formatter'})
					? $settings->{'text-formatter'}
					: null,
				"{$field_name}[text-formatter]",
				'Text Formatter'
			));
			$wrapper->appendChild($group);

			// Styles:
			$label = Widget::Label(__('Available Styles'));
			$input = Widget::Input(
				"{$field_name}[available-styles]",
				$settings->{'available-styles'}
			);
			$label->appendChild($input);
			$wrapper->appendChild($label);

			$list = new XMLElement('ul');
			$list->addClass('tags');

			foreach (explode(',', $settings->{'available-styles'}) as $tag) {
				$tag = trim($tag);

				if ($tag == '') continue;

				$list->appendChild(new XMLElement('li', $tag));
			}

			$wrapper->appendChild($list);

			// Enable this content type:
			$input = Widget::Input("{$field_name}[enabled]", 'yes', 'checkbox');

			if ($settings->{'enabled'} == 'yes') {
				$input->setAttribute('checked', 'checked');
			}

			$wrapper->appendChild(Widget::Label(
				__('%s Enable the Image URL content type', array(
					$input->generate()
				))
			));
		}

		public function sanitizeSettings($settings) {
			if (is_array($settings)) {
				$settings = (object)$settings;
			}

			else if (is_object($settings) === false) {
				$settings = new StdClass();
			}

			if (isset($settings->{'enabled'}) === false) {
				$settings->{'enabled'} = 'no';
			}

			if (isset($settings->{'text-size'}) === false) {
				$settings->{'text-size'} = 'medium';
			}

			if (isset($settings->{'text-formatter'}) === false) {
				$settings->{'text-formatter'} = 'none';
			}

			if (isset($settings->{'available-styles'}) === false) {
				$settings->{'available-styles'} = 'Default';
			}

			return $settings;
		}

		public function validateSettings(StdClass $data, MessageStack $errors) {
			return true;
		}

		public function appendPublishHeaders(HTMLPage $page) {

		}

		public function appendPublishInterface(XMLElement $wrapper, $field_name, StdClass $settings, StdClass $data, MessageStack $errors, $entry_id = null) {
			$header = new XMLElement('header');
			$header->addClass('main');
			$header->appendChild(
				new XMLElement('strong', __('Image URL'))
			);
			$wrapper->appendChild($header);

			$content = new XMLElement('div');
			$wrapper->appendChild($content);

			// URL:
			$label = Widget::Label(__('Image URL'));
			$input = Widget::Input(
				"{$field_name}[data][url]", (
					isset($data->url)
						? $data->url
						: null
				)
			);
			$input->setAttribute('placeholder', 'http://.../image.png');
			$label->appendChild($input);

			if (isset($errors->url)) {
				$label = Widget::wrapFormElementWithError($label, $errors->url);
			}

			$content->appendChild($label);

			// Alt text:
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			$content->appendChild($group);

			$label = Widget::Label(__('Alternative text'));
			$label->appendChild(new XMLElement('i', __('Optional')));
			$group->appendChild($label);

			$text = Widget::Textarea(
				"{$field_name}[data][alt-text]", 1, 20, (
					isset($data->{'alt-text'})
						? $data->{'alt-text'}
						: null
				)
			);
			$text->addClass('size-auto');

			if ($settings->{'text-formatter'} != 'none') {
				$text->addClass($settings->{'text-formatter'});
			}

			$label->appendChild($text);

			// Image style:
			$values = array();

			foreach (explode(',', $settings->{'available-styles'}) as $style) {
				$style = trim($style);

				if ($style == '') continue;

				$values[] = array(
					$style, $style == $data->{'style'}, $style
				);
			}

			$label = Widget::Label('Image style');
			$label->appendChild(Widget::Select(
				"{$field_name}[data][style]", $values
			));

			$group->appendChild($label);
		}

		public function processData(StdClass $settings, StdClass $data, $entry_id = null) {
			if ($settings->{'text-formatter'} != 'none') {
				$tfm = new TextformatterManager();
				$formatter = $tfm->create($settings->{'text-formatter'});
				$formatted = $formatter->run($data->{'alt-text'});
				$formatted = preg_replace('/&(?![a-z]{0,4}\w{2,3};|#[x0-9a-f]{2,6};)/i', '&amp;', $formatted);
			}

			else {
				$formatted = General::sanitize($data->{'alt-text'});
			}

			return (object)array(
				'handle'			=> null,
				'value'				=> $data->{'alt-text'},
				'value_formatted'	=> $formatted,
				'url'				=> $data->{'url'},
				'alt-text'			=> $data->{'alt-text'},
				'style'				=> $data->{'style'}
			);
		}

		public function sanitizeData(StdClass $settings, $data) {
			$result = (object)array(
				'url'		=> null,
				'alt-text'	=> null,
				'style'		=> null
			);

			if (is_object($data) && isset($data->url)) {
				return $data;
			}

			if (is_array($data) && isset($data['url'])) {
				return (object)$data;
			}

			return $result;
		}

		public function validateData(StdClass $settings, StdClass $data, MessageStack $errors, $entry_id = null) {
			// Check that either http or http are used:
			if (!preg_match('%^https?://%', $data->url)) {
				$errors->append('url', __('Invalid URL, please check that you entered it correctly.'));

				return false;
			}

			return true;
		}

		public function appendFormattedElement(XMLElement $wrapper, StdClass $settings, StdClass $data, $entry_id = null) {
			$url = new XMLElement('url');
			$url->setValue($data->url);
			$wrapper->appendChild($url);

			$text = new XMLElement('alt-text');
			$text->setValue($data->value_formatted);
			$wrapper->appendChild($text);

			$style = new XMLElement('style');
			$style->setValue($data->style);
			$wrapper->appendChild($style);
		}
	}