(function () {
    'use strict';

    // ---------------------------------------------------------------- sidebar
    var shell = document.getElementById('adminShell');
    var toggle = document.getElementById('adminSidebarToggle');
    var overlay = document.getElementById('adminSidebarOverlay');

    function closeSidebar() {
        if (!shell) return;
        shell.classList.remove('is-sidebar-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    function toggleSidebar() {
        if (!shell) return;
        var isOpen = shell.classList.toggle('is-sidebar-open');
        if (toggle) toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    if (toggle) {
        toggle.addEventListener('click', toggleSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // ------------------------------------------------------ подтверждение удаления
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        var message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    // ------------------------------------------------------- автогенерация slug
    // Транслитерация — грубое приближение к serverside slugify(), только для UX-предпросмотра;
    // окончательный slug всё равно нормализует сервер.
    var translitMap = {
        а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'e', ж: 'zh', з: 'z', и: 'i',
        й: 'y', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't',
        у: 'u', ф: 'f', х: 'h', ц: 'ts', ч: 'ch', ш: 'sh', щ: 'sch', ъ: '', ы: 'y', ь: '',
        э: 'e', ю: 'yu', я: 'ya'
    };

    function transliterate(text) {
        return text
            .toLowerCase()
            .split('')
            .map(function (ch) {
                return translitMap.hasOwnProperty(ch) ? translitMap[ch] : ch;
            })
            .join('')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    document.querySelectorAll('[data-slug-name]').forEach(function (nameInput) {
        var form = nameInput.closest('form');
        if (!form) return;
        var slugInput = form.querySelector('[data-slug-target]');
        if (!slugInput) return;

        // Если поле slug уже заполнено вручную пользователем — больше не трогаем его автоматически.
        var slugTouched = slugInput.value.trim() !== '';
        slugInput.addEventListener('input', function () {
            slugTouched = true;
        });

        nameInput.addEventListener('input', function () {
            if (slugTouched) return;
            slugInput.value = transliterate(nameInput.value);
        });
    });

    // -------------------------------------------------------- предпросмотр фото
    document.querySelectorAll('[data-image-preview]').forEach(function (input) {
        var targetId = input.getAttribute('data-image-preview');
        var target = document.getElementById(targetId);
        if (!target) return;

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function (event) {
                target.innerHTML = '<img src="' + event.target.result + '" alt="">';
            };
            reader.readAsDataURL(file);
        });
    });

    // --------------------------------------------------- автоотправка фильтров
    document.querySelectorAll('[data-autosubmit]').forEach(function (field) {
        field.addEventListener('change', function () {
            var form = field.closest('form');
            if (form) form.submit();
        });
    });

    // ------------------------------------------------------- подсказки графика
    var tooltip = document.createElement('div');
    tooltip.className = 'chart-tooltip';
    document.body.appendChild(tooltip);

    function showTooltip(bar, event) {
        var date = bar.getAttribute('data-date');
        var revenue = bar.getAttribute('data-revenue');
        var orders = bar.getAttribute('data-orders');
        tooltip.innerHTML = '<strong>' + date + '</strong>Выручка: ' + revenue + '<br>Заказов: ' + orders;
        tooltip.classList.add('is-visible');
        positionTooltip(bar, event);
    }

    function positionTooltip(bar, event) {
        var rect = bar.getBoundingClientRect();
        var x = event && typeof event.clientX === 'number' ? event.clientX : rect.left + rect.width / 2;
        var top = rect.top - 10;
        tooltip.style.left = Math.min(x + 12, window.innerWidth - 230) + 'px';
        tooltip.style.top = Math.max(top - tooltip.offsetHeight, 8) + 'px';
    }

    function hideTooltip() {
        tooltip.classList.remove('is-visible');
    }

    document.querySelectorAll('.chart-bar').forEach(function (bar) {
        bar.addEventListener('mouseenter', function (event) { showTooltip(bar, event); });
        bar.addEventListener('mousemove', function (event) { positionTooltip(bar, event); });
        bar.addEventListener('mouseleave', hideTooltip);
        bar.addEventListener('focus', function (event) { showTooltip(bar, event); });
        bar.addEventListener('blur', hideTooltip);
    });
})();
