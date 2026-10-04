/**
 * core/rbnComponents.js — Zero-Dependency UI Components Engine 📱🪟
 * Handles: Dropdowns, Collapse/Accordion, Offcanvas / Mobile Drawer
 */
(function (window, document) {
    'use strict';

    /* 0. Otonom Sovereign Select Dönüştürücü (Modal & Form Select Enhancer) 🪄✨ */
    function rbnEnhanceSelects(container) {
        const root = container || document;
        const selects = root.querySelectorAll('.modal select.form-select, select[data-rbn-select]');
        
        selects.forEach(select => {
            // Eğer zaten dönüştürülmüşse wrapper'ı bul ve menüyü yenile 🔄
            let wrapper = select.nextElementSibling;
            if (wrapper && wrapper.classList.contains('rbn-modal-dropdown')) {
                wrapper.remove();
            }

            select.setAttribute('data-rbn-enhanced', 'true');
            select.style.position = 'absolute';
            select.style.opacity = '0';
            select.style.pointerEvents = 'none';
            select.style.width = '1px';
            select.style.height = '1px';

            wrapper = document.createElement('div');
            wrapper.className = 'dropdown rbn-dropdown rbn-modal-dropdown w-100';

            const selectedOption = select.options[select.selectedIndex] || select.options[0];
            const initialText = selectedOption ? selectedOption.text : 'Seçiniz';
            const initialVal = selectedOption ? selectedOption.value : '';

            // Tetikleyici Buton
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = (select.className.replace('d-none', '') + ' d-flex align-items-center justify-content-between dropdown-toggle text-start').trim();
            btn.setAttribute('data-bs-toggle', 'dropdown');
            btn.setAttribute('aria-expanded', 'false');
            btn.innerHTML = `<span class="selected-text">${initialText}</span><i class="ri-arrow-down-s-line opacity-50"></i>`;

            // Açılır Menü
            const menu = document.createElement('ul');
            menu.className = 'dropdown-menu rbn-dropdown-menu shadow-lg py-1 w-100 mt-1 font-monospace';

            Array.from(select.options).forEach(opt => {
                if (opt.hidden || opt.disabled) return; // Gizli/devre dışı seçenekleri custom menüye basma! 🎯

                const li = document.createElement('li');
                const a = document.createElement('a');
                a.className = `dropdown-item rbn-dropdown-item py-2 d-flex align-items-center justify-content-between ${opt.value === initialVal ? 'active' : ''}`;
                a.href = 'javascript:void(0)';
                a.setAttribute('data-value', opt.value);
                a.innerHTML = `<span>${opt.text}</span>${opt.value === initialVal ? '<i class="ri-check-line text-warning"></i>' : ''}`;

                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));

                    btn.querySelector('.selected-text').innerHTML = opt.text;
                    menu.querySelectorAll('.dropdown-item').forEach(el => {
                        el.classList.remove('active');
                        const icon = el.querySelector('.ri-check-line');
                        if (icon) icon.remove();
                    });

                    a.classList.add('active');
                    if (!a.querySelector('.ri-check-line')) {
                        a.insertAdjacentHTML('beforeend', '<i class="ri-check-line text-warning"></i>');
                    }
                    menu.classList.remove('show');
                });

                li.appendChild(a);
                menu.appendChild(li);
            });

            wrapper.appendChild(btn);
            wrapper.appendChild(menu);
            select.parentNode.insertBefore(wrapper, select.nextSibling);
        });
    }

    /* 1. Evrensel Dropdown Menü Motoru (.dropdown-toggle / [data-rbn-toggle="dropdown"]) */
    function rbnInitDropdowns() {
        document.addEventListener('click', function (e) {
            const toggle = e.target.closest('[data-bs-toggle="dropdown"], [data-rbn-toggle="dropdown"], .dropdown-toggle');
            
            // Eğer dropdown butonuna tıklandıysa
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();

                const parent = toggle.closest('.dropdown, .btn-group, .rbn-dropdown') || toggle.parentElement;
                const menu = parent ? parent.querySelector('.dropdown-menu, .rbn-dropdown-menu') : null;

                if (menu) {
                    const isOpen = menu.classList.contains('show');
                    // Diğer açık dropdownları kapat
                    document.querySelectorAll('.dropdown-menu.show, .rbn-dropdown-menu.show').forEach(m => m.classList.remove('show'));
                    
                    if (!isOpen) {
                        menu.classList.add('show');
                    }
                }
                return;
            }

            // Sayfa dışına tıklandıysa tüm dropdownları kapat
            if (!e.target.closest('.dropdown-menu, .rbn-dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu.show, .rbn-dropdown-menu.show').forEach(m => m.classList.remove('show'));
            }
        });

        // Mouse dropdown alanının dışına çıktığında zarifçe kapat 🖱️✨
        document.addEventListener('mouseover', function (e) {
            const dropdown = e.target.closest('.dropdown, .btn-group, .rbn-dropdown');
            if (dropdown && !dropdown._hasMouseEvents) {
                dropdown._hasMouseEvents = true;
                let closeTimer = null;

                dropdown.addEventListener('mouseleave', function () {
                    closeTimer = setTimeout(function () {
                        const menu = dropdown.querySelector('.dropdown-menu.show, .rbn-dropdown-menu.show');
                        if (menu) {
                            menu.classList.remove('show');
                        }
                    }, 250);
                });

                dropdown.addEventListener('mouseenter', function () {
                    if (closeTimer) {
                        clearTimeout(closeTimer);
                        closeTimer = null;
                    }
                });
            }
        });
    }

    /* 2. Evrensel Accordion & Collapse Motoru ([data-bs-toggle="collapse"], [data-rbn-toggle="collapse"]) */
    function rbnInitCollapse() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-bs-toggle="collapse"], [data-rbn-toggle="collapse"], .accordion-button');
            if (!btn) return;

            e.preventDefault();
            const targetSelector = btn.getAttribute('data-bs-target') || btn.getAttribute('data-rbn-target') || btn.getAttribute('href');
            if (!targetSelector || targetSelector === '#') return;

            const target = document.querySelector(targetSelector);
            if (!target) return;

            const isShown = target.classList.contains('show');
            const parentSelector = btn.getAttribute('data-bs-parent') || btn.getAttribute('data-rbn-parent');

            // Eğer accordion ise diğer kardeşleri kapat
            if (parentSelector) {
                const parent = document.querySelector(parentSelector);
                if (parent) {
                    parent.querySelectorAll('.collapse.show, .rbn-collapse.show').forEach(c => {
                        if (c !== target) c.classList.remove('show');
                    });
                    parent.querySelectorAll('.accordion-button').forEach(b => {
                        if (b !== btn) b.classList.add('collapsed');
                    });
                }
            }

            if (isShown) {
                target.classList.remove('show');
                btn.classList.add('collapsed');
            } else {
                target.classList.add('show');
                btn.classList.remove('collapsed');
            }
        });
    }

    /* 3. Evrensel Offcanvas / Mobil Menü ([data-bs-toggle="offcanvas"], [data-rbn-toggle="offcanvas"]) */
    function rbnInitOffcanvas() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-bs-toggle="offcanvas"], [data-rbn-toggle="offcanvas"], [data-rbn-drawer]');
            if (btn) {
                e.preventDefault();
                const targetSelector = btn.getAttribute('data-bs-target') || btn.getAttribute('data-rbn-target') || btn.getAttribute('href');
                if (!targetSelector) return;

                const target = document.querySelector(targetSelector);
                if (target) {
                    target.classList.toggle('show');
                    
                    // Backdrop ekle/kaldır
                    let backdrop = document.querySelector('.offcanvas-backdrop, .rbn-offcanvas-backdrop');
                    if (target.classList.contains('show')) {
                        if (!backdrop) {
                            backdrop = document.createElement('div');
                            backdrop.className = 'offcanvas-backdrop fade show';
                            document.body.appendChild(backdrop);
                            backdrop.onclick = () => {
                                target.classList.remove('show');
                                backdrop.remove();
                            };
                        }
                    } else if (backdrop) {
                        backdrop.remove();
                    }
                }
                return;
            }

            // Kapat butonu (.btn-close / [data-bs-dismiss="offcanvas"])
            const closeBtn = e.target.closest('[data-bs-dismiss="offcanvas"], [data-rbn-dismiss="offcanvas"]');
            if (closeBtn) {
                const target = closeBtn.closest('.offcanvas, .rbn-offcanvas');
                if (target) {
                    target.classList.remove('show');
                    const backdrop = document.querySelector('.offcanvas-backdrop, .rbn-offcanvas-backdrop');
                    if (backdrop) backdrop.remove();
                }
            }
        });
    }

    /* 4. Evrensel Date Input Takvim Açıcı (Tüm input[type="date"] tıklamasında otomatik popup) */
    function rbnInitDatePickers() {
        document.addEventListener('click', function (e) {
            const dateInput = e.target.closest('input[type="date"], input[type="time"], input[type="datetime-local"]');
            if (dateInput && typeof dateInput.showPicker === 'function') {
                try {
                    dateInput.showPicker();
                } catch (err) {}
            }
        });
    }

    /* 5. Evrensel Tab & Pill Motoru ([data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-rbn-toggle="tab"]) 📑⚡ */
    function rbnInitTabs() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-rbn-toggle="tab"], [data-rbn-toggle="pill"], [data-tab-target]');
            if (!btn) return;

            e.preventDefault();
            const targetSelector = btn.getAttribute('data-bs-target') || btn.getAttribute('data-tab-target') || btn.getAttribute('data-rbn-target') || btn.getAttribute('href');
            if (!targetSelector || targetSelector === '#') return;

            const target = document.querySelector(targetSelector.startsWith('#') ? targetSelector : '#' + targetSelector);
            if (!target) return;

            // Nav ebeveynini bul ve butonların active sınıfını güncelle
            const nav = btn.closest('.nav, .nav-pills, .nav-tabs, .rbn-nav, .rbn-nav-pills, .rbn-nav-tabs') || btn.parentElement;
            if (nav) {
                nav.querySelectorAll('.active').forEach(b => b.classList.remove('active'));
            }
            btn.classList.add('active');

            // Hedef pane'in kardeş panelerini gizle, hedefi aç (iç içe nested tabları bozma!)
            const tabContent = target.closest('.tab-content, .rbn-tab-content') || target.parentElement;
            if (tabContent) {
                const panes = tabContent.querySelectorAll(':scope > .tab-pane, :scope > .rbn-tab-pane');
                if (panes.length > 0) {
                    panes.forEach(pane => {
                        if (pane === target) {
                            pane.style.display = 'block';
                            pane.classList.add('show', 'active');
                        } else {
                            pane.style.display = 'none';
                            pane.classList.remove('show', 'active');
                        }
                    });
                } else {
                    tabContent.querySelectorAll('.tab-pane, .rbn-tab-pane').forEach(pane => {
                        if (pane.parentElement === tabContent) {
                            if (pane === target) {
                                pane.style.display = 'block';
                                pane.classList.add('show', 'active');
                            } else {
                                pane.style.display = 'none';
                                pane.classList.remove('show', 'active');
                            }
                        }
                    });
                }
            }
        });
    }

    // Auto Init
    if (typeof window.rbnReady === 'function') {
        window.rbnReady(() => {
            rbnEnhanceSelects();
            rbnInitDropdowns();
            rbnInitCollapse();
            rbnInitOffcanvas();
            rbnInitDatePickers();
            rbnInitTabs();
        });
    }

    // Modal açıldığında modal içindeki select'leri de otomatik dönüştür 🪄
    document.addEventListener('rbnModalLoaded', function (e) {
        if (e.detail && e.detail.container) {
            rbnEnhanceSelects(e.detail.container);
        }
    });

    window.rbnEnhanceSelects = rbnEnhanceSelects;
})(window, document);
