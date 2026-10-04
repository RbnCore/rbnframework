/**
 * rbnAi.js — Sovereign AI UI Assistant 🧠🎨🛰️
 * Part of the RBN 3.5 "Masterpiece" Architecture.
 */
(function () {
    'use strict';

    function initAiImageGenerators() {
        const buttons = document.querySelectorAll('[data-rbn-ai="image"]');
        buttons.forEach(btn => {
            if (btn.dataset.rbnAiBound) return;
            btn.dataset.rbnAiBound = 'true';

            btn.addEventListener('click', function (e) {
                e.preventDefault();

                const titleSel = btn.getAttribute('data-source-title');
                const catSel = btn.getAttribute('data-source-category');
                const projectSel = btn.getAttribute('data-source-project');
                const promptSel = btn.getAttribute('data-source-prompt');
                const targetInputSel = btn.getAttribute('data-target-input');
                const targetPreviewSel = btn.getAttribute('data-target-preview');
                const targetPlaceholderSel = btn.getAttribute('data-target-placeholder');

                const projectKey = projectSel ? document.querySelector(projectSel)?.value : '';
                const customPrompt = promptSel ? document.querySelector(promptSel)?.value.trim() : '';

                let hasError = false;
                if (titleSel) {
                    const titleVal = document.querySelector(titleSel)?.value.trim();
                    if (!titleVal) {
                        const msg = btn.getAttribute('data-error-title') || 'Lütfen gerekli başlık/ipucu alanını doldurun!';
                        if (typeof RbnAlert !== 'undefined') RbnAlert.error('Eksik Bilgi', msg);
                        else alert(msg);
                        hasError = true;
                    }
                }

                if (!hasError && catSel) {
                    const catVal = document.querySelector(catSel)?.value;
                    if (!catVal) {
                        const msg = btn.getAttribute('data-error-category') || 'Lütfen gerekli kategori alanını seçin!';
                        if (typeof RbnAlert !== 'undefined') RbnAlert.error('Eksik Bilgi', msg);
                        else alert(msg);
                        hasError = true;
                    }
                }

                if (hasError) return;

                const idSel = btn.getAttribute('data-source-id') || 'input[name="id"]';
                const idVal = document.querySelector(idSel)?.value || '';
                const title = titleSel ? document.querySelector(titleSel)?.value.trim() : '';
                const categoryId = catSel ? document.querySelector(catSel)?.value : '';

                const generateAction = () => {
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Üretiliyor...';

                    if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.loading === 'function') {
                        RbnAlert.loading('Yapay Zeka Görseliniz Üretiliyor...');
                    }

                    RbnService.post(btn.getAttribute('data-url') || btn.href, {
                        id: idVal,
                        title: title,
                        category_id: categoryId,
                        project_key: projectKey,
                        custom_prompt: customPrompt
                    }, { silent: true }).then(res => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;

                        if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.close === 'function') {
                            RbnAlert.close();
                        }

                        if (res.success && res.url) {
                            if (targetInputSel) {
                                const inputEl = document.querySelector(targetInputSel);
                                if (inputEl) inputEl.value = res.url;
                            }

                            if (targetPreviewSel) {
                                const previewImg = document.querySelector(targetPreviewSel);
                                if (previewImg) {
                                    previewImg.src = res.url;
                                    previewImg.classList.remove('d-none');
                                }
                            }

                            if (targetPlaceholderSel) {
                                const placeholder = document.querySelector(targetPlaceholderSel);
                                if (placeholder) placeholder.classList.add('d-none');
                            }

                            if (typeof RbnAlert !== 'undefined') {
                                RbnAlert.success('Başarılı', 'Yapay zeka görseli başarıyla üretildi! 🎉');
                            }
                        } else {
                            if (typeof RbnAlert !== 'undefined') RbnAlert.error('Hata', res.message || 'Görsel üretilemedi.');
                            else alert(res.message || 'Hata oluştu.');
                        }
                    }).catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.close === 'function') {
                            RbnAlert.close();
                        }
                    });
                };

                if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.confirm === 'function') {
                    RbnAlert.confirm(
                        btn.getAttribute('data-confirm-title') || 'Görsel Üretilsin mi?',
                        btn.getAttribute('data-confirm') || 'Yapay zeka ile yeni bir yazı görseli üretmek istediğinize emin misiniz?',
                        {
                            type: 'action',
                            confirmButtonText: 'Üret',
                            cancelButtonText: 'İptal'
                        }
                    ).then(choice => {
                        if (choice.isConfirmed) {
                            generateAction();
                        }
                    });
                } else {
                    generateAction();
                }
            });
        });
    }

    function initAiTextGenerators() {
        const buttons = document.querySelectorAll('[data-rbn-ai="text"]');
        buttons.forEach(btn => {
            if (btn.dataset.rbnAiBound) return;
            btn.dataset.rbnAiBound = 'true';

            btn.addEventListener('click', function (e) {
                e.preventDefault();

                const sourceSel = btn.getAttribute('data-source');
                const targetSel = btn.getAttribute('data-target');
                const prompt = sourceSel ? document.querySelector(sourceSel)?.value.trim() : '';

                if (!prompt) {
                    const msg = 'Lütfen üretilecek içerik için bir ipucu veya başlık yazın!';
                    if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.error === 'function') RbnAlert.error('Eksik Bilgi', msg);
                    else alert(msg);
                    return;
                }

                const generateAction = () => {
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Üretiliyor...';

                    if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.loading === 'function') {
                        RbnAlert.loading('Yapay Zeka İçeriğiniz Üretiliyor...');
                    }

                    RbnService.post(btn.getAttribute('data-url') || btn.href, {
                        prompt: prompt
                    }, { silent: true }).then(res => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;

                        if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.close === 'function') {
                            RbnAlert.close();
                        }

                        if (res.success && res.text) {
                            const targetEl = document.querySelector(targetSel);
                            if (targetEl) {
                                if (typeof $ !== 'undefined' && $(targetEl).data('summernote')) {
                                    $(targetEl).summernote('code', res.text);
                                } else {
                                    targetEl.value = res.text;
                                }
                            }
                            if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.success === 'function') {
                                RbnAlert.success('Başarılı', 'İçerik başarıyla üretildi! 🎉');
                            }
                        } else {
                            if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.error === 'function') RbnAlert.error('Hata', res.message || 'İçerik üretilemedi.');
                            else alert(res.message || 'Hata oluştu.');
                        }
                    }).catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.close === 'function') {
                            RbnAlert.close();
                        }
                    });
                };

                if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.confirm === 'function') {
                    RbnAlert.confirm(
                        btn.getAttribute('data-confirm-title') || 'İçerik Üretilsin mi?',
                        btn.getAttribute('data-confirm') || 'Yapay zeka ile yeni bir içerik üretmek istediğinize emin misiniz?',
                        {
                            type: 'action',
                            confirmButtonText: 'Üret',
                            cancelButtonText: 'İptal'
                        }
                    ).then(choice => {
                        if (choice.isConfirmed) {
                            generateAction();
                        }
                    });
                } else {
                    generateAction();
                }
            });
        });
    }

    function initAiArticleGenerators() {
        const buttons = document.querySelectorAll('[data-rbn-ai="article"]');
        buttons.forEach(btn => {
            if (btn.dataset.rbnAiBound) return;
            btn.dataset.rbnAiBound = 'true';

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (typeof RbnUtils !== 'undefined') {
                    RbnUtils.confirm({
                        type: 'action',
                        url: btn.getAttribute('data-url') || btn.href,
                        method: 'POST',
                        title: btn.getAttribute('data-title') || 'Yapay Zeka ile Makale Üret',
                        text: btn.getAttribute('data-confirm'),
                        loadingMessage: btn.getAttribute('data-loading-message'),
                        redirect: true
                    });
                }
            });
        });
    }

    function initAiRewriteGenerators() {
        const buttons = document.querySelectorAll('[data-rbn-ai="rewrite"]');
        buttons.forEach(btn => {
            if (btn.dataset.rbnAiBound) return;
            btn.dataset.rbnAiBound = 'true';

            btn.addEventListener('click', function (e) {
                e.preventDefault();

                const idSel = btn.getAttribute('data-source-id') || 'input[name="id"]';
                const idVal = document.querySelector(idSel)?.value || '';

                const rewriteAction = () => {
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> İnsanlaştırılıyor...';

                    if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.loading === 'function') {
                        RbnAlert.loading(btn.getAttribute('data-loading-message') || 'Yapay Zeka Makalenizi Yeniden Üretiyor...');
                    }

                    RbnService.post(btn.getAttribute('data-url') || btn.href, {
                        id: idVal
                    }, { silent: true }).then(res => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;

                        if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.close === 'function') {
                            RbnAlert.close();
                        }

                        if (res.success) {
                            if (typeof RbnAlert !== 'undefined') {
                                RbnAlert.success('Başarılı', res.message || 'Makale başarıyla yeniden üretildi! 🎉');
                            }
                            if (res.reload !== false) {
                                setTimeout(() => window.location.reload(), 800);
                            }
                        } else {
                            if (typeof RbnAlert !== 'undefined') RbnAlert.error('Hata', res.message || 'İçerik yeniden üretilemedi.');
                            else alert(res.message || 'Hata oluştu.');
                        }
                    }).catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.close === 'function') {
                            RbnAlert.close();
                        }
                    });
                };

                if (typeof RbnAlert !== 'undefined' && typeof RbnAlert.confirm === 'function') {
                    RbnAlert.confirm(
                        btn.getAttribute('data-confirm-title') || 'Makale Yeniden Üretilsin mi?',
                        btn.getAttribute('data-confirm') || 'Bu makale yapay zeka ile E-E-A-T uyumlu olarak baştan yazılacak ve güncellenecektir. Devam etmek istiyor musunuz?',
                        {
                            type: 'action',
                            confirmButtonText: 'Evet, Yeniden Üret',
                            cancelButtonText: 'İptal'
                        }
                    ).then(choice => {
                        if (choice.isConfirmed) {
                            rewriteAction();
                        }
                    });
                } else {
                    rewriteAction();
                }
            });
        });
    }

    if (typeof rbnReady !== 'undefined') {
        rbnReady(function () {
            initAiImageGenerators();
            initAiTextGenerators();
            initAiArticleGenerators();
            initAiRewriteGenerators();
        });
    }
})();
