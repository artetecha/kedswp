/******/ (() => { // webpackBootstrap
/*!************************************************************!*\
  !*** ./assets/src/js/backend/admin-course-certificates.js ***!
  \************************************************************/
(function ($) {
  const L = window.lpCertAdminCourse || {};
  const i18n = L.i18n || {};
  const courseId = parseInt(L.course_id, 10) || 0;
  const uploadIconUrl = L.upload_icon_url || '';
  function buildUploadPlaceholder() {
    const wrap = document.createElement('div');
    wrap.className = 'lp-cert-info-upload-area__placeholder';
    const icon = document.createElement('img');
    icon.className = 'lp-cert-info-upload-area__icon';
    icon.src = uploadIconUrl;
    icon.alt = '';
    wrap.appendChild(icon);
    const textWrap = document.createElement('div');
    textWrap.className = 'lp-cert-info-upload-area__text';
    const linkSpan = document.createElement('span');
    linkSpan.className = 'lp-cert-info-upload-area__link';
    linkSpan.textContent = i18n.click_to_upload || '';
    textWrap.appendChild(linkSpan);
    const dragSpan = document.createElement('span');
    dragSpan.className = 'lp-cert-info-upload-area__drag';
    dragSpan.textContent = i18n.or_drag_drop || '';
    textWrap.appendChild(dragSpan);
    wrap.appendChild(textWrap);
    const hint = document.createElement('div');
    hint.className = 'lp-cert-info-upload-area__hint';
    hint.textContent = i18n.jpg_png_less_1mb || '';
    wrap.appendChild(hint);
    return wrap;
  }
  const lpCertFieldRows = ['#lp-cert-info-row-image', '#lp-cert-info-row-title', '#lp-cert-info-row-description'];
  let lpCertInfoFrame;
  function setFieldRowsDisabled(disabled) {
    $.each(lpCertFieldRows, function (i, sel) {
      if (disabled) {
        $(sel).addClass('lp-option-disabled');
      } else {
        $(sel).removeClass('lp-option-disabled');
      }
    });
  }
  function setEnableCheckbox(hasCert) {
    const $cb = $('#lp_cert_info_enable');
    $cb.prop('disabled', !hasCert);
    if (!hasCert) {
      $cb.prop('checked', false);
      setFieldRowsDisabled(true);
    }
  }
  function persistEnableDisabled(cId) {
    if (!cId || !window.lpAJAXG) {
      return;
    }
    window.lpAJAXG.fetchAJAX({
      action: 'save_course_cert_info',
      course_id: cId,
      enable: 0,
      image: $('#lp_cert_info_image').val(),
      title: $.trim($('#lp_cert_info_title').val()),
      description: $('#lp_cert_info_description').val()
    }, {
      error: function () {
        console.error('Failed to persist enable=0 after cert removal.');
      }
    });
  }
  function showCertPopup(options) {
    if (typeof window.lpCertConfirm === 'function') {
      return window.lpCertConfirm(options);
    }
    alert(options.title + (options.text ? '\n' + options.text : ''));
    return Promise.resolve({
      isConfirmed: true
    });
  }
  function renderUploadPlaceholder() {
    const target = document.getElementById('lp-cert-info-upload-area');
    if (!target) {
      return;
    }
    while (target.firstChild) {
      target.removeChild(target.firstChild);
    }
    target.appendChild(buildUploadPlaceholder());
  }
  function openMediaPicker() {
    if (lpCertInfoFrame) {
      lpCertInfoFrame.open();
      return;
    }
    lpCertInfoFrame = wp.media({
      title: i18n.select_image || '',
      button: {
        text: i18n.use_this_image || ''
      },
      multiple: false
    });
    lpCertInfoFrame.on('select', function () {
      const attachment = lpCertInfoFrame.state().get('selection').first().toJSON();
      const url = attachment.url;
      $('#lp_cert_info_image').val(url);
      const target = document.getElementById('lp-cert-info-upload-area');
      if (!target) {
        return;
      }
      while (target.firstChild) {
        target.removeChild(target.firstChild);
      }
      const img = document.createElement('img');
      img.src = url;
      img.alt = '';
      target.appendChild(img);
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'lp-cert-info-remove-image';
      removeBtn.setAttribute('aria-label', i18n.remove_image_aria || '');
      removeBtn.textContent = '×';
      target.appendChild(removeBtn);
    });
    lpCertInfoFrame.open();
  }
  $(document).on('change', '#lp_cert_info_enable', function () {
    setFieldRowsDisabled(!$(this).is(':checked'));
  });
  $(document).on('click', '#lp-cert-info-upload-area', function (e) {
    e.preventDefault();
    openMediaPicker();
  });
  $(document).on('click', '.lp-cert-info-remove-image', function (e) {
    e.preventDefault();
    e.stopPropagation();
    showCertPopup({
      title: i18n.are_you_sure || '',
      text: i18n.image_will_be_removed || '',
      icon: 'warning',
      confirmButtonText: i18n.yes || '',
      cancelButtonText: i18n.cancel || ''
    }).then(function (result) {
      if (result.isConfirmed) {
        $('#lp_cert_info_image').val('');
        renderUploadPlaceholder();
      }
    });
  });
  function getActiveCertificateId() {
    return parseInt($('#certificate-browser .lp-certificates-new .theme.active').first().data('id'), 10) || 0;
  }
  function syncActiveCertificate(certId) {
    const $themes = $('#certificate-browser .lp-certificates-new .theme');
    $themes.removeClass('active');
    if (certId) {
      $themes.filter('[data-id="' + certId + '"]').addClass('active');
    }
    setEnableCheckbox(!!certId);
  }
  function clearUpdatingCertificate() {
    $('#certificate-browser .theme.updating').removeClass('updating');
  }
  function updateCerOfCourse(cId, certId) {
    const currentCertId = getActiveCertificateId();
    if (!cId || !window.lpAJAXG) {
      clearUpdatingCertificate();
      return;
    }
    window.lpAJAXG.fetchAJAX({
      action: 'update_course_certificate',
      course_id: cId,
      cert_id: certId
    }, {
      success: function (response) {
        if (response && response.status === 'success') {
          syncActiveCertificate(certId);
          if (!certId) {
            persistEnableDisabled(cId);
          }
          return;
        }
        syncActiveCertificate(currentCertId);
        console.error(response && response.message || 'Failed to update course certificate.');
      },
      error: function (error) {
        syncActiveCertificate(currentCertId);
        console.error(error);
      },
      completed: function () {
        clearUpdatingCertificate();
      }
    });
  }
  function getCourseID() {
    const postID = $('#post_ID').val();
    if (postID) {
      return postID;
    }
    return $('.lp-course-cert-browser-new').data('course-id');
  }
  $(document).on('click', '.button-assign-certificate', function (e) {
    e.preventDefault();
    const cId = getCourseID();
    const $theme = $(this).closest('.theme');
    const certID = $theme.data('id');
    $theme.addClass('updating');
    updateCerOfCourse(cId, certID);
  });
  $(document).on('click', '.button-remove-certificate', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const cId = getCourseID();
    const $theme = $(this).closest('.theme');
    showCertPopup({
      title: i18n.remove_cert_title || '',
      text: i18n.remove_cert_text || '',
      icon: 'warning',
      confirmButtonText: i18n.remove || '',
      cancelButtonText: i18n.cancel || ''
    }).then(function (result) {
      if (!result || !result.isConfirmed) {
        return;
      }
      $theme.addClass('updating');
      updateCerOfCourse(cId, 0);
    });
  });
  $(document).on('click', '#lp-cert-info-save-btn', function (e) {
    e.preventDefault();
    const $btn = $(this);
    if ($btn.prop('disabled')) {
      return;
    }
    const isEnabled = $('#lp_cert_info_enable').is(':checked');
    const title = $.trim($('#lp_cert_info_title').val());
    if (isEnabled && !title) {
      showCertPopup({
        title: i18n.title_required || '',
        text: i18n.enter_title_text || '',
        icon: 'error',
        showCancelButton: false,
        confirmButtonText: i18n.ok || ''
      });
      return;
    }
    $btn.prop('disabled', true).text(i18n.saving || '');
    const dataSend = {
      action: 'save_course_cert_info',
      course_id: courseId,
      enable: isEnabled ? 1 : 0,
      image: $('#lp_cert_info_image').val(),
      title: title,
      description: $('#lp_cert_info_description').val()
    };
    window.lpAJAXG.fetchAJAX(dataSend, {
      success: function (response) {
        if (response.status === 'success') {
          showCertPopup({
            title: i18n.saved || '',
            text: i18n.saved_text || '',
            icon: 'success',
            showCancelButton: false,
            confirmButtonText: i18n.ok || ''
          });
        } else {
          showCertPopup({
            title: i18n.error || '',
            text: response.message || i18n.error_save_text || '',
            icon: 'error',
            showCancelButton: false,
            confirmButtonText: i18n.ok || ''
          });
        }
      },
      error: function () {
        showCertPopup({
          title: i18n.error || '',
          text: i18n.error_save_text || '',
          icon: 'error',
          showCancelButton: false,
          confirmButtonText: i18n.ok || ''
        });
      },
      completed: function () {
        $btn.prop('disabled', false).text(i18n.save || '');
      }
    });
  });
  $(document).on('click', '.lp-cer-btn-load-more-new', function (e) {
    e.preventDefault();
    const $btn = $(this);
    if ($btn.prop('disabled')) {
      return;
    }
    const offset = parseInt($btn.data('offset'), 10);
    const cId = $btn.data('course-id');
    const certActive = $btn.data('cert-active');
    const textOriginal = $btn.text();
    $btn.prop('disabled', true).text(textOriginal.trim() + '...');
    const isCourseBuilder = parseInt($('.lp-course-cert-browser-new').data('is-course-builder'), 10) === 1;
    const dataSend = {
      action: 'certificate_load_more_course_certs',
      offset: offset,
      course_id: cId,
      cert_active: certActive,
      is_course_builder: isCourseBuilder ? 1 : 0
    };
    window.lpAJAXG.fetchAJAX(dataSend, {
      success: function (response) {
        if (response.status === 'success' && response.data && response.data.html) {
          $('.lp-certificates-new .add-new-theme').before(response.data.html);
          $btn.data('offset', offset + 6);
          if (!response.data.has_more) {
            $btn.remove();
          }
        }
      },
      error: function () {
        console.error('Error loading more certificates');
      },
      completed: function () {
        $btn.prop('disabled', false).text(textOriginal);
      }
    });
  });
})(jQuery);
/******/ })()
;
//# sourceMappingURL=admin-course-certificates.js.map