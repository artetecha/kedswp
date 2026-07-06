const { addQueryArgs } = wp.url;

const showListCertificates = () => {
    const elementCertificateProfile = document.querySelector('.learnpress-certificates-profile');
    if (!elementCertificateProfile) {
        return;
    }

    const userID = elementCertificateProfile.querySelector('input[name="userID"]').value;

    let filter = {
        userID: userID,
        page: 1,
    };

    //loader template tab
    const skeletonTab = () => {
        getResponse(elementCertificateProfile, { ...filter });
    };

    const getResponse = async (ele, filter) => {
        const skeleton = ele.querySelector('.lp-skeleton-animation');

        try {
            const response = await wp.apiFetch({
                path: addQueryArgs('lp/v1/certificate/items-profile', filter),
                method: 'GET',
            });

            const { data, status, message } = response;

            if (status === 'error') {
                throw new Error(message || 'Error');
            }

            data && ele.insertAdjacentHTML('beforeend', data.template);

            await getImageCertificate(data.certKey);
        } catch (error) {
            ele.insertAdjacentHTML(
                'beforeend',
                `<div class="lp-ajax-message error" style="display:block">${
                    error.message || 'Error: Query lp/v1/certificate/items-profile'
                }</div>`
            );
        }

        skeleton && skeleton.remove();
    };

    const getImageCertificate = async (certKey) => {
        const listItemCertificate = document.querySelectorAll('.learnpress-certificates-profile .certificate-item');

        if (!listItemCertificate.length) return;

        for (const item of listItemCertificate) {
            const eleConfig = item.querySelector('input.lp-data-config-cer');
            if (!eleConfig || !eleConfig.value) continue;
            const dataConfig = JSON.parse(eleConfig.value);
            const elemParent = item.querySelector('.certificate-preview');

            if (elemParent === null) continue;

            const key = elemParent.dataset.key;

            if (!certKey.includes(key)) continue;

            eleConfig.value = '';
            await LP_Certificate(elemParent, dataConfig);
        }
    };

    const showMoreReview = async (filter, ele, btnLoadReview = false) => {
        try {
            const response = await wp.apiFetch({
                path: addQueryArgs('lp/v1/certificate/items-profile', filter),
                method: 'GET',
            });

            const { data, status, message } = response;

            if (status === 'success' && data) {
                ele.insertAdjacentHTML('beforeend', data.template);
            } else {
                ele.insertAdjacentHTML('beforeend', `<li class="lp-ajax-message error" style="display:block">${message}</li>`);
            }

            if (btnLoadReview) {
                btnLoadReview.classList.remove('loading');

                const paged = btnLoadReview.dataset.paged;
                const numberPage = btnLoadReview.dataset.number;

                if (numberPage <= paged) {
                    btnLoadReview.remove();
                }

                btnLoadReview.dataset.paged = parseInt(paged) + 1;
            }

            await getImageCertificate(data.certKey);
        } catch (error) {
            ele.insertAdjacentHTML('beforeend', `<li class="lp-ajax-message error" style="display:block">${error}</li>`);
        }
    };

    document.addEventListener('click', function (e) {
        const btnLoadReview = document.querySelector('#certificates-load-more');

        if (btnLoadReview && btnLoadReview.contains(e.target)) {
            btnLoadReview.classList.add('loading');
            const paged = btnLoadReview && btnLoadReview.dataset.paged;
            filter.page = paged;

            const element = document.querySelector('#profile-content-certificates .profile-certificates');
            showMoreReview({ ...filter }, element, btnLoadReview);
        }
    });

    skeletonTab();
};

const initCertShare = () => {
    if (window.__lpCertShareInit) {
        return;
    }

    const settings = window.lpCertShareSettings;
    if (!settings || !settings.ajaxUrl || !settings.nonce) {
        return;
    }

    window.__lpCertShareInit = true;

    const i18n = settings.i18n || {};

    const postToggle = (key, shared) => {
        const form = new FormData();
        form.append('lp-load-ajax', 'lp_cert_toggle_share');
        form.append('nonce', settings.nonce);
        form.append('data', JSON.stringify({ cert_key: key, shared: shared ? 1 : 0 }));

        return fetch(settings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form,
        }).then((r) => r.json());
    };

    document.addEventListener('change', (ev) => {
        const input = ev.target;
        if (!input.classList || !input.classList.contains('lp-cert-share__input')) {
            return;
        }
        const wrap = input.closest('.lp-cert-share');
        if (!wrap) return;

        const key = wrap.getAttribute('data-cert-key');
        const label = wrap.querySelector('.lp-cert-share__label');
        const shared = input.checked;

        input.disabled = true;
        postToggle(key, shared)
            .then((res) => {
                if (res && res.status === 'success') {
                    if (label) label.textContent = shared ? i18n.public : i18n.private;
                } else {
                    input.checked = !shared;
                    alert((res && res.message) || i18n.error);
                }
            })
            .catch(() => {
                input.checked = !shared;
                alert(i18n.error);
            })
            .finally(() => {
                input.disabled = false;
            });
    });

};

document.addEventListener('DOMContentLoaded', function () {
    showListCertificates();
    initCertShare();
});
