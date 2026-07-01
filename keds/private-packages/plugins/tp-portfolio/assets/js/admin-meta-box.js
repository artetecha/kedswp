/**
 * Meta Box Field Handlers
 * Manages image uploads, video uploads, file uploads, repeater fields, and color picker
 */

'use strict';

jQuery(function ($) {
	// Cache DOM elements and variables
	var $document = $(document);
	var $body = $('body');
	var frame;
	var videoDialogInstance;

	// ========================================
	// 1. PORTFOLIO TYPE TOGGLE
	// ========================================
	function initPortfolioToggle() {
		var $typeBoxes = $('div[class*="portfolio_type_"]').hide();
		var $selectPortfolio = $('#selectPortfolio');

		if ($selectPortfolio.length) {
			$selectPortfolio.on('change', function () {
				$typeBoxes.hide();
				$('.' + $(this).val()).show();
			}).trigger('change');
		}
	}

	// ========================================
	// 2. IMAGE-VIDEO DIALOG INITIALIZATION
	// ========================================
	function initVideoDialog() {
		var $dialogElement = $('#dialog-k');
		if (!$dialogElement.length) {
			return;
		}

		var portfolio_add = false;
		var edit_li;
		var edit_data;

		// Initialize dialog instance
		videoDialogInstance = $dialogElement.dialog({
			autoOpen: false,
			height: 'auto',
			width: 'auto',
			modal: true,
			buttons: {
				'Add': function () {
					handleVideoAdd($(this));
				},
				'Close': function () {
					$(this).dialog('close');
				}
			},
			dialogClass: 'portfolio-dialog'
		});

		function handleVideoAdd($dialog) {
			var ids = $('#thim-video-data-k').val().trim();
			var type = $('#thim-video-type-k').val();
			var $uploadButton = $('.thim-video-advanced-upload-k');
			var $imageList = $uploadButton.siblings('.thim-images-video');

			if (!ids.length) {
				return;
			}

			var xids = type === 'vimeo' ? 'v.' + ids : 'y.' + ids;
			var datav = generateVideoHtml(ids, type);

			if (!portfolio_add) {
				// Edit mode - update existing item
				edit_li.empty().html(
					'<li id="item_' + xids + '">' + datav +
					'<div class="thim-image-bar">' +
					'<a title="Edit" class="thim-edit-file-k" href="#" target="_blank">Edit</a> | ' +
					'<a title="Delete" class="thim-delete-file-k" href="#" data-attachment_id="' + xids + '">×</a>' +
					'</div></li>'
				);
			} else {
				// Add mode - append new item
				$imageList.removeClass('hidden').append(
					'<li id="item_' + xids + '">' + datav +
					'<div class="thim-image-bar">' +
					'<a title="Edit" class="thim-edit-file-k" href="#" target="_blank">Edit</a> | ' +
					'<a title="Delete" class="thim-delete-file-k" href="#" data-attachment_id="' + xids + '">×</a>' +
					'</div></li>'
				);
			}

			$('#thim-video-data-k').val('');
			$dialog.dialog('close');
		}

		function generateVideoHtml(ids, type) {
			if (type === 'vimeo') {
				return '<iframe src="https://player.vimeo.com/video/' + ids + '?title=0&byline=0&portrait=0&color=ffffff" width="150" height="150" frameborder="0"></iframe>';
			}
			return '<iframe title="YouTube video player" class="youtube-player" type="text/html" width="150" height="150" src="https://www.youtube.com/embed/' + ids + '" frameborder="0"></iframe>';
		}

		// Edit video handler
		$body.on('click', '.thim-edit-file-k', function (e) {
			e.preventDefault();
			edit_li = $(this).closest('li');
			var itemId = edit_li.attr('id');
			edit_data = itemId.replace('item_', '');

			var videoId = edit_data.substring(2); // Remove v. or y. prefix
			var videoType = edit_data.substring(0, 2) === 'v.' ? 'vimeo' : 'youtube';

			$('#thim-video-type-k').val(videoType);
			$('#thim-video-data-k').val(videoId);
			$('.portfolio-dialog .ui-button-text:contains(Add)').text('Save');

			portfolio_add = false;
			videoDialogInstance.dialog('open');
		});

		// Add video handler
		$body.on('click', '.thim-video-advanced-upload-k', function (e) {
			e.preventDefault();
			$('.portfolio-dialog .ui-button-text:contains(Save)').text('Add');
			$('#thim-video-type-k').val('youtube');
			$('#thim-video-data-k').val('');

			portfolio_add = true;
			videoDialogInstance.dialog('open');
		});
	}
	// ========================================
	// 3. IMAGE-VIDEO FIELD HANDLERS
	// ========================================
	function initImageVideoField() {
		// Upload images to image-video field
		$body.on('click', '.thim-image-video-advanced-upload', function (e) {
			e.preventDefault();

			var $uploadButton = $(this);
			var $imageList = $uploadButton.siblings('.thim-images-video');
			var maxFileUploads = parseInt($imageList.data('max_file_uploads')) || 0;

			if (!frame) {
				frame = wp.media({
					className: 'media-frame thim-media-frame',
					multiple: true,
					title: 'Select Image',
					library: {
						type: 'image'
					}
				});
			}

			frame.off('select');
			frame.on('select', function () {
				handleImageVideoSelection($uploadButton, $imageList, maxFileUploads);
			});

			frame.open();
		});

		function handleImageVideoSelection($uploadButton, $imageList, maxFileUploads) {
			var selection = frame.state().get('selection').toJSON();
			var uploaded = $imageList.children().length;

			if (maxFileUploads > 0 && (uploaded + selection.length) > maxFileUploads) {
				if (uploaded < maxFileUploads) {
					selection = selection.slice(0, maxFileUploads - uploaded);
				}
				alert('You may only upload maximum ' + maxFileUploads + ' files');
			}

			var ids = _.pluck(selection, 'id');

			if (ids.length > 0) {
				var data = {
					action: 'thim_attach_image_video',
					post_id: $('#post_ID').val(),
					field_id: $imageList.data('field_id'),
					attachment_ids: ids,
					_ajax_nonce: $uploadButton.data('attach_media_nonce')
				};

				$.post(ajaxurl, data, function (r) {
					if (r.success) {
						$imageList.append(r.data);
					}
				}, 'json');
			}
		}

		// Reorder image-video
		$('.thim-images-video').each(function () {
			var $this = $(this);
			var data = {
				action: 'thim_reorder_image_video',
				_ajax_nonce: $this.data('reorder_nonce'),
				post_id: $('#post_ID').val(),
				field_id: $this.data('field_id')
			};

			$this.sortable({
				placeholder: 'ui-state-highlight',
				items: 'li',
				update: function () {
					data.order = $this.sortable('serialize');
					$.post(ajaxurl, data);
				}
			});
		});

		// Delete image-video
		$('.thim-image-video-uploaded').on('click', '.thim-delete-file-k', function () {
			var $this = $(this);
			var $parent = $this.closest('li');
			var $container = $this.closest('.thim-image-video-uploaded');

			var data = {
				action: 'thim_delete_image_video',
				_ajax_nonce: $container.data('delete_nonce'),
				post_id: $('#post_ID').val(),
				field_id: $container.data('field_id'),
				attachment_id: $this.data('attachment_id'),
				force_delete: $container.data('force_delete')
			};

			$.post(ajaxurl, data, function (r) {
				if (!r.success) {
					alert(r.data);
					return;
				}

				$parent.addClass('removed');

				// Handle removal with transition
				if (!('ontransitionend' in window) && ('onwebkittransitionend' in window)) {
					$parent.remove();
					$container.trigger('update.thimFile');
				}

				$('.thim-image-video-uploaded').on('transitionend webkitTransitionEnd otransitionend', 'li.removed', function () {
					$(this).remove();
					$container.trigger('update.thimFile');
				});
			}, 'json');

			return false;
		});
	}

	// ========================================
	// 4. IMAGE FIELD HANDLERS
	// ========================================
	function initImageField() {
		// Upload images
		$body.on('click', '.thim-image-advanced-upload', function (e) {
			e.preventDefault();

			var $uploadButton = $(this);
			var $imageList = $uploadButton.siblings('.thim-images');
			var maxFileUploads = parseInt($imageList.data('max_file_uploads')) || 0;

			if (!frame) {
				frame = wp.media({
					className: 'media-frame thim-media-frame',
					multiple: true,
					title: 'Select Image',
					library: {
						type: 'image'
					}
				});
			}

			frame.off('select');
			frame.on('select', function () {
				handleImageSelection($uploadButton, $imageList, maxFileUploads);
			});

			frame.open();
		});

		function handleImageSelection($uploadButton, $imageList, maxFileUploads) {
			var selection = frame.state().get('selection').toJSON();
			var uploaded = $imageList.children().length;

			if (maxFileUploads > 0 && (uploaded + selection.length) > maxFileUploads) {
				if (uploaded < maxFileUploads) {
					selection = selection.slice(0, maxFileUploads - uploaded);
				}
				alert('You may only upload maximum ' + maxFileUploads + ' files');
			}

			// Filter out duplicates
			selection = _.filter(selection, function (attachment) {
				return $imageList.children('li#item_' + attachment.id).length === 0;
			});

			var ids = _.pluck(selection, 'id');

			if (ids.length > 0) {
				var data = {
					action: 'thim_attach_media',
					post_id: $('#post_ID').val(),
					field_id: $imageList.data('field_id'),
					attachment_ids: ids,
					_ajax_nonce: $uploadButton.data('attach_media_nonce')
				};

				$.post(ajaxurl, data, function (r) {
					if (r.success) {
						$imageList.append(r.data);
					}
				}, 'json');
			}
		}

		// Reorder images
		$('.thim-images').each(function () {
			var $this = $(this);
			var data = {
				action: 'thim_reorder_images',
				_ajax_nonce: $this.data('reorder_nonce'),
				post_id: $('#post_ID').val(),
				field_id: $this.data('field_id')
			};

			$this.sortable({
				placeholder: 'ui-state-highlight',
				items: 'li',
				update: function () {
					data.order = $this.sortable('serialize');
					$.post(ajaxurl, data);
				}
			});
		});

		// Delete image
		$('.thim-images').on('click', '.thim-delete-file', function () {
			var $this = $(this);
			var $parent = $this.closest('li');
			var $container = $this.closest('.thim-images');

			var data = {
				action: 'thim_delete_file',
				_ajax_nonce: $container.data('delete_nonce'),
				post_id: $('#post_ID').val(),
				field_id: $container.data('field_id'),
				attachment_id: $this.data('attachment_id'),
				force_delete: $container.data('force_delete')
			};

			$.post(ajaxurl, data, function (r) {
				if (!r.success) {
					alert(r.data);
					return;
				}

				$parent.addClass('removed');

				// Handle removal with transition
				if (!('ontransitionend' in window) && ('onwebkittransitionend' in window)) {
					$parent.remove();
					$container.trigger('update.rwmbFile');
				}

				$('.thim-uploaded').on('transitionend webkitTransitionEnd otransitionend', 'li.removed', function () {
					$(this).remove();
					$container.trigger('update.rwmbFile');
				});
			}, 'json');

			return false;
		});
	}

	// ========================================
	// 5. FILE UPLOAD HANDLERS
	// ========================================
	function initFileUpload() {
		var file_frame;

		// Open file uploader
		$body.on('click', '[id$="_button"].button', function (e) {
			if (!$(this).hasClass('portfolio_file_upload_button') && !$(this).closest('.file-preview').length) {
				return;
			}

			e.preventDefault();

			var $button = $(this);
			var fieldId = $button.attr('id').replace(/_button$/, '');

			if (file_frame) {
				file_frame.open();
				return;
			}

			file_frame = wp.media.frames.file_frame = wp.media({
				title: 'Select a File',
				button: {
					text: 'Use this file',
				},
				multiple: false
			});

			file_frame.on('select', function () {
				var attachment = file_frame.state().get('selection').first().toJSON();

				if (attachment.mime === 'application/pdf') {
					$('#' + fieldId + '_name').val(attachment.filename);
					$('#' + fieldId + '_url').val(attachment.url);
				} else {
					alert('Please select a PDF file.');
				}
			});

			file_frame.open();
		});

		// Delete uploaded file
		$body.on('click', '.portfolio_file_delete_button', function () {
			var $preview = $(this).closest('.file-preview');
			$preview.find('[id$="_name"]').val('');
			$preview.find('[id$="_url"]').val('');
			$(this).hide();
			$preview.find('a[target="_blank"]').closest('p').hide();
		});
	}

	// ========================================
	// 6. REPEATER FIELD HANDLERS
	// ========================================
	function initRepeater() {
		// Add repeater item
		$document.on('click', '[id^="add-"]', function () {
			var fieldId = $(this).attr('id').replace(/^add-/, '');
			var $wrapper = $('#portfolio-repeater-wrapper, #' + fieldId + '-wrapper').first();

			if (!$wrapper.length) {
				$wrapper = $(this).closest('.thim-field').find('[id$="-wrapper"]').first();
			}

			if (!$wrapper.length) {
				return;
			}

			var index = $wrapper.children('.portfolio-repeater-item').length;
			var newItem = createRepeaterItem(fieldId, index);
			$wrapper.append(newItem);
		});

		function createRepeaterItem(fieldId, index) {
			return '<div class="portfolio-repeater-item">' +
				'<label>Item ' + (index + 1) + ':</label>' +
				'<input type="text" name="' + fieldId + '[' + index + '][title]" placeholder="Title">' +
				'<input type="text" name="' + fieldId + '[' + index + '][description]" placeholder="Description">' +
				'<button type="button" class="remove-repeater-item button">Delete</button>' +
				'</div>';
		}

		// Remove repeater item
		$document.on('click', '.remove-repeater-item', function () {
			$(this).closest('.portfolio-repeater-item').remove();
			reindexRepeaterItems();
		});

		function reindexRepeaterItems() {
			$('.portfolio-repeater-item').each(function (index) {
				var $item = $(this);
				$item.find('label').text('Item ' + (index + 1) + ':');

				$item.find('input').each(function () {
					var $input = $(this);
					var name = $input.attr('name');
					var matches = name.match(/^([^\[]+)\[\d+\]/);

					if (matches) {
						var fieldId = matches[1];
						var type = name.substring(name.lastIndexOf('['));
						$input.attr('name', fieldId + '[' + index + ']' + type);
					}
				});
			});
		}
	}

	// ========================================
	// 7. COLOR PICKER WITH CONDITION CHECK
	// ========================================
	function initColorPicker() {
		// Verify jQuery.fn.wpColorPicker exists before calling
		if (typeof $.fn.wpColorPicker === 'function') {
			$('.thim-color-field').wpColorPicker();
		}
	}

	// ========================================
	// 8. TAB NAVIGATION
	// ========================================
	function initTabNavigation() {
		var $tabNav = $('.thim-tab-nav');
		if (!$tabNav.length) {
			return;
		}

		var $tabs = $tabNav.find('a');
		var $tabContents = $('.thim-tab-content');

		if (!$tabs.length) {
			return;
		}

		$tabs.on('click', function (e) {
			e.preventDefault();

			// Remove active class from all tabs and contents
			$tabs.removeClass('active');
			$tabContents.removeClass('active');

			// Add active class to clicked tab
			$(this).addClass('active');

			// Show corresponding content
			var targetId = $(this).attr('href').substring(1);
			$('#' + targetId).addClass('active');
		});

		// Trigger first tab by default
		$tabs.first().click();
	}

	// ========================================
	// INITIALIZE ALL MODULES
	// ========================================
	initPortfolioToggle();
	initVideoDialog();
	initImageVideoField();
	initImageField();
	initFileUpload();
	initRepeater();
	initColorPicker();
	initTabNavigation();
});
